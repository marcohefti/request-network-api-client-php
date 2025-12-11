<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use stdClass;

final class SchemaValidator
{
    private SchemaRegistry $registry;

    private Validator $validator;

    private SchemaValidationOptions $defaultOptions;

    public function __construct(
        ?SchemaRegistry $registry = null,
        ?Validator $validator = null,
        ?SchemaValidationOptions $defaultOptions = null
    ) {
        $this->registry = $registry ?? new SchemaRegistry();
        $this->validator = $validator ?? new Validator();
        $this->defaultOptions = $defaultOptions ?? new SchemaValidationOptions();
    }

    public function parseWithSchema(mixed $schema, mixed $value, ?string $description = null): SchemaValidationResult
    {
        $normalisedSchema = $this->normaliseSchemaDocument($schema);
        $preparedValue = $this->normaliseArrayValue($value);
        $result = $this->validator->validate($preparedValue, $normalisedSchema);
        if ($result->isValid()) {
            return $this->success($value);
        }

        $message = $description ?? 'Validation failed';

        return $this->failure(new SchemaValidationException($message, $result->error()));
    }

    public function parseWithRegistry(
        SchemaKey $key,
        mixed $value,
        RuntimeValidationConfig $config,
        ?SchemaValidationOptions $options = null
    ): SchemaValidationResult {
        $options ??= $this->defaultOptions;
        $entry = $this->registry->resolve($key);

        if ($entry === null) {
            if ($options->shouldSkipOnMissingSchema()) {
                return $this->success($value);
            }

            $message = sprintf('No schema registered for %s', (string) $key);

            return $this->failure(new SchemaValidationException($message));
        }

        $preparedValue = $this->prepareValue($value, $entry->preprocessor());

        if (! $this->shouldValidate($key, $config)) {
            return $this->success($preparedValue);
        }

        return $this->parseWithSchema($entry->schema(), $preparedValue, $options->description());
    }

    /**
     */
    private function normaliseSchemaDocument(mixed $schema): mixed
    {
        if (! is_array($schema)) {
            return $schema;
        }

        $encoded = json_encode($schema, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);

        return $decoded instanceof stdClass ? $decoded : $schema;
    }

    private function normaliseArrayValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);

        if ((is_array($decoded) && ! array_is_list($value)) || ($value === [] && is_array($decoded))) {
            return (object) $decoded;
        }

        return $decoded;
    }

    private function prepareValue(mixed $value, ?callable $preprocessor): mixed
    {
        $processed = $preprocessor !== null ? $preprocessor($value) : $value;

        return $this->normaliseArrayValue($processed);
    }

    private function shouldValidate(SchemaKey $key, RuntimeValidationConfig $config): bool
    {
        return match ($key->kind()) {
            SchemaKey::KIND_REQUEST => $config->requests,
            SchemaKey::KIND_RESPONSE, SchemaKey::KIND_WEBHOOK => $config->responses,
            SchemaKey::KIND_ERROR => $config->errors,
            default => true,
        };
    }

    private function success(mixed $data): SchemaValidationResult
    {
        return new SchemaValidationResult(true, $data);
    }

    private function failure(SchemaValidationException $exception): SchemaValidationResult
    {
        return new SchemaValidationResult(false, null, $exception);
    }

    public function registry(): SchemaRegistry
    {
        return $this->registry;
    }
}
