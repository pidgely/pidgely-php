<?php

declare(strict_types=1);

namespace Pidgely\Internal;

use Pidgely\Model\Model;

/**
 * Typed accessors over decoded JSON.
 *
 * @internal
 */
final class Data
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return \is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        return (bool) ($data[$key] ?? false);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    public static function stringList(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (\is_string($value)) {
            return [$value];
        }
        if (!\is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (\is_scalar($item)) {
                $out[] = (string) $item;
            }
        }

        return $out;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function map(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return \is_array($value) ? self::stringKeys($value) : [];
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<string, mixed>
     */
    public static function stringKeys(array $value): array
    {
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    public static function objects(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!\is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (\is_array($item)) {
                $out[] = self::stringKeys($item);
            }
        }

        return $out;
    }

    /**
     * @template T of Model
     *
     * @param array<array-key, mixed> $data
     * @param class-string<T>         $class
     *
     * @return list<T>
     */
    public static function models(array $data, string $key, string $class): array
    {
        $out = [];
        foreach (self::objects($data, $key) as $item) {
            $out[] = new $class($item);
        }

        return $out;
    }
}
