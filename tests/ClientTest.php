<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pidgely\Client;
use Pidgely\Exception\ApiConnectionException;
use Pidgely\Exception\ApiTimeoutException;
use Pidgely\Exception\AuthenticationException;
use Pidgely\Exception\ConflictException;
use Pidgely\Exception\InternalServerException;
use Pidgely\Exception\MissingApiKeyException;
use Pidgely\Exception\NotFoundException;
use Pidgely\Exception\PermissionDeniedException;
use Pidgely\Exception\PidgelyException;
use Pidgely\Exception\RateLimitException;
use Pidgely\Exception\ValidationException;
use Pidgely\Internal\Transport;
use PHPUnit\Framework\Attributes\DataProvider;

final class ClientTest extends TestCase
{
    private static function unavailable(): Response
    {
        return self::json(503, ['statusCode' => 503, 'name' => 'internal_server_error', 'message' => 'busy']);
    }

    private static function rateLimited(): Response
    {
        return self::json(429, ['statusCode' => 429, 'name' => 'rate_limit_exceeded', 'message' => 'slow down']);
    }

    public function testFactoryAndEnvironment(): void
    {
        putenv('PIDGELY_API_KEY=pdg_env');
        putenv('PIDGELY_BASE_URL=https://env.test/');
        $client = \Pidgely::client(getenv('PIDGELY_API_KEY'), ['http_client' => new MockHttpClient()]);
        self::assertSame('https://env.test', $client->baseUrl());
        self::assertSame('https://env.test', \Pidgely::client(null, ['http_client' => new MockHttpClient()])->baseUrl());
        self::assertSame('https://other.test', (new Client('pdg_x', ['base_url' => 'https://other.test/']))->baseUrl());
        putenv('PIDGELY_BASE_URL');
        self::assertSame(Client::DEFAULT_BASE_URL, (new Client('pdg_x'))->baseUrl());
        self::assertSame($client->segments, $client->audiences);
    }

    public function testMissingApiKey(): void
    {
        try {
            \Pidgely::client(getenv('PIDGELY_API_KEY'));
            self::fail('Expected MissingApiKeyException');
        } catch (MissingApiKeyException $e) {
            self::assertSame('missing_api_key', $e->getErrorName());
        }
    }

    public function testUserAgentAndHeaders(): void
    {
        $client = $this->client([], ['user_agent' => 'my-app/1.2', 'headers' => ['X-Team' => 'growth']]);
        $client->domains->list([], ['headers' => ['X-Trace' => 'abc', 'Authorization' => 'Bearer other']]);
        $request = $this->http->last();
        self::assertSame('pidgely-php/'.Client::VERSION.' my-app/1.2', $request->getHeaderLine('User-Agent'));
        self::assertSame('growth', $request->getHeaderLine('X-Team'));
        self::assertSame('abc', $request->getHeaderLine('X-Trace'));
        self::assertSame('Bearer '.self::API_KEY, $request->getHeaderLine('Authorization'));
    }

    public function testApiKeyNeverLeaks(): void
    {
        $client = $this->client([
            self::json(401, ['statusCode' => 401, 'name' => 'invalid_api_key', 'message' => 'API key is invalid']),
            new NetworkException('connection reset while talking with '.self::API_KEY),
        ], ['max_retries' => 0]);
        $outputs = [print_r($client, true), var_export($client, true), (string) json_encode($client), print_r($client->emails, true)];
        ob_start();
        var_dump($client);
        var_dump($client->transport());
        $outputs[] = (string) ob_get_clean();
        try {
            $client->emails->get('e1');
        } catch (AuthenticationException $e) {
            $outputs[] = $e->getMessage();
            $outputs[] = (string) $e;
            $outputs[] = print_r($e->getHeaders(), true);
        }
        try {
            $client->emails->get('e2');
        } catch (ApiConnectionException $e) {
            $outputs[] = $e->getMessage();
            self::assertStringContainsString('[REDACTED]', $e->getMessage());
        }
        if (\PHP_VERSION_ID >= 80200) {
            // #[\SensitiveParameter] keeps the key out of stack traces.
            ini_set('zend.exception_ignore_args', '0');
            try {
                /** @phpstan-ignore argument.type */
                new Client(self::API_KEY, ['http_client' => new MockHttpClient(), 'headers' => 'not-an-array']);
                self::fail('Expected an exception');
            } catch (\TypeError $e) {
                $outputs[] = $e->getTraceAsString();
                self::assertStringContainsString('SensitiveParameterValue', $e->getTraceAsString());
            }
        }
        foreach ($outputs as $output) {
            self::assertStringNotContainsString(self::API_KEY, $output);
            self::assertStringNotContainsString('0123456789abcdef', $output);
        }
    }

