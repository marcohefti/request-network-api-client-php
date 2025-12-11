<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

final class SchemaValidationResult
{
    private bool $success;

    private mixed $data;

    private ?SchemaValidationException $error;

    public function __construct(bool $success, mixed $data = null, ?SchemaValidationException $error = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function error(): ?SchemaValidationException
    {
        return $this->error;
    }
}
