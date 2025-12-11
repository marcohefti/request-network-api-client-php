<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation\Overrides;

use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;

final class PayerComplianceOverride
{
    private const AGREEMENT_STATUSES = ['not_started', 'completed', 'pending', 'signed'];
    private const KYC_STATUSES = ['not_started', 'completed', 'initiated'];

    public function register(SchemaRegistry $registry): void
    {
        $factory = new SchemaKeyFactory();

        $registry->register(
            $factory->response('PayerV1Controller_getComplianceData_v1', 200),
            self::schema(),
            null,
            SchemaEntry::SOURCE_OVERRIDE
        );

        $registry->register(
            $factory->response('PayerV2Controller_getComplianceData_v2', 200),
            self::schema(),
            null,
            SchemaEntry::SOURCE_OVERRIDE
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'agreementUrl' => ['type' => 'string'],
                'kycUrl' => ['type' => 'string'],
                'userId' => ['type' => 'string'],
                'status' => [
                    'type' => 'object',
                    'properties' => [
                        'agreementStatus' => ['type' => 'string', 'enum' => self::AGREEMENT_STATUSES],
                        'kycStatus' => ['type' => 'string', 'enum' => self::KYC_STATUSES],
                    ],
                    'required' => ['agreementStatus', 'kycStatus'],
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['status'],
            'additionalProperties' => true,
        ];
    }
}