    public function testSerializationIsRefused(): void
    {
        $this->expectException(\LogicException::class);
        serialize($this->client());
    }

    /**
     * @return iterable<string, array{Response, class-string<PidgelyException>, int, string, string}>
     */
    public static function errors(): iterable
    {
        yield '422' => [self::json(422, ['statusCode' => 422, 'name' => 'validation_error', 'message' => 'bad to']), ValidationException::class, 422, 'validation_error', 'bad to'];
        yield '400' => [self::json(400, ['name' => 'validation_error', 'message' => 'bad']), ValidationException::class, 400, 'validation_error', 'bad'];
        yield '404' => [self::json(404, ['name' => 'not_found', 'message' => 'nope']), NotFoundException::class, 404, 'not_found', 'nope'];
        yield 'native 403' => [self::json(403, ['error' => ['code' => 'forbidden', 'message' => 'no']]), PermissionDeniedException::class, 403, 'forbidden', 'no'];
        yield 'native without message' => [self::json(403, ['error' => ['code' => 'forbidden']]), PermissionDeniedException::class, 403, 'forbidden', 'forbidden'];
        yield '409' => [self::json(409, ['name' => 'invalid_idempotent_request', 'message' => 'x']), ConflictException::class, 409, 'invalid_idempotent_request', 'x'];
        yield 'quota' => [self::json(429, ['name' => 'daily_quota_exceeded', 'message' => 'q']), RateLimitException::class, 429, 'daily_quota_exceeded', 'q'];
        yield 'html 5xx' => [new Response(501, [], '<html>Bad</html>'), InternalServerException::class, 501, 'internal_server_error', '<html>Bad</html>'];
        yield 'empty' => [new Response(418, [], ''), PidgelyException::class, 418, 'application_error', "I'm a teapot"];
        yield 'json list' => [self::json(400, [1]), ValidationException::class, 400, 'application_error', '[1]'];
    }

    /**
     * @param class-string<PidgelyException> $class
     */
    #[DataProvider('errors')]
    public function testApiErrors(Response $response, string $class, int $status, string $name, string $message): void
    {
        $client = $this->client([$response]);
        try {
            $client->domains->get('d');
            self::fail('Expected an exception');
        } catch (PidgelyException $e) {
            self::assertInstanceOf($class, $e);
            self::assertSame($status, $e->getStatusCode());
            self::assertSame($status, $e->getCode());
            self::assertSame($name, $e->getErrorName());
            self::assertSame($message, $e->getMessage());
        }
    }

    public function testInvalidSuccessJson(): void
    {
        $client = $this->client([new Response(200, [], '{not json')]);
        $this->expectExceptionMessage('Unable to parse the API response');
        $client->emails->get('x');
    }

    public function testEmptySuccessBody(): void
    {
        $client = $this->client([new Response(200, [], '')]);
        self::assertSame([], $client->domains->remove('d')->toArray());
    }

