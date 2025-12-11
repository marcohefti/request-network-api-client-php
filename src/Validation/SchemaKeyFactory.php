<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

final class SchemaKeyFactory
{
    public function request(string $operationId, ?string $variant = null): SchemaKey
    {
        return new SchemaKey($operationId, SchemaKey::KIND_REQUEST, $variant);
    }

    public function response(string $operationId, int $status, ?string $variant = null): SchemaKey
    {
        return new SchemaKey($operationId, SchemaKey::KIND_RESPONSE, $variant, $status);
    }

    public function webhook(string $operationId, ?string $variant = null): SchemaKey
    {
        return new SchemaKey($operationId, SchemaKey::KIND_WEBHOOK, $variant);
    }

    public function error(string $operationId, ?int $status = null): SchemaKey
    {
        return new SchemaKey($operationId, SchemaKey::KIND_ERROR, null, $status);
    }
}
