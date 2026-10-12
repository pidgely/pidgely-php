<?php

declare(strict_types=1);

use Pidgely\Client;

/**
 * Entry point of the Pidgely PHP SDK.
 *
 *     $pidgely = Pidgely::client(getenv('PIDGELY_API_KEY'));
 *     $pidgely->emails->send([...]);
 */
final class Pidgely
{
    /**
     * Creates a client. Without an API key, PIDGELY_API_KEY is used.
     *
     * @param array{base_url?: string, timeout?: float|int, max_retries?: int, user_agent?: string, headers?: array<string, string>, http_client?: \Psr\Http\Client\ClientInterface, request_factory?: \Psr\Http\Message\RequestFactoryInterface, stream_factory?: \Psr\Http\Message\StreamFactoryInterface} $options
     */
    public static function client(#[\SensitiveParameter] string|false|null $apiKey = null, array $options = []): Client
    {
        return new Client($apiKey === false ? null : $apiKey, $options);
    }
}
