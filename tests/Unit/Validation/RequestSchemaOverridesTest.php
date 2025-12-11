<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;
use RequestSuite\RequestPhpClient\Validation\SchemaValidator;

final class RequestSchemaOverridesTest extends TestCase
{
    private SchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $registry = new SchemaRegistry();
        $this->validator = new SchemaValidator($registry);
    }

    public function testRequestStatusOverrideRemovesNulls(): void
    {
        $result = $this->validator->parseWithRegistry(
            (new SchemaKeyFactory())->response('RequestControllerV2_getRequestStatus_v2', 200),
            [
                'requestId' => 'req-123',
                'originalRequestId' => null,
                'status' => 'paid',
            ],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testPaymentRoutesOverrideAllowsStringFees(): void
    {
        $result = $this->validator->parseWithRegistry(
            (new SchemaKeyFactory())->response('RequestControllerV2_getRequestPaymentRoutes_v2', 200),
            [
                'routes' => [
                    ['fee' => '0.1'],
                    ['fee' => 0.2],
                ],
            ],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testPayerComplianceOverrideAcceptsExtraStatuses(): void
    {
        $result = $this->validator->parseWithRegistry(
            (new SchemaKeyFactory())->response('PayerV2Controller_getComplianceData_v2', 200),
            [
                'status' => [
                    'agreementStatus' => 'pending',
                    'kycStatus' => 'initiated',
                ],
            ],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertTrue($result->isSuccess());
    }
}
