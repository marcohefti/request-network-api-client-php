<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;

/**
 * Builds query strings with configurable serialisation (comma, repeat, or custom callable).
 */
final class QueryStringBuilder
{
    /**
     * @param array<string, scalar|array<int, scalar>|null> $query
     * @param 'comma'|'repeat'|callable|null $serializer
     */
    public function build(array $query, string|callable|null $serializer = null): string
    {
        if ($query === []) {
            return '';
        }

        $pairs = [];
        $set = static function (string $key, string $value) use (&$pairs): void {
            $encodedKey = rawurlencode($key);
            $encodedValue = rawurlencode($value);
            // Remove existing entries for the key.
            $pairs = array_values(array_filter(
                $pairs,
                static fn(array $pair) => $pair[0] !== $encodedKey
            ));
            $pairs[] = [$encodedKey, $encodedValue];
        };

        $append = static function (string $key, string $value) use (&$pairs): void {
            $pairs[] = [rawurlencode($key), rawurlencode($value)];
        };

        $resolver = $this->resolveSerializer($serializer);

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (! is_string($key) || $key === '') {
                throw new ConfigurationException('Query parameter keys must be non-empty strings.');
            }
            $resolver($key, $value, $set, $append);
        }

        if ($pairs === []) {
            return '';
        }

        return implode('&', array_map(
            static fn(array $pair) => $pair[0] . '=' . $pair[1],
            $pairs
        ));
    }

    /**
     * @param 'comma'|'repeat'|callable|null $serializer
     * @return callable(
     *   string,
     *   scalar|array<int, scalar>,
     *   callable(string,string):void,
     *   callable(string,string):void
     * ): void
     */
    private function resolveSerializer(string|callable|null $serializer): callable
    {
        if ($serializer === null || $serializer === 'comma') {
            return function (string $key, mixed $value, callable $set, callable $append): void {
                // Parameter kept for signature compatibility.
                unset($append);
                $joined = implode(',', $this->normaliseItems($value));
                $set($key, $joined);
            };
        }

        if ($serializer === 'repeat') {
            return function (string $key, mixed $value, callable $set, callable $append): void {
                unset($set);
                foreach ($this->normaliseItems($value) as $item) {
                    $append($key, $item);
                }
            };
        }

        if (is_callable($serializer)) {
            return function (
                string $key,
                mixed $value,
                callable $set,
                callable $append
            ) use ($serializer): void {
                $serializer($key, $value, $set, $append);
            };
        }

        throw new ConfigurationException('Unsupported query serializer provided.');
    }

    /**
     * @param scalar|array<int, scalar> $value
     * @return array<int, string>
     */
    private function normaliseItems(mixed $value): array
    {
        if (is_array($value)) {
            return array_map(
                fn(mixed $item): string => $this->stringify($item),
                array_values($value)
            );
        }

        return [$this->stringify($value)];
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        throw new ConfigurationException('Query parameter values must be scalar or arrays of scalar values.');
    }
}
