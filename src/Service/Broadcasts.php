<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Broadcast;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;

/**
 * Marketing broadcasts.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Broadcasts extends Service
{
    /**
     * POST /broadcasts: creates a draft, or sends it with send => true.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/broadcasts', [], $params, $options));
    }

    /**
     * GET /broadcasts
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Broadcast>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/broadcasts', self::page($params), $options, Broadcast::class);
    }

    /**
     * GET /broadcasts/{id}
     *
     * @param RequestOptions       $options
     */
    public function get(string $id, array $options = []): Broadcast
    {
        return new Broadcast($this->transport->request('GET', '/broadcasts/'.self::id($id), [], null, $options));
    }

    /**
     * PATCH /broadcasts/{id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(string $id, array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', '/broadcasts/'.self::id($id), [], $params, $options));
    }

    /**
     * DELETE /broadcasts/{id}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $id, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/broadcasts/'.self::id($id), [], null, $options));
    }

    /**
     * POST /broadcasts/{id}/send: now, or at scheduled_at.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function send(string $id, array $params = [], array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/broadcasts/'.self::id($id).'/send', [], (object) $params, $options));
    }

    /**
     * POST /broadcasts/{id}/cancel: cancel a scheduled or queued broadcast.
     *
     * @param RequestOptions       $options
     */
    public function cancel(string $id, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/broadcasts/'.self::id($id).'/cancel', [], null, $options));
    }

    /**
     * POST /broadcasts/{id}/duplicate: copy as a new draft.
     *
     * @param RequestOptions       $options
     */
    public function duplicate(string $id, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/broadcasts/'.self::id($id).'/duplicate', [], null, $options));
    }
}
