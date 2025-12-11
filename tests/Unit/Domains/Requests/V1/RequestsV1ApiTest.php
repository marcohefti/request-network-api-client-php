<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Requests\V1;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Requests\V1\RequestsV1Api;

final class RequestsV1ApiTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'paymentReference' => 'ref-1',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->create(['amount' => 1000]);

        self::assertSame('ref-1', $result['paymentReference'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/request', $path);
    }

    public function testGetRequestStatus(): void
    {
        $response = json_encode([
            'paymentReference' => 'ref-2',
            'hasBeenPaid' => true,
        ], JSON_THROW_ON_ERROR);
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], $response)], $adapter);

        $result = $api->getRequestStatus('ref-2');

        self::assertSame('paid', $result->kind);
        self::assertTrue($result->hasBeenPaid);
    }

    public function testStopRecurrence(): void
    {
        $api = $this->apiWithResponses([new Response(204, [], '')], $adapter);

        $api->stopRecurrence('ref-3');

        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/request/ref-3/stop-recurrence', $path);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): RequestsV1Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new RequestsV1Api($http);
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
