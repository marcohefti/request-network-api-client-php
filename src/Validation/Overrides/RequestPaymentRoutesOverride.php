<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation\Overrides;

use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;

final class RequestPaymentRoutesOverride
{
    public function register(SchemaRegistry $registry): void
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'required' => ['routes'],
            'properties' => [
                'routes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'fee' => [
                                'oneOf' => [
                                    ['type' => 'number'],
                                    ['type' => 'string'],
                                ],
                            ],
                        ],
                        'additionalProperties' => true,
                    ],
                ],
            ],
            'additionalProperties' => true,
        ];

        $factory = new SchemaKeyFactory();

        $registry->register(
            $factory->response('RequestControllerV2_getRequestPaymentRoutes_v2', 200),
            $schema,
            null,
            SchemaEntry::SOURCE_OVERRIDE
        );
    }
}
