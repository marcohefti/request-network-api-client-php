<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

final class SchemaKey
{
    public const KIND_REQUEST = 'request';
    public const KIND_RESPONSE = 'response';
    public const KIND_WEBHOOK = 'webhook';
    public const KIND_ERROR = 'error';

    private string $operationId;

    private string $kind;

    private ?string $variant;

    private ?int $status;

    public function __construct(string $operationId, string $kind, ?string $variant = null, ?int $status = null)
    {
        $this->operationId = $operationId;
        $this->kind = $kind;
        $this->variant = $variant;
        $this->status = $status;
    }

    public function descriptor(): string
    {
        $variant = $this->variant ?? 'default';
        $status = $this->status !== null ? (string) $this->status : 'any';

        return sprintf('%s|%s|%s|%s', $this->operationId, $this->kind, $variant, $status);
    }

    public function operationId(): string
    {
        return $this->operationId;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function status(): ?int
    {
        return $this->status;
    }

    public function __toString(): string
    {
        return $this->descriptor();
    }
}
