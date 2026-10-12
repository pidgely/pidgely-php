<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;
use Pidgely\Model\Webhook;
use Pidgely\Model\WebhookEvent;
use Pidgely\Model\WebhookSecret;
use Pidgely\Webhook as WebhookVerifier;
use Psr\Http\Message\MessageInterface;

/**
 * Webhook endpoints.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Webhooks extends Service
{
    /**
     * POST /webhooks: returns the signing_secret (whsec_...).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): WebhookSecret
    {
        return new WebhookSecret($this->transport->request('POST', '/webhooks', [], $params, $options));
    }

    /**
     * GET /webhooks
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Webhook>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/webhooks', self::page($params), $options, Webhook::class);
    }

    /**
     * GET /webhooks/{id}
     *
     * @param RequestOptions       $options
     */
    public function get(string $id, array $options = []): Webhook
    {
        return new Webhook($this->transport->request('GET', '/webhooks/'.self::id($id), [], null, $options));
    }

    /**
     * PATCH /webhooks/{id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(string $id, array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', '/webhooks/'.self::id($id), [], $params, $options));
    }

    /**
     * DELETE /webhooks/{id}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $id, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/webhooks/'.self::id($id), [], null, $options));
    }

    /**
     * POST /webhooks/{id}/signing-secret/rotate
     *
     * @param RequestOptions       $options
     */
    public function rotateSigningSecret(string $id, array $options = []): WebhookSecret
    {
        return new WebhookSecret($this->transport->request('POST', '/webhooks/'.self::id($id).'/signing-secret/rotate', [], null, $options));
    }

    /**
     * Verifies a delivery and returns the parsed event. See Webhook::verify(); no API key is needed.
     *
     * @param MessageInterface|array<string, string|list<string>> $headers
     */
    public function verify(string $payload, MessageInterface|array $headers, #[\SensitiveParameter] string $secret, int $tolerance = WebhookVerifier::DEFAULT_TOLERANCE): WebhookEvent
    {
        return WebhookVerifier::verify($payload, $headers, $secret, $tolerance);
    }
}
