<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A contact saved by a batch upsert; index is its position in the request.
 */
final class ContactBatchItem extends Model
{
    public readonly int $index;

    public readonly string $id;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->index = Data::int($data, 'index');
        $this->id = Data::string($data, 'id');
    }
}
