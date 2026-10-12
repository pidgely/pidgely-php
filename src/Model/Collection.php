<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A page of results: {"object": "list", "has_more", "data"}. Iterable and countable.
 *
 * @template T of Model
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Collection extends Model implements \IteratorAggregate, \Countable
{
    public readonly string $object;

    public readonly bool $hasMore;

    /** @var list<T> */
    public readonly array $data;

    /**
     * @param array<string, mixed> $response
     * @param class-string<T>      $class
     */
    public function __construct(array $response, string $class)
    {
        parent::__construct($response);
        $this->object = Data::string($response, 'object');
        $this->hasMore = Data::bool($response, 'has_more');
        $this->data = Data::models($response, 'data', $class);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return \count($this->data);
    }
}