    public function testRetriesGetOn5xx(): void
    {
        $client = $this->client([self::unavailable(), self::unavailable(), self::json(200, ['id' => 'e1'])]);
        self::assertSame('e1', $client->emails->get('e1')->id);
        self::assertCount(3, $this->http->requests);
        self::assertCount(2, $this->sleeps);
        self::assertGreaterThanOrEqual(0.25, $this->sleeps[0]);
        self::assertLessThanOrEqual(0.5, $this->sleeps[0]);
        self::assertGreaterThanOrEqual(0.5, $this->sleeps[1]);
        self::assertLessThanOrEqual(1.0, $this->sleeps[1]);
    }

    public function testStopsAfterMaxRetries(): void
    {
        $client = $this->client([self::unavailable(), self::unavailable(), self::unavailable(), self::unavailable()]);
        try {
            $client->domains->list();
            self::fail('Expected an exception');
        } catch (InternalServerException) {
        }
        self::assertCount(3, $this->http->requests);
    }

    public function testRetryOptions(): void
    {
        $client = $this->client(array_fill(0, 6, self::unavailable()), ['max_retries' => 0]);
        try {
            $client->domains->list();
        } catch (InternalServerException) {
        }
        self::assertCount(1, $this->http->requests);
        try {
            $client->domains->list([], ['max_retries' => 4]);
        } catch (InternalServerException) {
        }
        self::assertCount(6, $this->http->requests);
    }

    /**
     * @return iterable<string, array{Response|\Throwable}>
     */
    public static function failures(): iterable
    {
        yield '503' => [self::unavailable()];
        yield '429' => [self::rateLimited()];
        yield '500' => [new Response(500, [], 'oops')];
        yield 'network' => [new NetworkException('reset')];
    }

    #[DataProvider('failures')]
    public function testNeverRetriesPostWithoutIdempotencyKey(Response|\Throwable $failure): void
    {
        $client = $this->client([$failure, self::json(200, ['id' => 'dup'])]);
        try {
            $client->emails->send(['to' => 'a@b.c']);
            self::fail('Expected an exception');
        } catch (PidgelyException) {
        }
        self::assertCount(1, $this->http->requests);
        self::assertSame([], $this->sleeps);
    }

    public function testRetriesPostWithIdempotencyKey(): void
    {
        $client = $this->client([self::unavailable(), self::rateLimited(), new NetworkException('x'), self::json(200, ['id' => 'e1'])], ['max_retries' => 3]);
        self::assertSame('e1', $client->emails->send(['to' => 'a@b.c'], ['idempotency_key' => 'k1'])->id);
        self::assertCount(4, $this->http->requests);
        foreach ($this->http->requests as $request) {
            self::assertSame('k1', $request->getHeaderLine('Idempotency-Key'));
            self::assertSame('{"to":"a@b.c"}', (string) $request->getBody());
        }
    }

    public function testRetriesPatchAndDelete(): void
    {
        $client = $this->client([self::unavailable(), self::json(200, []), self::unavailable(), self::json(200, [])]);
        $client->domains->update(['id' => 'd', 'tls' => 'enforced']);
        $client->domains->remove('d');
        self::assertCount(4, $this->http->requests);
    }

    public function testHonoursRetryAfter(): void
    {
        $client = $this->client([self::json(429, ['name' => 'rate_limit_exceeded', 'message' => 'x'], ['Retry-After' => '3']), self::json(200, [])]);
        $client->domains->list();
        self::assertSame([3.0], $this->sleeps);
    }

    public function testRetryAfterTooLong(): void
    {
        $client = $this->client([self::json(429, ['name' => 'rate_limit_exceeded', 'message' => 'x'], ['Retry-After' => '120'])]);
        try {
            $client->domains->list();
            self::fail('Expected an exception');
        } catch (RateLimitException $e) {
            self::assertSame('120', $e->getHeaders()['retry-after']);
        }
        self::assertCount(1, $this->http->requests);
    }

