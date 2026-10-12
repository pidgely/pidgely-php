<?php

declare(strict_types=1);

namespace Pidgely;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Pidgely\Exception\MissingApiKeyException;
use Pidgely\Internal\Transport;
use Pidgely\Service\ApiKeys;
use Pidgely\Service\Batch;
use Pidgely\Service\Broadcasts;
use Pidgely\Service\Contacts;
use Pidgely\Service\Domains;
use Pidgely\Service\Emails;
use Pidgely\Service\Segments;
use Pidgely\Service\Suppressions;
use Pidgely\Service\Templates;
use Pidgely\Service\Webhooks;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Pidgely API client. Create it with Pidgely::client($apiKey) or new Client($apiKey).
 *
 * The API key never appears in var_dump(), print_r() or exceptions.
 */
final class Client
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://api.pidgely.com';

    public readonly Emails $emails;
    public readonly Batch $batch;
    public readonly Domains $domains;
    public readonly ApiKeys $apiKeys;
    public readonly Contacts $contacts;
    public readonly Segments $segments;
    /** @deprecated Alias of $segments, kept for Resend compatibility. */
    public readonly Segments $audiences;
    public readonly Broadcasts $broadcasts;
    public readonly Templates $templates;
    public readonly Webhooks $webhooks;
    public readonly Suppressions $suppressions;

    private readonly Transport $transport;

    /**
     * @param string|null $apiKey  pdg_... key; defaults to the PIDGELY_API_KEY environment variable
     * @param array{base_url?: string, timeout?: float|int, max_retries?: int, user_agent?: string, headers?: array<string, string>, http_client?: ClientInterface, request_factory?: RequestFactoryInterface, stream_factory?: StreamFactoryInterface} $options
     *                             base_url (default PIDGELY_BASE_URL or https://api.pidgely.com), timeout in seconds per
     *                             attempt (default 30), max_retries (default 2), user_agent (appended to pidgely-php/<version>),
     *                             headers sent with every request, and a PSR-18 http_client / PSR-17 factories (discovered
     *                             when omitted; Guzzle is used when installed)
     *
     * @throws MissingApiKeyException
     */
    public function __construct(#[\SensitiveParameter] ?string $apiKey = null, array $options = [])
    {
        $apiKey = $apiKey ?? '';
        if ('' === $apiKey) {
            $apiKey = self::env('PIDGELY_API_KEY');
        }
        if ('' === $apiKey) {
            throw new MissingApiKeyException("Missing API key. Pass it to Pidgely::client('pdg_...') or set the PIDGELY_API_KEY environment variable.", 'missing_api_key');
        }
        $baseUrl = $options['base_url'] ?? '';
        if ('' === $baseUrl) {
            $baseUrl = self::env('PIDGELY_BASE_URL');
        }
        if ('' === $baseUrl) {
            $baseUrl = self::DEFAULT_BASE_URL;
        }
        $timeout = (float) ($options['timeout'] ?? 30);
        $http = $options['http_client'] ?? self::defaultHttpClient($timeout);

        $this->transport = new Transport(
            $apiKey,
            rtrim($baseUrl, '/'),
            $timeout,
            max(0, $options['max_retries'] ?? 2),
            Transport::userAgent($options['user_agent'] ?? null),
            $options['headers'] ?? [],
            $http,
            $options['request_factory'] ?? Psr17FactoryDiscovery::findRequestFactory(),
            $options['stream_factory'] ?? Psr17FactoryDiscovery::findStreamFactory(),
        );

        $this->emails = new Emails($this->transport);
        $this->batch = new Batch($this->transport);
        $this->domains = new Domains($this->transport);
        $this->apiKeys = new ApiKeys($this->transport);
        $this->contacts = new Contacts($this->transport);
        $this->segments = new Segments($this->transport);
        $this->audiences = $this->segments;
        $this->broadcasts = new Broadcasts($this->transport);
        $this->templates = new Templates($this->transport);
        $this->webhooks = new Webhooks($this->transport);
        $this->suppressions = new Suppressions($this->transport);
    }

    public function baseUrl(): string
    {
        return $this->transport->baseUrl;
    }

    /**
     * The HTTP layer (exposed for tests and advanced use).
     *
     * @internal
     */
    public function transport(): Transport
    {
        return $this->transport;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->transport->baseUrl, 'apiKey' => '[REDACTED]'];
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new \LogicException('A Pidgely client cannot be serialized.');
    }

    private static function env(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return \is_string($value) ? $value : '';
    }

    private static function defaultHttpClient(float $timeout): ClientInterface
    {
        if (class_exists(\GuzzleHttp\Client::class)) {
            return new \GuzzleHttp\Client(['timeout' => $timeout, 'connect_timeout' => min($timeout > 0 ? $timeout : 10.0, 10.0), 'http_errors' => false]);
        }

        return Psr18ClientDiscovery::find();
    }
}
