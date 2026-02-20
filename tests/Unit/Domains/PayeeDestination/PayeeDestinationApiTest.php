<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\PayeeDestination;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\PayeeDestination\PayeeDestinationApi;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class PayeeDestinationApiTest extends TestCase
{
    public function testGetSigningData(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'nonce' => 'nonce-1',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->getSigningData([
            'walletAddress' => '0xabc',
            'action' => 'add',
            'tokenAddress' => '0xdef',
            'chainId' => '8453',
        ]);

        self::assertSame('nonce-1', $result['nonce'] ?? null);

        $request = $adapter->lastRequest;
        $path = parse_url($request?->url() ?? '', PHP_URL_PATH);
        parse_str(parse_url($request?->url() ?? '', PHP_URL_QUERY) ?? '', $query);

        self::assertSame('/v2/payee-destination/signing-data', $path);
        self::assertSame('0xabc', $query['walletAddress'] ?? null);
        self::assertSame('add', $query['action'] ?? null);
    }

    public function testCreateAddsSchemaMetadata(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->create([
            'signature' => '0xsignature',
            'nonce' => 'nonce-1',
        ]);

        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey'] ?? null);
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame('PayeeDestinationController_createPayeeDestination_v2', $meta['requestSchemaKey']->operationId());
        self::assertSame(201, $meta['responseSchemaKey']->status());
    }

    public function testGetByIdUsesEncodedPath(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->getById('base:0xabc:0xdef');

        $request = $adapter->lastRequest;
        $path = parse_url($request?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/payee-destination/base%3A0xabc%3A0xdef', $path);
    }

    public function testDeactivateUsesDeleteAndSchemaMetadata(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->deactivate('base:0xabc:0xdef', [
            'signature' => '0xsignature',
            'nonce' => 'nonce-2',
        ]);

        $request = $adapter->lastRequest;
        self::assertSame('DELETE', $request?->method());
        $meta = $request?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey'] ?? null);
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame(200, $meta['responseSchemaKey']->status());
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PayeeDestinationApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PayeeDestinationApi($http);
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
