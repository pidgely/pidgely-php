<?php

declare(strict_types=1);

namespace Pidgely\Model;

/**
 * Base class of the typed response objects.
 *
 * Documented fields are typed, read-only camelCase properties ($email->createdAt).
 * The raw API response stays available with array access ($email['created_at']),
 * toArray() and json_encode().
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class Model implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array<string, mixed> $attributes the raw API response
     */
    public function __construct(protected readonly array $attributes = []) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->attributes);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \LogicException(static::class.' is read-only.');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \LogicException(static::class.' is read-only.');
    }
}
