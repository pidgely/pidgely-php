<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A declared template variable.
 */
final class TemplateVariable extends Model
{
    public readonly string $id;

    public readonly string $key;

    public readonly string $type;

    public readonly mixed $fallbackValue;

    public readonly string $createdAt;

    public readonly string $updatedAt;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->id = Data::string($data, 'id');
        $this->key = Data::string($data, 'key');
        $this->type = Data::string($data, 'type');
        $this->fallbackValue = $data['fallback_value'] ?? null;
        $this->createdAt = Data::string($data, 'created_at');
        $this->updatedAt = Data::string($data, 'updated_at');
    }
}
