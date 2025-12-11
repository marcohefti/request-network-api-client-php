<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

final class JsonRequestHelperTest extends TestCase
{
    public function testRequestJsonEncodesBodyAndDecodesResponse(): void
    {
        $response = new Response(
            200,
            ['content-type' => 'application/json'],
            json_encode(['status' => 'ok', 'amount' => 123], JSON_THROW_ON_ERROR)
        );

        $adapter = new RecordingAdapter($response);
        $http = $this->createHttpClient($adapter);

        $helper = new JsonRequestHelper();

        $result = $helper->requestJson($http, [
            'operationId' => 'payments.create',
            'method' => 'POST',
            'path' => '/v2/payments',
            'body' => ['amount' => 123],
        ]);

        self::assertSame(['status' => 'ok', 'amount' => 123], $result);

        $sentHeaders = $adapter->lastRequest?->headers() ?? [];
        self::assertArrayHasKey('Accept', $sentHeaders);
        self::assertArrayHasKey('Content-Type', $sentHeaders);
        self::assertSame('application/json', $sentHeaders['Content-Type']);
    }

    public function testRequestJsonThrowsForErrorStatus(): void
    {
        $response = new Response(
            422,
            ['content-type' => 'application/json'],
            json_encode(['error' => 'invalid'], JSON_THROW_ON_ERROR)
        );

        $adapter = new RecordingAdapter($response);
        $http = $this->createHttpClient($adapter);

        $this->expectException(RequestApiException::class);

        $helper = new JsonRequestHelper();

        $helper->requestJson($http, [
            'operationId' => 'payments.create',
            'method' => 'POST',
            'path' => '/v2/payments',
        ]);
    }

    public function testRequestVoidThrowsOnFailure(): void
    {
        $response = new Response(500, [], 'error');
        $adapter = new RecordingAdapter($response);
        $http = $this->createHttpClient($adapter);

        $this->expectException(RequestApiException::class);

        $helper = new JsonRequestHelper();

        $helper->requestVoid($http, [
            'operationId' => 'payments.create',
            'method' => 'POST',
            'path' => '/v2/payments',
        ]);
    }

    private function createHttpClient(RecordingAdapter $adapter): HttpClient
    {
        return new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );
    }
}

final class RecordingAdapter implements HttpAdapter
{
    public ?PendingRequest $lastRequest = null;

    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function send(PendingRequest $request): Response
    {
        $this->lastRequest = $request;

        return $this->response;
    }

    public function description(): string
    {
        return 'recording-adapter';
    }
}
