<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\ContactSegmentRemoved;
use Pidgely\Model\ResourceId;
use Pidgely\Model\Segment;

/**
 * Segment memberships of a contact, selected by email, id or contact_id.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class ContactSegments extends Service
{
    /**
     * GET /contacts/{id_or_email}/segments
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Segment>
     */
    public function list(array $params, array $options = []): Collection
    {
        $key = Transport::seg(self::require($params, 'contact_id', 'id', 'email'));

        return $this->collection('/contacts/'.$key.'/segments', self::page($params), $options, Segment::class);
    }

    /**
     * POST /contacts/{id_or_email}/segments/{segment_id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function add(array $params, array $options = []): ResourceId
    {
        $key = Transport::seg(self::require($params, 'contact_id', 'id', 'email'));
        $segment = Transport::seg(self::require($params, 'segment_id'));

        return new ResourceId($this->transport->request('POST', '/contacts/'.$key.'/segments/'.$segment, [], null, $options));
    }

    /**
     * DELETE /contacts/{id_or_email}/segments/{segment_id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function remove(array $params, array $options = []): ContactSegmentRemoved
    {
        $key = Transport::seg(self::require($params, 'contact_id', 'id', 'email'));
        $segment = Transport::seg(self::require($params, 'segment_id'));

        return new ContactSegmentRemoved($this->transport->request('DELETE', '/contacts/'.$key.'/segments/'.$segment, [], null, $options));
    }
}
