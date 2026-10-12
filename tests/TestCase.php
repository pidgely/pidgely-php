<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use GuzzleHttp\Psr7\Response;
use Pidgely\Client;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public const API_KEY = 'pdg_test_0123456789abcdef';
    public const BASE = 'https://api.pidgely.test';

    /** @var list<float> */
    protected array $sleeps = [];

    protected MockHttpClient $http;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['PIDGELY_API_KEY', 'PIDGELY_BASE_URL'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
        $this->sleeps = [];
    }

    /**
     * @param list<Response|\Throwable> $queue
     * @param array<string, mixed>       $options
     */
    protected function client(array $queue = [], array $options = []): Client
    {
        $this->http = new MockHttpClient($queue);
        /** @phpstan-ignore argument.type */
        $client = new Client(self::API_KEY, ['base_url' => self::BASE, 'http_client' => $this->http, ...$options]);
        $client->transport()->setSleep(function (float $seconds): void {
            $this->sleeps[] = $seconds;
        });

        return $client;
    }

    /**
     * @param array<array-key, mixed>|string $body
     * @param array<string, string>          $headers
     */
    protected static function json(int $status, array|string $body, array $headers = []): Response
    {
        $text = \is_string($body) ? $body : json_encode($body, \JSON_THROW_ON_ERROR);

        return new Response($status, ['Content-Type' => 'application/json', ...$headers], $text);
    }
}
