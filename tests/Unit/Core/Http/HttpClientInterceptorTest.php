<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\Interceptor;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\LogLevel;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

final class HttpClientInterceptorTest extends TestCase
{
    public function testPerRequestInterceptorsRunBeforeGlobalOnes(): void
    {
        $order = [];

        $perRequest = new SpyInterceptor(function () use (&$order): void {
            $order[] = 'per-request';
        });
        $global = new SpyInterceptor(function () use (&$order): void {
            $order[] = 'global';
        });

        $client = new HttpClient(
            RequestClientConfig::fromArray([]),
            new StaticResponseAdapter(new Response(200, [], '{}')),
            StandardRetryPolicy::default(),
            [$global]
        );

        $options = new RequestOptions(
            'GET',
            '/v2/example',
            meta: ['interceptors' => [$perRequest]]
        );

        $client->request($options);

        self::assertSame(['per-request', 'global'], $order);
    }

    public function testLoggingInterceptorEmitsEvents(): void
    {
        $events = [];
        $logger = function (string $event, array $meta) use (&$events): void {
            $events[] = [$event, $meta];
        };

        $client = new HttpClient(
            RequestClientConfig::fromArray([]),
            new StaticResponseAdapter(new Response(200, [], '{}')),
            StandardRetryPolicy::default(),
            [],
            $logger,
            LogLevel::DEBUG
        );

        $client->request(new RequestOptions('GET', '/v2/example'));

        self::assertCount(2, $events); // start + response (no error)
        self::assertSame('request:start', $events[0][0]);
        self::assertSame('request:response', $events[1][0]);
    }
}

final class SpyInterceptor implements Interceptor
{
    /**
     * @var callable(): void
     */
    private $callback;

    /**
     * @param callable(): void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function handle(PendingRequest $request, callable $next): Response
    {
        ($this->callback)();

        return $next($request);
    }
}

final class StaticResponseAdapter implements HttpAdapter
{
    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function send(PendingRequest $request): Response
    {
        return $this->response;
    }

    public function description(): string
    {
        return 'static';
    }
}
