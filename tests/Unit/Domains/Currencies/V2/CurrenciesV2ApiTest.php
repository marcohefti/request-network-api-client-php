<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Currencies\V2;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Currencies\V2\CurrenciesV2Api;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class CurrenciesV2ApiTest extends TestCase
{
    public function testListCurrencies(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            [
                'id' => 'ETH-sepolia-sepolia',
                'symbol' => 'USDC',
            ],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $tokens = $api->list(['network' => 'sepolia']);

        self::assertSame('ETH-sepolia-sepolia', $tokens[0]['id'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/currencies', $path);

        parse_str(parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_QUERY) ?? '', $query);
        self::assertSame('sepolia', $query['network'] ?? null);

        $meta = $adapter->lastRequest?->meta() ?? [];
        self::assertInstanceOf(SchemaKey::class, $meta['responseSchemaKey'] ?? null);
        self::assertSame('CurrenciesV2Controller_getNetworkTokens_v2', $meta['responseSchemaKey']->operationId());
    }

    public function testGetConversionRoutes(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'currencyId' => 'USDC',
            'conversionRoutes' => [
                ['symbol' => 'ETH'],
            ],
        ], JSON_THROW_ON_ERROR))], $adapter);

        $routes = $api->getConversionRoutes('USDC', ['networks' => ['sepolia', 'taiko']]);

        self::assertSame('USDC', $routes['currencyId'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/currencies/USDC/conversion-routes', $path);

        $url = $adapter->lastRequest?->url() ?? '';
        self::assertStringContainsString('networks=sepolia%2Ctaiko', $url);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): CurrenciesV2Api
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new CurrenciesV2Api($http);
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
