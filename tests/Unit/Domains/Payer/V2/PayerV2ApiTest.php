<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payer\V2;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Payer\ComplianceStatusFormatter;
use RequestSuite\RequestPhpClient\Domains\Payer\V2\PayerV2Api;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class PayerV2ApiTest extends TestCase
{
    public function testCreateComplianceData(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'userId' => 'user-123',
            'status' => ['agreementStatus' => 'pending', 'kycStatus' => 'initiated'],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->createComplianceData(['clientUserId' => 'merchant-user-1']);

        self::assertSame('user-123', $result['userId'] ?? null);
        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertArrayHasKey('requestSchemaKey', $meta);
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey']);
        self::assertSame('PayerV2Controller_getComplianceData_v2', $meta['requestSchemaKey']->operationId());
        self::assertArrayHasKey('responseSchemaKey', $meta);
    }

    public function testGetComplianceStatus(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'userId' => 'uuid-merchant-user-1',
            'isCompliant' => true,
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->getComplianceStatus('merchant-user-1');

        self::assertTrue($result['isCompliant']);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/payer/merchant-user-1', $path);

        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertArrayNotHasKey('requestSchemaKey', $meta);
        self::assertArrayHasKey('responseSchemaKey', $meta);
        self::assertSame('PayerV2Controller_getComplianceStatus_v2', $meta['responseSchemaKey']->operationId());
    }

    public function testCreatePaymentDetailsErrorIncludesSummary(): void
    {
        $errorResponse = json_encode([
            'detail' => [
                'kycStatus' => 'pending',
                'agreementStatus' => 'not_started',
                'clientUserId' => 'merchant-user-1',
            ],
        ], JSON_THROW_ON_ERROR);

        $api = $this->apiWithResponses([
            new Response(409, ['content-type' => 'application/json'], $errorResponse),
        ], $adapter);

        try {
            $api->createPaymentDetails('merchant-user-1', ['bankName' => 'Monzo']);
            self::fail('Expected RequestApiException to be thrown.');
        } catch (RequestApiException $exception) {
            $summary = ComplianceStatusFormatter::summaryFromException($exception);
            self::assertNotNull($summary);
            self::assertStringContainsString('KYC: pending', $summary);
            self::assertStringContainsString('Agreement: not started', $summary);
            self::assertStringContainsString('Client user: merchant-user-1', $summary);
        }
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PayerV2Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PayerV2Api($http);
    }
}

final class RecordingAdapter implements HttpAdapter
{
    /**
     * @var array<int, Response>
     */
    private array $responses;

    public ?PendingRequest $lastRequest = null;

    /**
     * @param array<int, Response> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function send(PendingRequest $request): Response
    {
        $this->lastRequest = $request;

        if ($this->responses === []) {
            return new Response(200, ['content-type' => 'application/json'], '{}');
        }

        return array_shift($this->responses);
    }

    public function description(): string
    {
        return 'recording';
    }
}
