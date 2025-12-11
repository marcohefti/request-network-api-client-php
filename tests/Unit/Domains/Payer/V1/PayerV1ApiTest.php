<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payer\V1;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Payer\V1\PayerV1Api;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class PayerV1ApiTest extends TestCase
{
    public function testCreateComplianceData(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'userId' => 'legacy-user-1',
            'status' => ['agreementStatus' => 'pending', 'kycStatus' => 'initiated'],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->createComplianceData(['clientUserId' => 'legacy-user-1']);

        self::assertSame('legacy-user-1', $result['userId'] ?? null);
        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey'] ?? null);
        self::assertSame('PayerV1Controller_getComplianceData_v1', $meta['requestSchemaKey']->operationId());
    }

    public function testGetPaymentDetailsUsesV1Path(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'paymentDetails' => [['id' => 'pd-legacy']],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->getPaymentDetails('legacy-user-2');

        self::assertSame('pd-legacy', $result['paymentDetails'][0]['id'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/payer/legacy-user-2/payment-details', $path);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PayerV1Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PayerV1Api($http);
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
