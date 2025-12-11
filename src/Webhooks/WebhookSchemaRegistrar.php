<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;

final class WebhookSchemaRegistrar
{
    private const SPEC_PATH = 'specs/webhooks/request-network-webhooks.json';

    private bool $registered = false;

    public function __construct(
        private readonly SchemaRegistry $registry,
        private readonly SchemaKeyFactory $schemaKeys
    ) {
    }

    public function registerDefaults(): void
    {
        if ($this->registered) {
            return;
        }

        $document = $this->loadDocument();
        if ($document === null) {
            return;
        }

        $components = $document['components'] ?? null;

        foreach ($document['webhooks'] as $eventName => $definition) {
            $schema = $this->extractSchema($definition);
            if ($schema === null) {
                continue;
            }

            if (! isset($schema['$schema'])) {
                $schema['$schema'] = 'https://json-schema.org/draft/2020-12/schema';
            }

            if (! isset($schema['components']) && is_array($components)) {
                $schema['components'] = $components;
            }

            $this->registry->register(
                $this->schemaKeys->webhook((string) $eventName),
                $schema,
                null,
                SchemaEntry::SOURCE_GENERATED
            );
        }

        $this->registered = true;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>|null
     */
    private function extractSchema(array $definition): ?array
    {
        $operation = $definition['post'] ?? $definition['put'] ?? null;
        if (! is_array($operation)) {
            return null;
        }

        $requestBody = $operation['requestBody']['content']['application/json']['schema'] ?? null;
        if (! is_array($requestBody)) {
            return null;
        }

        return $requestBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadDocument(): ?array
    {
        $base = dirname(__DIR__, 2);

        $specPath = $base . DIRECTORY_SEPARATOR . self::SPEC_PATH;
        if (! is_file($specPath)) {
            return null;
        }

        $contents = file_get_contents($specPath);
        if ($contents === false) {
            return null;
        }

        $document = json_decode($contents, true);
        if (! is_array($document) || ! isset($document['webhooks']) || ! is_array($document['webhooks'])) {
            return null;
        }

        return $document;
    }
}
