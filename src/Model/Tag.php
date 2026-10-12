<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * An email tag.
 */
final class Tag extends Model
{
    public readonly string $name;

    public readonly string $value;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->name = Data::string($data, 'name');
        $this->value = Data::string($data, 'value');
    }
}