    public function testQuotaErrorsNotRetried(): void
    {
        $client = $this->client([self::json(429, ['name' => 'monthly_quota_exceeded', 'message' => 'q'])]);
        try {
            $client->domains->list();
        } catch (RateLimitException) {
        }
        self::assertCount(1, $this->http->requests);
    }

    public function testNonRetryableStatus(): void
    {
        $client = $this->client([self::json(422, ['name' => 'validation_error', 'message' => 'x'])]);
        try {
            $client->domains->list();
        } catch (ValidationException) {
        }
        self::assertCount(1, $this->http->requests);
    }

    public function testRetriesGetOnNetworkError(): void
    {
        $client = $this->client([new NetworkException('down'), new NetworkException('down'), new NetworkException('down')]);
        try {
            $client->domains->list();
            self::fail('Expected an exception');
        } catch (ApiConnectionException $e) {
            self::assertSame('Unable to reach the Pidgely API: down', $e->getMessage());
            self::assertNull($e->getStatusCode());
        }
        self::assertCount(3, $this->http->requests);
        self::assertCount(2, $this->sleeps);
    }

    public function testGuzzlePerRequestTimeout(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], '{"id":"e1"}'), new Response(200, [], '{}'), new \GuzzleHttp\Exception\ConnectException('cURL error 28: Operation timed out', new Request('GET', 'x'))]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new Client(self::API_KEY, ['base_url' => self::BASE, 'http_client' => new \GuzzleHttp\Client(['handler' => $stack]), 'timeout' => 12, 'max_retries' => 0]);
        self::assertSame('e1', $client->emails->get('e1')->id);
        $client->emails->get('e2', ['timeout' => 2.5]);
        /** @var list<array{options: array<string, mixed>}> $history */
        self::assertSame(12.0, $history[0]['options']['timeout']);
        self::assertSame(2.5, $history[1]['options']['timeout']);
        try {
            $client->emails->get('e3', ['timeout' => 1.5]);
            self::fail('Expected a timeout');
        } catch (ApiTimeoutException $e) {
            self::assertSame('Request timed out after 1.5s', $e->getMessage());
        }
    }

    public function testParseRetryAfter(): void
    {
        $now = 1_760_270_400;
        self::assertNull(Transport::parseRetryAfter(null));
        self::assertNull(Transport::parseRetryAfter(''));
        self::assertSame(2.0, Transport::parseRetryAfter('2'));
        self::assertSame(1.5, Transport::parseRetryAfter('1.5'));
        self::assertNull(Transport::parseRetryAfter('-1'));
        self::assertNull(Transport::parseRetryAfter('soon'));
        self::assertSame(5.0, Transport::parseRetryAfter(gmdate('D, d M Y H:i:s', $now + 5).' GMT', $now));
        self::assertSame(0.0, Transport::parseRetryAfter(gmdate('D, d M Y H:i:s', $now - 3600).' GMT', $now));
    }

    public function testBackoffBounds(): void
    {
        self::assertSame(0.25, Transport::backoff(0, 0.0));
        self::assertSame(0.5, Transport::backoff(0, 1.0));
        self::assertSame(8.0, Transport::backoff(10, 1.0));
        for ($attempt = 0; $attempt < 8; ++$attempt) {
            $cap = min(8.0, 0.5 * 2 ** $attempt);
            $value = Transport::backoff($attempt);
            self::assertGreaterThanOrEqual($cap / 2, $value);
            self::assertLessThanOrEqual($cap, $value);
        }
    }

    public function testSegAndQueryBooleans(): void
    {
        self::assertSame('a%20b%2Fc', Transport::seg('a b/c'));
        self::assertSame('ana+x@example.com', Transport::seg('ana+x@example.com'));
        $client = $this->client();
        $client->transport()->request('GET', '/contacts', ['flag' => true, 'off' => false, 'skip' => null]);
        self::assertSame('flag=true&off=false', $this->http->last()->getUri()->getQuery());
    }
}
