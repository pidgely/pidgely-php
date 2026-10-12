<?php

declare(strict_types=1);

namespace Pidgely\Internal;

use Pidgely\Client;
use Pidgely\Exception\ApiConnectionException;
use Pidgely\Exception\ApiTimeoutException;
use Pidgely\Exception\AuthenticationException;
use Pidgely\Exception\ConflictException;
use Pidgely\Exception\InternalServerException;
use Pidgely\Exception\NotFoundException;
use Pidgely\Exception\PermissionDeniedException;
use Pidgely\Exception\PidgelyException;
use Pidgely\Exception\RateLimitException;
use Pidgely\Exception\ValidationException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * HTTP layer: auth, JSON, timeouts, retries.
 *
 * @internal
 *
 * @phpstan-type RequestOptions array{idempotency_key?: string, headers?: array<string, string>, timeout?: float|int, max_retries?: int, batch_validation?: string}
 */
final class Transport
{
    public const RETRYABLE_STATUS = [408, 429, 500, 502, 503, 504];
    private const BACKOFF_BASE = 0.5;
    private const BACKOFF_CAP = 8.0;
    private const MAX_RETRY_AFTER = 60.0;

    /** @var \Closure(float): void */
    private \Closure $sleep;

    /**
     * The API key, held in a closure so it never shows up in var_dump(), print_r(),
     * var_export() or serialized output.
     *
     * @var \Closure(): string
     */
    private readonly \Closure $credentials;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        #[\SensitiveParameter]
        string $apiKey,
        public readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly string $userAgent,
        private readonly array $headers,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->credentials = static fn(): string => $apiKey;
        $this->sleep = static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
    }

    /**
     * Replaces the function used to wait between retries (tests).
     *
     * @param \Closure(float): void $sleep
     */
    public function setSleep(\Closure $sleep): void
    {
        $this->sleep = $sleep;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl, 'apiKey' => '[REDACTED]'];
    }

    /**
     * @param array<string, scalar|null> $query
     * @param RequestOptions             $options
     * @param array<string, string>      $headers
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $query = [], mixed $body = null, array $options = [], array $headers = []): array
    {
        $request = $this->build($method, $path, $query, $body, $options, $headers);
        $idempotencyKey = $options['idempotency_key'] ?? '';
        $maxRetries = max(0, (int) ($options['max_retries'] ?? $this->maxRetries));
        $timeout = (float) ($options['timeout'] ?? $this->timeout);
        // A POST without an idempotency key is never retried: the API might already have
        // accepted it, and a retry could send the email twice.
        $canRetry = 'POST' !== $method || '' !== $idempotencyKey;

        for ($attempt = 0; ; ++$attempt) {
            $network = false;
            try {
                $response = $this->send($request, $timeout);
                $status = $response->getStatusCode();
                if ($status >= 200 && $status < 300) {
                    return $this->decode($response);
                }
                $error = self::parseError($response);
            } catch (ClientExceptionInterface $e) {
                $network = true;
                $error = $this->networkError($e, $timeout);
            }
            $delay = $this->retryDelay($error, $attempt, $maxRetries, $canRetry, $network);
            if (null === $delay) {
                throw $error;
            }
            ($this->sleep)($delay);
        }
    }

    /**
     * @param array<string, scalar|null> $query
     * @param RequestOptions             $options
     * @param array<string, string>      $headers
     */
    private function build(string $method, string $path, array $query, mixed $body, array $options, array $headers): RequestInterface
    {
        $params = [];
        foreach ($query as $key => $value) {
            if (null !== $value) {
                $params[$key] = \is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            }
        }
        $url = $this->baseUrl.$path.([] === $params ? '' : '?'.http_build_query($params, '', '&', \PHP_QUERY_RFC3986));
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', $this->userAgent);
        foreach ([...$this->headers, ...$headers, ...($options['headers'] ?? [])] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $request = $request->withHeader('Authorization', 'Bearer '.($this->credentials)());
        if (null !== $body) {
            try {
                $json = json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                throw new PidgelyException('Unable to encode the request body: '.$e->getMessage(), 'application_error', null, [], $e);
            }
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));
        }
        $idempotencyKey = $options['idempotency_key'] ?? '';
        if ('' !== $idempotencyKey) {
            $request = $request->withHeader('Idempotency-Key', $idempotencyKey);
        }

        return $request;
    }

    private function send(RequestInterface $request, float $timeout): ResponseInterface
    {
        $request->getBody()->rewind();
        if ($this->http instanceof \GuzzleHttp\ClientInterface) {
            // Guzzle supports per-request timeouts; other PSR-18 clients use their own configuration.
            return $this->http->send($request, ['timeout' => $timeout, 'http_errors' => false, 'allow_redirects' => false]);
        }

        return $this->http->sendRequest($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $text = (string) $response->getBody();
        if ('' === trim($text)) {
            return [];
        }
        try {
            $data = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new PidgelyException('Unable to parse the API response', 'application_error', null, self::headers($response));
        }

        return \is_array($data) ? Data::stringKeys($data) : [];
    }

    private function networkError(ClientExceptionInterface $e, float $timeout): ApiConnectionException
    {
        $message = str_replace(($this->credentials)(), '[REDACTED]', $e->getMessage());
        if (str_contains($message, 'timed out') || str_contains($message, 'cURL error 28')) {
            return new ApiTimeoutException(\sprintf('Request timed out after %ss', rtrim(rtrim(number_format($timeout, 3, '.', ''), '0'), '.')));
        }

        return new ApiConnectionException('Unable to reach the Pidgely API: '.$message);
    }

    private function retryDelay(PidgelyException $error, int $attempt, int $maxRetries, bool $canRetry, bool $network): ?float
    {
        if ($attempt >= $maxRetries || !$canRetry) {
            return null;
        }
        if ($network) {
            return self::backoff($attempt);
        }
        if (!\in_array($error->getStatusCode(), self::RETRYABLE_STATUS, true) || str_ends_with($error->getErrorName(), 'quota_exceeded')) {
            return null;
        }
        $retryAfter = self::parseRetryAfter($error->getHeaders()['retry-after'] ?? null);
        if (null === $retryAfter) {
            return self::backoff($attempt);
        }

        return $retryAfter > self::MAX_RETRY_AFTER ? null : $retryAfter;
    }

    /**
     * Exponential backoff with jitter: 50-100% of min(cap, base * 2^attempt), in seconds.
     */
    public static function backoff(int $attempt, ?float $random = null): float
    {
        $exp = min(self::BACKOFF_CAP, self::BACKOFF_BASE * (2 ** min($attempt, 10)));
        $r = $random ?? mt_rand() / mt_getrandmax();

        return $exp / 2 + $r * $exp / 2;
    }

    /**
     * Retry-After in seconds or as an HTTP date, converted to seconds.
     */
    public static function parseRetryAfter(?string $value, ?int $now = null): ?float
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            $seconds = (float) $value;

            return $seconds >= 0 ? $seconds : null;
        }
        $time = strtotime($value);
        if (false === $time) {
            return null;
        }

        return (float) max(0, $time - ($now ?? time()));
    }

    public static function parseError(ResponseInterface $response): PidgelyException
    {
        $status = $response->getStatusCode();
        $headers = self::headers($response);
        $text = (string) $response->getBody();
        try {
            $body = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $body = null;
        }
        if (\is_array($body)) {
            if (\is_string($body['name'] ?? null) && \is_string($body['message'] ?? null)) {
                return self::forStatus($status, $body['name'], $body['message'], $headers);
            }
            $native = $body['error'] ?? null;
            if (\is_array($native) && \is_string($native['code'] ?? null)) {
                $message = \is_string($native['message'] ?? null) ? $native['message'] : $native['code'];

                return self::forStatus($status, $native['code'], $message, $headers);
            }
        }
        $name = $status >= 500 ? 'internal_server_error' : 'application_error';
        $message = trim($text);
        if ('' === $message) {
            $message = '' !== $response->getReasonPhrase() ? $response->getReasonPhrase() : 'HTTP '.$status;
        }

        return self::forStatus($status, $name, $message, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    private static function forStatus(int $status, string $name, string $message, array $headers): PidgelyException
    {
        return match (true) {
            400 === $status, 422 === $status => new ValidationException($message, $name, $status, $headers),
            401 === $status => new AuthenticationException($message, $name, $status, $headers),
            403 === $status => new PermissionDeniedException($message, $name, $status, $headers),
            404 === $status => new NotFoundException($message, $name, $status, $headers),
            409 === $status => new ConflictException($message, $name, $status, $headers),
            429 === $status => new RateLimitException($message, $name, $status, $headers),
            $status >= 500 => new InternalServerException($message, $name, $status, $headers),
            default => new PidgelyException($message, $name, $status, $headers),
        };
    }

    /**
     * @return array<string, string>
     */
    private static function headers(ResponseInterface $response): array
    {
        $out = [];
        foreach ($response->getHeaders() as $name => $values) {
            $out[strtolower((string) $name)] = implode(', ', $values);
        }

        return $out;
    }

    /**
     * Escapes a path segment (ids, emails, template aliases).
     */
    public static function seg(string $value): string
    {
        return str_replace(['%40', '%2B'], ['@', '+'], rawurlencode($value));
    }

    public static function userAgent(?string $extra): string
    {
        $ua = 'pidgely-php/'.Client::VERSION;

        return null === $extra || '' === $extra ? $ua : $ua.' '.$extra;
    }
}
