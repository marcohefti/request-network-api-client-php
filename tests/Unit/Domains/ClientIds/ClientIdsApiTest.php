<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\ClientIds;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\ClientIds\ClientIdsApi;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class ClientIdsApiTest extends TestCase
{
    public function testListClientIds(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            ['id' => 'client_1'],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->list();

        self::assertSame('client_1', $result[0]['id'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/client-ids', $path);
        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame('ClientIdV2Controller_findAll_v2', $meta['responseSchemaKey']->operationId());
    }

    public function testCreateClientIdAddsSchemaMetadata(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'id' => 'client_new',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $api->create(['name' => 'My Client']);

        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['requestSchemaKey'] ?? null);
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame(201, $meta['responseSchemaKey']->status());
    }

    public function testRevokeClientIdUsesDelete(): void
    {
        $api = $this->apiWithResponses([new Response(204, ['content-type' => 'application/json'], '{}')], $adapter);

        $api->revoke('client_2');

        $request = $adapter->lastRequest;
        self::assertSame('DELETE', $request?->method());
        $path = parse_url($request?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/client-ids/client_2', $path);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): ClientIdsApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new ClientIdsApi($http);
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
