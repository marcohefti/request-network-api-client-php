<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use InvalidArgumentException;
use Opis\JsonSchema\Schema;
use RequestSuite\RequestPhpClient\Validation\Overrides\OverrideRegistrar;

final class SchemaRegistry
{
    /**
     * @var array<string, SchemaEntry>
     */
    private array $entries = [];

    private static ?self $global = null;

    public function __construct(
        ?SchemaRegistryBootstrapper $bootstrapper = null,
        ?SchemaRegistryBootConfig $bootConfig = null
    ) {
        $loader = $bootstrapper ?? new SchemaRegistryBootstrapper(
            new SchemaManifestLoader(),
            new OverrideRegistrar()
        );
        $loader->bootstrap($this, $bootConfig);
    }

    public static function global(): self
    {
        if (self::$global === null) {
            self::$global = new self();
        }

        return self::$global;
    }

    public static function resetGlobal(): void
    {
        self::$global = new self();
    }

    public function register(
        SchemaKey $key,
        mixed $schema,
        ?callable $preprocessor = null,
        string $source = SchemaEntry::SOURCE_GENERATED
    ): void {
        $entry = new SchemaEntry($key, $this->normaliseSchema($schema), $preprocessor, $source);
        $this->entries[$key->descriptor()] = $entry;
    }

    public function resolve(SchemaKey $key): ?SchemaEntry
    {
        return $this->entries[$key->descriptor()] ?? null;
    }

    /**
     * @return array<string, SchemaEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    /**
     * @return bool|string|object|Schema
     */
    private function normaliseSchema(mixed $schema): mixed
    {
        if ($schema instanceof Schema || is_bool($schema) || is_string($schema) || is_object($schema)) {
            return $schema;
        }

        $encoded = json_encode($schema, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);
        if (! is_object($decoded)) {
            throw new InvalidArgumentException('Schema array must serialise to an object.');
        }

        return $decoded;
    }
}
