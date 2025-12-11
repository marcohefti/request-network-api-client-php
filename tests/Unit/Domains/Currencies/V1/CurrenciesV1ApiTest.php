<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Currencies\V1;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Currencies\V1\CurrenciesV1Api;

final class CurrenciesV1ApiTest extends TestCase
{
    public function testListLegacyCurrencies(): void
    {
        $api = $this->apiWithResponses([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                [
                    'id' => 'USDC',
                    'symbol' => 'USDC',
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $adapter);

        $tokens = $api->list(['network' => 'mainnet']);

        self::assertSame('USDC', $tokens[0]['id'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/currencies', $path);
    }

    public function testListLegacyCurrenciesHandlesSingleObject(): void
    {
        $api = $this->apiWithResponses([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'id' => 'DAI',
            ], JSON_THROW_ON_ERROR)),
        ], $adapter);

        $tokens = $api->list();

        self::assertSame('DAI', $tokens[0]['id'] ?? null);
    }

    public function testGetConversionRoutes(): void
    {
        $api = $this->apiWithResponses([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'currencyId' => 'USDC',
                'conversionRoutes' => [['symbol' => 'DAI']],
            ], JSON_THROW_ON_ERROR)),
        ], $adapter);

        $routes = $api->getConversionRoutes('USDC');

        self::assertSame('USDC', $routes['currencyId'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/currencies/USDC/conversion-routes', $path);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): CurrenciesV1Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new CurrenciesV1Api($http);
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
            return new Response(200, ['content-type' => 'application/json'], '[]');
        }

        return array_shift($this->responses);
    }

    public function description(): string
    {
        return 'recording';
    }
}
