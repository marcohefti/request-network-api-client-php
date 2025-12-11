<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Requests;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestsApi;

final class RequestsApiTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'requestId' => 'req-123',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->create(['name' => 'Test'], ['timeoutMs' => 1000]);

        self::assertSame('req-123', $result['requestId'] ?? null);
        $lastRequest = $adapter->lastRequest;
        self::assertSame('POST', $lastRequest?->method());
        $path = parse_url($lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/request', $path);
    }

    public function testGetPaymentRoutesBuildsQuery(): void
    {
        $responseBody = json_encode(['routes' => []], JSON_THROW_ON_ERROR);
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], $responseBody)], $adapter);

        $api->getPaymentRoutes('req-1', ['wallet' => '0xabc', 'feeAddress' => null, 'amount' => 100]);

        $lastUrl = $adapter->lastRequest?->url() ?? '';
        parse_str(parse_url($lastUrl, PHP_URL_QUERY) ?? '', $query);
        self::assertSame('0xabc', $query['wallet'] ?? null);
        self::assertSame('100', $query['amount'] ?? null);
    }

    public function testGetPaymentCalldataReturnsKind(): void
    {
        $responseBody = json_encode(['transactions' => []], JSON_THROW_ON_ERROR);
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], $responseBody)], $adapter);

        $result = $api->getPaymentCalldata('req-1');

        self::assertSame('calldata', $result->kind);
    }

    public function testGetRequestStatusNormalisesResult(): void
    {
        $responseBody = json_encode([
            'status' => 'completed',
            'hasBeenPaid' => true,
            'requestId' => 'req-55',
        ], JSON_THROW_ON_ERROR);
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], $responseBody)], $adapter);

        $result = $api->getRequestStatus('req-55');

        self::assertSame('paid', $result->kind);
        self::assertSame('req-55', $result->requestId);
    }

    public function testSendPaymentIntent(): void
    {
        $api = $this->apiWithResponses([new Response(204, [], '')], $adapter);

        $api->sendPaymentIntent('pi-1', ['amount' => 123]);

        $lastRequest = $adapter->lastRequest;
        self::assertSame('POST', $lastRequest?->method());
        $path = parse_url($lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/request/payment-intents/pi-1', $path);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): RequestsApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new RequestsApi($http);
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
