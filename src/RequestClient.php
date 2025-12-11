<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient;

use RequestSuite\RequestPhpClient\Core\Config\EnvironmentClientFactory;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Domains\ClientIds\ClientIdsApi;
use RequestSuite\RequestPhpClient\Domains\Currencies\CurrenciesApi;
use RequestSuite\RequestPhpClient\Domains\Currencies\V1\CurrenciesV1Api;
use RequestSuite\RequestPhpClient\Domains\Currencies\V2\CurrenciesV2Api;
use RequestSuite\RequestPhpClient\Domains\Pay\PayApi;
use RequestSuite\RequestPhpClient\Domains\Pay\V1\PayV1Api;
use RequestSuite\RequestPhpClient\Domains\Payer\PayerApi;
use RequestSuite\RequestPhpClient\Domains\Payer\V1\PayerV1Api;
use RequestSuite\RequestPhpClient\Domains\Payer\V2\PayerV2Api;
use RequestSuite\RequestPhpClient\Domains\Payments\PaymentsApi;
use RequestSuite\RequestPhpClient\Domains\Payouts\PayoutsApi;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestsApi;
use RequestSuite\RequestPhpClient\Domains\Requests\V1\RequestsV1Api;

/**
 * Entry point for interacting with the Request Network REST API.
 *
 * Mirrors the TypeScript `createRequestClient` by bundling shared configuration,
 * the low-level HttpClient accessor, and lazily-instantiated domain facades
 * (requests, payouts, payments, payer compliance, pay, client IDs, currencies).
 */
final class RequestClient
{
    private readonly RequestClientConfig $config;

    private readonly HttpClient $http;

    private ?RequestsApi $requestsApi = null;
    private ?RequestsV1Api $requestsV1Api = null;
    private ?PaymentsApi $paymentsApi = null;
    private ?PayoutsApi $payoutsApi = null;
    private ?ClientIdsApi $clientIdsApi = null;
    private ?CurrenciesV1Api $currenciesV1Api = null;
    private ?CurrenciesApi $currenciesApi = null;
    private ?PayerV1Api $payerV1Api = null;
    private ?PayerApi $payerApi = null;
    private ?PayV1Api $payV1Api = null;
    private ?PayApi $payApi = null;

    public function __construct(RequestClientConfig $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http = $http;
    }

    /**
     * Factory helper that mirrors {@link createRequestClient} in the TypeScript SDK.
     *
     * @param array{
     *   baseUrl?: string,
     *   apiKey?: string,
     *   clientId?: string,
     *   origin?: string,
     *   headers?: array<string,string>,
     *   userAgent?: string,
     *   sdk?: array{name: string, version?: string},
     *   httpAdapter?: \RequestSuite\RequestPhpClient\Core\Http\HttpAdapter,
     *   retryPolicy?: \RequestSuite\RequestPhpClient\Core\Retry\RetryPolicy,
     *   interceptors?: array<int, \RequestSuite\RequestPhpClient\Core\Http\Interceptor\Interceptor>,
     *   logger?: callable,
     *   logLevel?: string,
     *   runtimeValidation?: bool|array<string, bool>|\RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig
     * } $options
     */
    public static function create(array $options = []): self
    {
        return (new RequestClientFactory())->create($options);
    }

    /**
     * @param array<string, string>|null $env
     */
    public static function createFromEnv(?array $env = null): self
    {
        /** @var self $client */
        $client = EnvironmentClientFactory::createRequestClient($env);

        return $client;
    }

    public function config(): RequestClientConfig
    {
        return $this->config;
    }

    public function http(): HttpClient
    {
        return $this->http;
    }

    public function requests(): RequestsApi
    {
        return $this->requestsApi ??= new RequestsApi($this->http);
    }

    public function requestsV1(): RequestsV1Api
    {
        return $this->requestsV1Api ??= new RequestsV1Api($this->http);
    }

    public function payouts(): PayoutsApi
    {
        return $this->payoutsApi ??= new PayoutsApi($this->http);
    }

    public function payments(): PaymentsApi
    {
        return $this->paymentsApi ??= new PaymentsApi($this->http);
    }

    public function currencies(): CurrenciesApi
    {
        if ($this->currenciesApi === null) {
            $this->currenciesV1Api ??= new CurrenciesV1Api($this->http);
            $this->currenciesApi = new CurrenciesApi(new CurrenciesV2Api($this->http), $this->currenciesV1Api);
        }

        return $this->currenciesApi;
    }

    public function currenciesV1(): CurrenciesV1Api
    {
        return $this->currenciesV1Api ??= new CurrenciesV1Api($this->http);
    }

    public function clientIds(): ClientIdsApi
    {
        return $this->clientIdsApi ??= new ClientIdsApi($this->http);
    }

    public function payer(): PayerApi
    {
        if ($this->payerApi === null) {
            $this->payerV1Api ??= new PayerV1Api($this->http);
            $this->payerApi = new PayerApi(new PayerV2Api($this->http), $this->payerV1Api);
        }

        return $this->payerApi;
    }

    public function payerV1(): PayerV1Api
    {
        return $this->payerV1Api ??= new PayerV1Api($this->http);
    }

    public function pay(): PayApi
    {
        if ($this->payApi === null) {
            $this->payV1Api ??= new PayV1Api($this->http);
            $this->payApi = new PayApi($this->http, $this->payV1Api);
        }

        return $this->payApi;
    }

    public function payV1(): PayV1Api
    {
        return $this->payV1Api ??= new PayV1Api($this->http);
    }
}
