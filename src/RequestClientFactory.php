<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient;

use Psr\Log\LoggerInterface;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfigFactory;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Http\Adapter\CurlHttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Retry\RetryConfigFactory;
use RequestSuite\RequestPhpClient\Core\Retry\RetryPolicy;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Logging\PsrLoggerAdapter;

final class RequestClientFactory
{
    public function __construct(
        private readonly RequestClientConfigFactory $configFactory = new RequestClientConfigFactory(),
        private readonly RetryConfigFactory $retryConfigFactory = new RetryConfigFactory()
    ) {
    }

    /**
     * @param array{
     *   baseUrl?: string,
     *   apiKey?: string,
     *   clientId?: string,
     *   origin?: string,
     *   headers?: array<string,string>,
     *   userAgent?: string,
     *   sdk?: array{name: string, version?: string},
     *   httpAdapter?: HttpAdapter,
     *   retryPolicy?: RetryPolicy,
     *   interceptors?: array<int, \RequestSuite\RequestPhpClient\Core\Http\Interceptor\Interceptor>,
     *   logger?: callable|LoggerInterface,
     *   logLevel?: string,
     *   runtimeValidation?: bool|array<string, bool>|\RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig
     * } $options
     */
    public function create(array $options = []): RequestClient
    {
        $config = $this->configFactory->create($options);

        $adapter = $options['httpAdapter'] ?? new CurlHttpAdapter();
        if (! $adapter instanceof HttpAdapter) {
            throw new ConfigurationException('The provided httpAdapter must implement HttpAdapter.');
        }

        $retryPolicy = $options['retryPolicy'] ?? new StandardRetryPolicy($this->retryConfigFactory->default());
        if (! $retryPolicy instanceof RetryPolicy) {
            throw new ConfigurationException('The provided retryPolicy must implement RetryPolicy.');
        }

        $interceptors = $options['interceptors'] ?? [];
        if ($interceptors !== [] && ! is_array($interceptors)) {
            throw new ConfigurationException('Interceptors option must be an array.');
        }

        $logger = $options['logger'] ?? null;
        if ($logger instanceof LoggerInterface) {
            $defaultLevel = $options['logLevel'] ?? 'info';
            $logger = new PsrLoggerAdapter($logger, $defaultLevel);
        } elseif ($logger !== null && ! is_callable($logger)) {
            throw new ConfigurationException('Logger must be a callable or PSR-3 logger instance.');
        }

        $http = new HttpClient(
            $config,
            $adapter,
            $retryPolicy,
            $interceptors,
            $logger,
            $options['logLevel'] ?? null,
            $options['runtimeValidation'] ?? null
        );

        return new RequestClient($config, $http);
    }
}
