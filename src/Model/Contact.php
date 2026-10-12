<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A contact. properties maps names to {value, type}.
 */
final class Contact extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $email;

    public readonly ?string $firstName;

    public readonly ?string $lastName;

    public readonly string $createdAt;

    public readonly bool $unsubscribed;

    /** @var array<string, mixed> */
    public readonly array $properties;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->email = Data::string($data, 'email');
        $this->firstName = Data::nullableString($data, 'first_name');
        $this->lastName = Data::nullableString($data, 'last_name');
        $this->createdAt = Data::string($data, 'created_at');
        $this->unsubscribed = Data::bool($data, 'unsubscribed');
        $this->properties = Data::map($data, 'properties');
    }
}
