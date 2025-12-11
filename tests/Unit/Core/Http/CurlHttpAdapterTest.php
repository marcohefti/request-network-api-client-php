<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\Adapter\CurlHttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

final class CurlHttpAdapterTest extends TestCase
{
    public function testJsonBodyAddsContentType(): void
    {
        $adapter = new StubCurlHttpAdapter(200, ['Content-Type' => 'application/json'], '{}');
        $http = new HttpClient(RequestClientConfig::fromArray([]), $adapter, StandardRetryPolicy::default());

        $http->request(new RequestOptions(
            'POST',
            '/v2/example',
            [],
            ['Accept' => 'application/json'],
            ['foo' => 'bar']
        ));

        $call = $adapter->calls[0];

        self::assertSame('https://api.request.network/v2/example', $call['url']);
        self::assertSame('POST', $call['method']);
        self::assertSame('{"foo":"bar"}', $call['body']);
        self::assertSame('application/json', $call['headers']['Content-Type']);
    }
}

final class StubCurlHttpAdapter extends CurlHttpAdapter
{
    /**
     * @var array<int, array{url:string,method:string,headers:array<string,string>,body:?string,timeout:?int}>
     */
    public array $calls = [];

    /**
     * @param array<string, string> $responseHeaders
     */
    public function __construct(
        private int $status,
        private array $responseHeaders,
        private string $responseBody
    ) {
    }

    /**
     * @param array<string, string> $headers
     * @return array{0:int,1:array<string,string>,2:string}
     */
    protected function performRequest(string $url, string $method, array $headers, ?string $body, ?int $timeoutMs): array
    {
        $this->calls[] = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeoutMs,
        ];

        return [$this->status, $this->responseHeaders, $this->responseBody];
    }
}
