<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Returned when a contact leaves a segment.
 */
final class ContactSegmentRemoved extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $segmentId;

    public readonly bool $deleted;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->segmentId = Data::string($data, 'segment_id');
        $this->deleted = Data::bool($data, 'deleted');
    }
}
