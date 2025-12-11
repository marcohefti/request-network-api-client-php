<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Currencies;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Currencies\CurrenciesApi;
use RequestSuite\RequestPhpClient\Domains\Currencies\V1\CurrenciesV1Api;
use RequestSuite\RequestPhpClient\Domains\Currencies\V2\CurrenciesV2Api;

final class CurrenciesApiTest extends TestCase
{
    public function testCurrenciesApiDelegatesToV2AndExposesLegacy(): void
    {
        $adapter = new RecordingAdapter([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                ['id' => 'USDC'],
            ], JSON_THROW_ON_ERROR)),
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'currencyId' => 'USDC',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        $api = new CurrenciesApi(new CurrenciesV2Api($http), new CurrenciesV1Api($http));
        $api->list();
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/currencies', $path);

        $routes = $api->legacy->getConversionRoutes('USDC');
        self::assertSame('USDC', $routes['currencyId'] ?? null);
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
