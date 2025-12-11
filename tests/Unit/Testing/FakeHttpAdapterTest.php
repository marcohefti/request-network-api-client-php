<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\Testing\FakeHttpAdapter;
use RuntimeException;

final class FakeHttpAdapterTest extends TestCase
{
    public function testQueuesResponsesAndRecordsRequests(): void
    {
        $adapter = new FakeHttpAdapter([
            FakeHttpAdapter::jsonResponse(['status' => 'ok']),
        ]);

        $pending = $this->pending('POST', '/v2/payments');
        $response = $adapter->send($pending);

        self::assertSame(200, $response->status());
        self::assertSame('application/json', $response->header('content-type'));
        self::assertCount(1, $adapter->sentRequests());
    }

    public function testCallableResponsesReceivePendingRequest(): void
    {
        $adapter = new FakeHttpAdapter();
        $adapter->queueResponse(function (PendingRequest $request) {
            self::assertSame('GET', $request->method());

            return FakeHttpAdapter::jsonResponse(['handled' => true], 202);
        });

        $response = $adapter->send($this->pending('GET', '/v2/requests'));

        self::assertSame(202, $response->status());
    }

    public function testAssertSentPassesWhenPredicateMatches(): void
    {
        $adapter = new FakeHttpAdapter([
            FakeHttpAdapter::jsonResponse([], 204),
        ]);
        $adapter->send($this->pending('DELETE', '/v2/client-ids/abc'));

        $adapter->assertSent(function (PendingRequest $request): bool {
            return $request->method() === 'DELETE';
        });

        $this->expectException(RuntimeException::class);
        $adapter->assertSent(function (PendingRequest $request): bool {
            return $request->method() === 'POST';
        });
    }

    public function testAssertNothingSentFailsWhenRequestsExist(): void
    {
        $adapter = new FakeHttpAdapter([
            FakeHttpAdapter::jsonResponse([], 200),
        ]);
        $adapter->send($this->pending('GET', '/v2/payer'));

        $this->expectException(RuntimeException::class);
        $adapter->assertNothingSent();
    }

    private function pending(string $method, string $path): PendingRequest
    {
        $config = RequestClientConfig::fromArray(['apiKey' => 'rk_test']);
        $options = new RequestOptions($method, $path);

        return new PendingRequest($config, $options);
    }
}

