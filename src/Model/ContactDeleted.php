<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Returned when a contact is removed.
 */
final class ContactDeleted extends Model
{
    public readonly string $object;

    public readonly string $contact;

    public readonly string $id;

    public readonly bool $deleted;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->contact = Data::string($data, 'contact');
        $this->id = Data::string($data, 'id');
        $this->deleted = Data::bool($data, 'deleted');
    }
}
