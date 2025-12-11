<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

final class SchemaRegistryBootConfig
{
    private const LOAD_GENERATED_SCHEMAS = 1;
    private const REGISTER_OVERRIDES = 2;

    private int $flags;

    private ?string $manifestPath;

    public function __construct(
        int $flags = self::LOAD_GENERATED_SCHEMAS | self::REGISTER_OVERRIDES,
        ?string $manifestPath = null
    ) {
        $this->flags = $flags;
        $this->manifestPath = $manifestPath;
    }

    public function withoutGeneratedSchemas(): self
    {
        return new self($this->flags & ~self::LOAD_GENERATED_SCHEMAS, $this->manifestPath);
    }

    public function withoutOverrides(): self
    {
        return new self($this->flags & ~self::REGISTER_OVERRIDES, $this->manifestPath);
    }

    public function withManifestPath(?string $path): self
    {
        return new self($this->flags, $path);
    }

    public function shouldLoadGeneratedSchemas(): bool
    {
        return ($this->flags & self::LOAD_GENERATED_SCHEMAS) !== 0;
    }

    public function shouldRegisterOverrides(): bool
    {
        return ($this->flags & self::REGISTER_OVERRIDES) !== 0;
    }

    public function manifestPath(): ?string
    {
        return $this->manifestPath;
    }
}
