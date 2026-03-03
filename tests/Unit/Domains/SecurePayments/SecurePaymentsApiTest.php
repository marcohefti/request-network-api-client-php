<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\SecurePayments;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\SecurePayments\SecurePaymentsApi;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class SecurePaymentsApiTest extends TestCase
{
    public function testFindByRequestId(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'token' => 'sp_123',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->findByRequestId('req_123', 'Bearer token-123');

        self::assertSame('sp_123', $result['token'] ?? null);

        $request = $adapter->lastRequest;
        $path = parse_url($request?->url() ?? '', PHP_URL_PATH);
        parse_str(parse_url($request?->url() ?? '', PHP_URL_QUERY) ?? '', $query);
        $headers = $request?->headers() ?? [];

        self::assertSame('/v2/secure-payments', $path);
        self::assertSame('req_123', $query['requestId'] ?? null);
        self::assertSame('Bearer token-123', $headers['Authorization'] ?? null);
    }

    public function testCreateAddsSchemaMetadata(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->create([
            'requests' => [
                ['destinationId' => 'destination', 'amount' => '10'],
            ],
        ]);

        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey'] ?? null);
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame('SecurePaymentController_createSecurePayment_v2', $meta['requestSchemaKey']->operationId());
        self::assertSame(201, $meta['responseSchemaKey']->status());
    }

    public function testGetByTokenUsesEncodedPathAndQuery(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->getByToken('sp:token', ['wallet' => '0xabc']);

        $request = $adapter->lastRequest;
        $path = parse_url($request?->url() ?? '', PHP_URL_PATH);
        parse_str(parse_url($request?->url() ?? '', PHP_URL_QUERY) ?? '', $query);

        self::assertSame('/v2/secure-payments/sp%3Atoken', $path);
        self::assertSame('0xabc', $query['wallet'] ?? null);
    }

    public function testFindByRequestIdRejectsEmptyAuthorization(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('authorization must not be empty.');

        $api = $this->apiWithResponses([], $adapter);
        $api->findByRequestId('req_123', '');
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): SecurePaymentsApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new SecurePaymentsApi($http);
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
