<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Pay\V1;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Pay\V1\PayV1Api;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class PayV1ApiTest extends TestCase
{
    public function testPayRequest(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'paymentId' => 'pay_legacy',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->payRequest(['amount' => 1000]);

        self::assertSame('pay_legacy', $result['paymentId'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/pay', $path);
    }

    public function testPayRequestAddsSchemaMetadata(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], '{}')], $adapter);
        $api->payRequest(['amount' => 500], ['validation' => ['requests' => false]]);

        $meta = $adapter->lastRequest?->meta() ?? [];

        self::assertArrayHasKey('requestSchemaKey', $meta);
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey']);
        self::assertSame('PayV1Controller_payRequest_v1', $meta['requestSchemaKey']->operationId());
        self::assertArrayHasKey('responseSchemaKey', $meta);
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey']);
        self::assertSame(201, $meta['responseSchemaKey']->status());
        self::assertArrayHasKey('validation', $meta);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PayV1Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PayV1Api($http);
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
