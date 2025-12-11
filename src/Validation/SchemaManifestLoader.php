<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use stdClass;

final class SchemaManifestLoader
{
    public function registerGeneratedSchemas(
        SchemaRegistry $registry,
        ?string $manifestPath = null
    ): void {
        $manifest = $this->readManifest($manifestPath ?? $this->defaultManifestPath());
        if ($manifest === null) {
            return;
        }

        $specData = $this->readSpec($manifest);
        if ($specData === null) {
            return;
        }

        $components = $specData['components'] ?? [];

        foreach ($this->manifestEntries($manifest) as $entry) {
            $key = $this->createKeyFromEntry($entry['key'] ?? null);
            if (! $key instanceof SchemaKey) {
                continue;
            }

            $schema = $this->schemaFromPointer($specData, $components, $entry['pointer'] ?? null);
            if ($schema === null) {
                continue;
            }

            $registry->register($key, $schema);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (! is_array($decoded) || ! isset($decoded['entries']) || ! is_array($decoded['entries'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, mixed>|null
     */
    private function readSpec(array $manifest): ?array
    {
        $specPath = $this->resolveSpecPath($manifest['specPath'] ?? null);
        if ($specPath === null || ! is_file($specPath)) {
            return null;
        }

        $contents = file_get_contents($specPath);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<int, array<string, mixed>>
     */
    private function manifestEntries(array $manifest): array
    {
        /** @var array<int, array<string, mixed>> $entries */
        $entries = $manifest['entries'];

        return $entries;
    }

    private function defaultManifestPath(): string
    {
        $base = dirname(__DIR__, 1); // packages/request-php-client/src -> packages/request-php-client
        $path = $base
            . DIRECTORY_SEPARATOR . 'generated'
            . DIRECTORY_SEPARATOR . 'Validation'
            . DIRECTORY_SEPARATOR . 'Schemas'
            . DIRECTORY_SEPARATOR . 'index.json';

        return $path;
    }

    private function resolveSpecPath(?string $specPath): ?string
    {
        if (! is_string($specPath) || $specPath === '') {
            return null;
        }

        if (str_starts_with($specPath, DIRECTORY_SEPARATOR)) {
            return $specPath;
        }

        $root = dirname(__DIR__, 1);

        return $root . DIRECTORY_SEPARATOR . ltrim($specPath, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array<string, mixed>|null $entry
     */
    private function createKeyFromEntry(?array $entry): ?SchemaKey
    {
        if (! is_array($entry) || ! isset($entry['operationId'], $entry['kind'])) {
            return null;
        }

        $status = $entry['status'] ?? null;
        if ($status !== null) {
            $status = (int) $status;
        }

        $variant = $entry['variant'] ?? null;
        $variant = is_string($variant) ? $variant : null;

        return new SchemaKey(
            (string) $entry['operationId'],
            (string) $entry['kind'],
            $variant,
            $status
        );
    }

    /**
     * @param array<mixed> $spec
     * @param array<mixed> $components
     */
    private function schemaFromPointer(array $spec, array $components, ?string $pointer): ?stdClass
    {
        if (! is_string($pointer) || $pointer === '') {
            return null;
        }

        $fragment = $this->extractPointer($spec, $pointer);
        if ($fragment === null) {
            return null;
        }

        $schemaObject = $this->toStdClass($fragment);
        if ($schemaObject === null) {
            return null;
        }

        if (! property_exists($schemaObject, '$schema')) {
            $schemaObject->{'$schema'} = 'https://json-schema.org/draft/2020-12/schema';
        }

        if (! property_exists($schemaObject, 'components')) {
            $schemaObject->components = self::toStdClass($components) ?? new stdClass();
        }

        return $schemaObject;
    }

    /**
     * @param array<mixed> $document
     */
    private function extractPointer(array $document, string $pointer): mixed
    {
        if ($pointer === '' || $pointer === '/') {
            return $document;
        }

        $segments = explode('/', ltrim($pointer, '/'));
        $current = $document;

        foreach ($segments as $segment) {
            $decoded = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if (is_array($current) && array_key_exists($decoded, $current)) {
                $current = $current[$decoded];
                continue;
            }

            return null;
        }

        return $current;
    }

    private function toStdClass(mixed $value): ?stdClass
    {
        if ($value instanceof stdClass) {
            return $value;
        }

        if (is_array($value)) {
            $decoded = json_decode(json_encode($value, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

            return $decoded instanceof stdClass ? $decoded : null;
        }

        return null;
    }
}
