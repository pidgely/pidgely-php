<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Result of a contact batch upsert: saved rows in data, invalid rows in errors (list of {index, message}).
 */
final class ContactBatchResult extends Model
{
    public readonly string $object;

    /** @var list<ContactBatchItem> */
    public readonly array $data;

    /** @var list<array<string, mixed>> */
    public readonly array $errors;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->data = Data::models($data, 'data', ContactBatchItem::class);
        $this->errors = Data::objects($data, 'errors');
    }
}
