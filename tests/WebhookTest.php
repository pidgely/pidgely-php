<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Pidgely\Client;
use Pidgely\Exception\WebhookVerificationException;
use Pidgely\Webhook;

final class WebhookTest extends TestCase
{
    // Official Standard Webhooks / Svix test vector.
    private const SECRET = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';
    private const MSG_ID = 'msg_p5jXN8AQM9LWM0D4loKWxJek';
    private const TS = 1614265330;
    private const PAYLOAD = '{"test": 2432232314}';
    private const SIGNATURE = 'v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=';
    private const HEADERS = ['svix-id' => self::MSG_ID, 'svix-timestamp' => '1614265330', 'svix-signature' => self::SIGNATURE];

    public function testSignMatchesVector(): void
    {
        self::assertSame(self::SIGNATURE, Webhook::sign(self::SECRET, self::MSG_ID, self::TS, self::PAYLOAD));
        self::assertSame(self::SIGNATURE, Webhook::sign(substr(self::SECRET, 6), self::MSG_ID, '1614265330', self::PAYLOAD));
    }

    public function testVerifySvixHeaders(): void
    {
        $event = Webhook::verify(self::PAYLOAD, self::HEADERS, self::SECRET, now: self::TS);
        self::assertSame(['test' => 2432232314], $event->toArray());
    }

    public function testVerifyWebhookHeadersFromPsr7AndServer(): void
    {
        $request = new ServerRequest('POST', '/', ['Webhook-Id' => self::MSG_ID, 'Webhook-Timestamp' => '1614265330', 'Webhook-Signature' => self::SIGNATURE], self::PAYLOAD);
        self::assertSame(2432232314, Webhook::verify((string) $request->getBody(), $request, self::SECRET, now: self::TS)['test']);
        $server = ['HTTP_WEBHOOK_ID' => self::MSG_ID, 'HTTP_WEBHOOK_TIMESTAMP' => '1614265330', 'HTTP_WEBHOOK_SIGNATURE' => self::SIGNATURE];
        self::assertSame(2432232314, Webhook::verify(self::PAYLOAD, $server, self::SECRET, now: self::TS)['test']);
        $resend = ['id' => self::MSG_ID, 'timestamp' => '1614265330', 'signature' => [self::SIGNATURE]];
        self::assertSame(2432232314, Webhook::verify(self::PAYLOAD, $resend, self::SECRET, now: self::TS)['test']);
    }

    public function testMultipleSignaturesAndTolerance(): void
    {
        $headers = ['svix-signature' => 'v1,bm90IHRoaXMgb25l '.self::SIGNATURE.' v2,other'] + self::HEADERS;
        self::assertSame(2432232314, Webhook::verify(self::PAYLOAD, $headers, self::SECRET, now: self::TS)['test']);
        self::assertSame(2432232314, Webhook::verify(self::PAYLOAD, self::HEADERS, self::SECRET, now: self::TS + 299)['test']);
        self::assertSame(2432232314, Webhook::verify(self::PAYLOAD, self::HEADERS, self::SECRET, now: self::TS - 299)['test']);
    }

    public function testClientShortcut(): void
    {
        $client = new Client('pdg_x', ['http_client' => new MockHttpClient()]);
        $event = $client->webhooks->verify(self::PAYLOAD, self::HEADERS, self::SECRET, 10 ** 10);
        self::assertSame(2432232314, $event['test']);
    }

    /**
     * @return iterable<string, array{string, array<string, string>, string, int, int, string}>
     */
    public static function rejections(): iterable
    {
        $other = 'whsec_'.base64_encode('another secret value!!');
        yield 'tampered' => ['{"test": 2432232315}', self::HEADERS, self::SECRET, self::TS, 300, 'No matching signature'];
        yield 'wrong secret' => [self::PAYLOAD, self::HEADERS, $other, self::TS, 300, 'No matching signature'];
        yield 'wrong id' => [self::PAYLOAD, ['svix-id' => 'msg_other'] + self::HEADERS, self::SECRET, self::TS, 300, 'No matching signature'];
        yield 'old' => [self::PAYLOAD, self::HEADERS, self::SECRET, self::TS + 301, 300, 'too old'];
        yield 'future' => [self::PAYLOAD, self::HEADERS, self::SECRET, self::TS - 301, 300, 'too new'];
        yield 'tolerance' => [self::PAYLOAD, self::HEADERS, self::SECRET, self::TS + 11, 10, 'too old'];
        yield 'timestamp' => [self::PAYLOAD, ['svix-timestamp' => '12abc'] + self::HEADERS, self::SECRET, self::TS, 300, 'Invalid signature headers'];
        yield 'headers' => [self::PAYLOAD, ['svix-id' => self::MSG_ID], self::SECRET, self::TS, 300, 'Missing required headers'];
        yield 'no secret' => [self::PAYLOAD, self::HEADERS, '', self::TS, 300, 'Missing webhook secret'];
        yield 'empty secret' => [self::PAYLOAD, self::HEADERS, 'whsec_', self::TS, 300, 'Invalid webhook secret'];
        yield 'bad secret' => [self::PAYLOAD, self::HEADERS, 'whsec_!!!', self::TS, 300, 'Invalid webhook secret'];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('rejections')]
    public function testRejects(string $payload, array $headers, string $secret, int $now, int $tolerance, string $message): void
    {
        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage($message);
        Webhook::verify($payload, $headers, $secret, $tolerance, $now);
    }

    public function testRejectsNonJsonPayload(): void
    {
        $signature = Webhook::sign(self::SECRET, self::MSG_ID, self::TS, 'nope');
        $this->expectExceptionMessage('Payload is not valid JSON');
        Webhook::verify('nope', ['svix-signature' => $signature] + self::HEADERS, self::SECRET, now: self::TS);
    }

    public function testDefaultNowRejectsOldVector(): void
    {
        $this->expectExceptionMessage('too old');
        Webhook::verify(self::PAYLOAD, self::HEADERS, self::SECRET);
    }
}
