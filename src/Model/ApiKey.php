<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * An API key (without its token).
 */
final class ApiKey extends Model
{
    public readonly string $id;

    public readonly string $name;

    public readonly string $permission;

    public readonly string $createdAt;

    public readonly ?string $lastUsedAt;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->id = Data::string($data, 'id');
        $this->name = Data::string($data, 'name');
        $this->permission = Data::string($data, 'permission');
        $this->createdAt = Data::string($data, 'created_at');
        $this->lastUsedAt = Data::nullableString($data, 'last_used_at');
    }
}
