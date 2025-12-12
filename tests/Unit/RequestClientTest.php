<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Domains\ClientIds\ClientIdsApi;
use RequestSuite\RequestPhpClient\Domains\Currencies\CurrenciesApi;
use RequestSuite\RequestPhpClient\Domains\Currencies\V1\CurrenciesV1Api;
use RequestSuite\RequestPhpClient\Domains\Pay\PayApi;
use RequestSuite\RequestPhpClient\Domains\Pay\V1\PayV1Api;
use RequestSuite\RequestPhpClient\Domains\Payer\PayerApi;
use RequestSuite\RequestPhpClient\Domains\Payer\V1\PayerV1Api;
use RequestSuite\RequestPhpClient\Domains\Payouts\PayoutsApi;
use RequestSuite\RequestPhpClient\Domains\Payments\PaymentsApi;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestsApi;
use RequestSuite\RequestPhpClient\Domains\Requests\V1\RequestsV1Api;
use RequestSuite\RequestPhpClient\RequestClient;

final class RequestClientTest extends TestCase
{
    public function testCreateReturnsInstance(): void
    {
        $client = RequestClient::create(['apiKey' => 'test_123']);

        self::assertSame('https://api.request.network', $client->config()->baseUrl());
    }

    public function testDefaultHttpAdapterIsPlaceholder(): void
    {
        $client = RequestClient::create();

        self::assertInstanceOf(
            \RequestSuite\RequestPhpClient\Core\Http\Adapter\CurlHttpAdapter::class,
            $client->http()->adapter()
        );
    }

    public function testCreateRejectsNonCallableLogger(): void
    {
        $this->expectException(ConfigurationException::class);
        RequestClient::create(['logger' => 'not-callable']);
    }

    public function testRequestClientBundlesAllFacades(): void
    {
        $client = RequestClient::create(['apiKey' => 'key_test']);

        self::assertInstanceOf(HttpClient::class, $client->http());
        self::assertInstanceOf(RequestsApi::class, $client->requests());
        self::assertInstanceOf(RequestsV1Api::class, $client->requestsV1());
        self::assertInstanceOf(PayoutsApi::class, $client->payouts());
        self::assertInstanceOf(PaymentsApi::class, $client->payments());
        self::assertInstanceOf(CurrenciesApi::class, $client->currencies());
        self::assertInstanceOf(CurrenciesV1Api::class, $client->currenciesV1());
        self::assertInstanceOf(ClientIdsApi::class, $client->clientIds());
        self::assertInstanceOf(PayerApi::class, $client->payer());
        self::assertInstanceOf(PayerV1Api::class, $client->payerV1());
        self::assertInstanceOf(PayApi::class, $client->pay());
        self::assertInstanceOf(PayV1Api::class, $client->payV1());

        self::assertSame($client->requests(), $client->requests());
        self::assertSame($client->requestsV1(), $client->requestsV1());
        self::assertSame($client->payouts(), $client->payouts());
        self::assertSame($client->payments(), $client->payments());
        self::assertSame($client->currencies(), $client->currencies());
        self::assertSame($client->currenciesV1(), $client->currenciesV1());
        self::assertSame($client->clientIds(), $client->clientIds());
        self::assertSame($client->payer(), $client->payer());
        self::assertSame($client->payerV1(), $client->payerV1());
        self::assertSame($client->pay(), $client->pay());
        self::assertSame($client->payV1(), $client->payV1());

        self::assertSame($client->currencies()->legacy, $client->currenciesV1());
        self::assertSame($client->payer()->legacy, $client->payerV1());
        self::assertSame($client->pay()->legacy, $client->payV1());
    }
}
