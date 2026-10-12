<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A segment of contacts (formerly audience).
 */
final class Segment extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $name;

    public readonly string $createdAt;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->name = Data::string($data, 'name');
        $this->createdAt = Data::string($data, 'created_at');
    }
}
