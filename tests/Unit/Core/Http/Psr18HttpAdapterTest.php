<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Exception\TransportException;
use RequestSuite\RequestPhpClient\Core\Http\Adapter\Psr18HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

final class Psr18HttpAdapterTest extends TestCase
{
    public function testSendRequest(): void
    {
        $factory = new Psr17Factory();
        $client = new DummyClient(new Psr7Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'));

        $adapter = new Psr18HttpAdapter($client, $factory, $factory);
        $http = new HttpClient(RequestClientConfig::fromArray([]), $adapter, StandardRetryPolicy::default());

        $http->request(new RequestOptions(
            'POST',
            '/v2/test',
            [],
            [],
            ['foo' => 'bar']
        ));

        self::assertInstanceOf(RequestInterface::class, $client->lastRequest);
        self::assertSame('POST', $client->lastRequest->getMethod());
        self::assertSame('https://api.request.network/v2/test', (string) $client->lastRequest->getUri());
        self::assertSame('application/json', $client->lastRequest->getHeaderLine('Content-Type'));
        self::assertSame('{"foo":"bar"}', (string) $client->lastRequest->getBody());
    }

    public function testClientExceptionMapsToTransportException(): void
    {
        $factory = new Psr17Factory();
        $client = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class('boom') extends \RuntimeException implements ClientExceptionInterface {
                };
            }
        };

        $adapter = new Psr18HttpAdapter($client, $factory, $factory);
        $http = new HttpClient(RequestClientConfig::fromArray([]), $adapter, StandardRetryPolicy::default());

        $this->expectException(TransportException::class);

        $http->request(new RequestOptions('GET', '/v2/test'));
    }
}

final class DummyClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    private Psr7Response $response;

    public function __construct(Psr7Response $response)
    {
        $this->response = $response;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
