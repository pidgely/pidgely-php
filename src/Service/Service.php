<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Exception\PidgelyException;
use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Model;

/**
 * Base class of the API resources.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
abstract class Service
{
    public function __construct(protected readonly Transport $transport) {}

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $params
     */
    protected static function require(array $params, string ...$keys): string
    {
        foreach ($keys as $key) {
            $value = $params[$key] ?? null;
            if (\is_scalar($value) && '' !== (string) $value) {
                return (string) $value;
            }
        }
        $names = implode(' or ', array_map(static fn(string $k): string => '`'.$k.'`', $keys));

        throw new PidgelyException('Missing '.$names.' field.', 'missing_required_field');
    }

    protected static function id(string $value, string $name = 'id'): string
    {
        if ('' === $value) {
            throw new PidgelyException('Missing `'.$name.'`.', 'missing_required_field');
        }

        return Transport::seg($value);
    }

    /**
     * Pagination (and extra) query parameters picked from a params array.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, scalar|null>
     */
    protected static function page(array $params, string ...$extra): array
    {
        $query = [];
        foreach (['limit', 'after', 'before', ...$extra] as $key) {
            $value = $params[$key] ?? null;
            if (\is_scalar($value)) {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected static function without(array $params, string ...$keys): array
    {
        return array_diff_key($params, array_flip($keys));
    }

    /**
     * @template T of Model
     *
     * @param array<string, scalar|null> $query
     * @param RequestOptions             $options
     * @param class-string<T>            $class
     *
     * @return Collection<T>
     */
    protected function collection(string $path, array $query, array $options, string $class): Collection
    {
        return new Collection($this->transport->request('GET', $path, $query, null, $options), $class);
    }
}
