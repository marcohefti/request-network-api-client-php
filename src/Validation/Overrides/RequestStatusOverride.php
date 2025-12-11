<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation\Overrides;

use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;

final class RequestStatusOverride
{
    public function register(SchemaRegistry $registry): void
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'requestId' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'recurrence' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
                'originalRequestId' => ['type' => 'string'],
                'originalRequestPaymentReference' => ['type' => 'string'],
                'payments' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'additionalProperties' => true],
                ],
            ],
            'additionalProperties' => true,
        ];

        $factory = new SchemaKeyFactory();

        $registry->register(
            $factory->response('RequestControllerV2_getRequestStatus_v2', 200),
            $schema,
            self::normaliser(...),
            SchemaEntry::SOURCE_OVERRIDE
        );
    }

    private static function normaliser(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $mutable = $value;

        foreach (['recurrence', 'originalRequestId', 'originalRequestPaymentReference'] as $key) {
            if (array_key_exists($key, $mutable) && $mutable[$key] === null) {
                unset($mutable[$key]);
            }
        }

        return $mutable;
    }
}
