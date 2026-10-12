<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Identifier returned by most mutations ({"id"} or {"object", "id"}).
 */
final class ResourceId extends Model
{
    public readonly string $id;

    public readonly ?string $object;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->id = Data::string($data, 'id');
        $this->object = Data::nullableString($data, 'object');
    }
}
