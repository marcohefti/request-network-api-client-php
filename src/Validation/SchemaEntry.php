<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use Opis\JsonSchema\Schema;

final class SchemaEntry
{
    public const SOURCE_GENERATED = 'generated';
    public const SOURCE_OVERRIDE = 'override';

    private SchemaKey $key;

    /**
     * @var bool|string|object|Schema
     */
    private mixed $schema;

    /**
     * @var callable(mixed): mixed|null
     */
    private $preprocessor;

    private string $source;

    /**
     * @param bool|string|object|Schema $schema
     * @param callable(mixed): mixed|null $preprocessor
     */
    public function __construct(
        SchemaKey $key,
        mixed $schema,
        ?callable $preprocessor = null,
        string $source = self::SOURCE_GENERATED
    ) {
        $this->key = $key;
        $this->schema = $schema;
        $this->preprocessor = $preprocessor;
        $this->source = $source;
    }

    public function key(): SchemaKey
    {
        return $this->key;
    }

    /**
     * @return bool|string|object|Schema
     */
    public function schema(): mixed
    {
        return $this->schema;
    }

    /**
     * @return callable(mixed): mixed|null
     */
    public function preprocessor(): ?callable
    {
        return $this->preprocessor;
    }

    public function source(): string
    {
        return $this->source;
    }
}
