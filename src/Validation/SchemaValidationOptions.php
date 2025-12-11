<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

final class SchemaValidationOptions
{
    private bool $skipOnMissingSchema;

    private ?string $description;

    public function __construct()
    {
        $this->skipOnMissingSchema = false;
        $this->description = null;
    }

    public static function defaults(): self
    {
        return new self();
    }

    public static function skipMissing(?string $description = null): self
    {
        return (new self())->enableSkipOnMissingSchema()->withDescription($description);
    }

    public function withDescription(?string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function enableSkipOnMissingSchema(): self
    {
        if ($this->skipOnMissingSchema) {
            return $this;
        }

        $clone = clone $this;
        $clone->skipOnMissingSchema = true;

        return $clone;
    }

    public function shouldSkipOnMissingSchema(): bool
    {
        return $this->skipOnMissingSchema;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
