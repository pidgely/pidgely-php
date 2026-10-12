<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Contact;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;
use Pidgely\Model\Segment;

/**
 * Segments of contacts (also available as the legacy audiences).
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Segments extends Service
{
    /**
     * POST /segments
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): Segment
    {
        return new Segment($this->transport->request('POST', '/segments', [], $params, $options));
    }

    /**
     * GET /segments
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Segment>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/segments', self::page($params), $options, Segment::class);
    }

    /**
     * GET /segments/{id}
     *
     * @param RequestOptions       $options
     */
    public function get(string $id, array $options = []): Segment
    {
        return new Segment($this->transport->request('GET', '/segments/'.self::id($id), [], null, $options));
    }

    /**
     * PATCH /segments/{id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(string $id, array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', '/segments/'.self::id($id), [], $params, $options));
    }

    /**
     * DELETE /segments/{id}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $id, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/segments/'.self::id($id), [], null, $options));
    }

    /**
     * GET /segments/{id}/contacts
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Contact>
     */
    public function listContacts(string $id, array $params = [], array $options = []): Collection
    {
        return $this->collection('/segments/'.self::id($id).'/contacts', self::page($params), $options, Contact::class);
    }
}
