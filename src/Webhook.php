<?php

declare(strict_types=1);

namespace Pidgely;

use Pidgely\Exception\WebhookVerificationException;
use Pidgely\Internal\Data;
use Pidgely\Model\WebhookEvent;
use Psr\Http\Message\MessageInterface;

/**
 * Verifies webhook deliveries (Standard Webhooks / Svix scheme).
 *
 * Pidgely signs every delivery with HMAC-SHA256 and sends the values under both the
 * webhook-* and svix-* header names.
 */
final class Webhook
{
    /** Maximum accepted clock difference, in seconds. */
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Verifies a delivery and returns the parsed event.
     *
     * @param string                                                $payload   the raw request body, exactly as received
     * @param MessageInterface|array<string, string|list<string>> $headers   a PSR-7 request, getallheaders(), $_SERVER, or ['id', 'timestamp', 'signature']
     * @param string                                                $secret    the endpoint's signing secret (whsec_...)
     * @param int                                                   $tolerance maximum clock difference in seconds
     * @param int|null                                              $now       override the current Unix time (tests)
     *
     * @throws WebhookVerificationException when the headers are missing, the timestamp is outside
     *                                      the tolerance, or no signature matches
     */
    public static function verify(
        string $payload,
        MessageInterface|array $headers,
        #[\SensitiveParameter]
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): WebhookEvent {
        self::key($secret);
        $normalized = self::normalize($headers);
        $id = self::header($normalized, 'id');
        $timestamp = self::header($normalized, 'timestamp');
        $signature = self::header($normalized, 'signature');
        if (null === $id || null === $timestamp || null === $signature) {
            throw new WebhookVerificationException('Missing required headers');
        }
        if (!ctype_digit($timestamp)) {
            throw new WebhookVerificationException('Invalid signature headers');
        }
        $ts = (int) $timestamp;
        $current = $now ?? time();
        if ($current - $ts > $tolerance) {
            throw new WebhookVerificationException('Message timestamp too old');
        }
        if ($ts - $current > $tolerance) {
            throw new WebhookVerificationException('Message timestamp too new');
        }

        $expected = self::sign($secret, $id, $timestamp, $payload);
        $matched = false;
        foreach (preg_split('/\s+/', trim($signature)) ?: [] as $candidate) {
            // Compare every candidate so timing does not reveal which one matched.
            if (hash_equals($expected, $candidate)) {
                $matched = true;
            }
        }
        if (!$matched) {
            throw new WebhookVerificationException('No matching signature found');
        }
        try {
            $data = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new WebhookVerificationException('Payload is not valid JSON');
        }

        return new WebhookEvent(\is_array($data) ? Data::stringKeys($data) : []);
    }

    /**
     * Computes the "v1,<base64>" signature of a delivery.
     */
    public static function sign(#[\SensitiveParameter] string $secret, string $id, int|string $timestamp, string $payload): string
    {
        $mac = hash_hmac('sha256', $id.'.'.$timestamp.'.'.$payload, self::key($secret), true);

        return 'v1,'.base64_encode($mac);
    }

    private static function key(#[\SensitiveParameter] string $secret): string
    {
        if ('' === $secret) {
            throw new WebhookVerificationException('Missing webhook secret');
        }
        $encoded = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = 1 === preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $encoded) ? base64_decode($encoded, true) : false;
        if (false === $key || '' === $key) {
            throw new WebhookVerificationException('Invalid webhook secret');
        }

        return $key;
    }

    /**
     * @param MessageInterface|array<string, string|list<string>> $headers
     *
     * @return array<string, string>
     */
    private static function normalize(MessageInterface|array $headers): array
    {
        $raw = $headers instanceof MessageInterface ? $headers->getHeaders() : $headers;
        $out = [];
        foreach ($raw as $name => $value) {
            $key = str_replace('_', '-', strtolower((string) $name));
            if (str_starts_with($key, 'http-')) {
                $key = substr($key, 5); // $_SERVER['HTTP_WEBHOOK_ID']
            }
            $out[$key] = \is_array($value) ? implode(' ', $value) : (string) $value;
        }

        return $out;
    }

    /**
     * @param array<string, string> $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        foreach (['webhook-'.$name, 'svix-'.$name, $name] as $key) {
            if (isset($headers[$key]) && '' !== $headers[$key]) {
                return $headers[$key];
            }
        }

        return null;
    }
}
