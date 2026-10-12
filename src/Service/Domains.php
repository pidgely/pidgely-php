<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\Domain;
use Pidgely\Model\ResourceId;

/**
 * Sending domains and their DNS records.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Domains extends Service
{
    /**
     * POST /domains: returns the DNS records to publish.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): Domain
    {
        return new Domain($this->transport->request('POST', '/domains', [], $params, $options));
    }

    /**
     * GET /domains
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Domain>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/domains', self::page($params), $options, Domain::class);
    }

    /**
     * GET /domains/{id}
     *
     * @param RequestOptions       $options
     */
    public function get(string $id, array $options = []): Domain
    {
        return new Domain($this->transport->request('GET', '/domains/'.self::id($id), [], null, $options));
    }

    /**
     * PATCH /domains/{id} (['id' => ..., ...]).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(array $params, array $options = []): ResourceId
    {
        $id = Transport::seg(self::require($params, 'id'));

        return new ResourceId($this->transport->request('PATCH', '/domains/'.$id, [], self::without($params, 'id'), $options));
    }

    /**
     * DELETE /domains/{id}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $id, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/domains/'.self::id($id), [], null, $options));
    }

    /**
     * POST /domains/{id}/verify: check the DNS records now.
     *
     * @param RequestOptions       $options
     */
    public function verify(string $id, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/domains/'.self::id($id).'/verify', [], null, $options));
    }
}
