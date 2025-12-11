<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Exception\TransportException;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\Interceptor;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\InterceptorPipeline;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\LoggingInterceptor;
use RequestSuite\RequestPhpClient\Core\Http\Interceptor\LogLevelResolver;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidation;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use RequestSuite\RequestPhpClient\Core\Retry\RetryPolicy;
use Throwable;

final class HttpClient
{
    private RequestClientConfig $config;

    private HttpAdapter $adapter;

    private RetryPolicy $retryPolicy;

    /**
     * @var array<int, Interceptor>
     */
    private array $interceptors;

    /**
     * @var callable(string, array<string, mixed>): void|null
     */
    private $logger;

    private string $logLevel;

    private LogLevelResolver $logLevelResolver;

    private RuntimeValidationConfig $runtimeValidation;

    private RuntimeValidation $validationHelper;

    private InterceptorPipeline $pipelineBuilder;

    /**
     * @param array<int, mixed> $interceptors
     * @param callable(string, array<string, mixed>): void|null $logger
     */
    public function __construct(
        RequestClientConfig $config,
        HttpAdapter $adapter,
        RetryPolicy $retryPolicy,
        array $interceptors = [],
        ?callable $logger = null,
        ?string $logLevel = null,
        mixed $runtimeValidation = null,
        ?LogLevelResolver $logLevelResolver = null
    ) {
        $this->config = $config;
        $this->adapter = $adapter;
        $this->retryPolicy = $retryPolicy;
        $this->interceptors = array_map(function (mixed $interceptor): Interceptor {
            if (! $interceptor instanceof Interceptor) {
                throw new ConfigurationException('Interceptors must implement Interceptor interface.');
            }

            return $interceptor;
        }, $interceptors);
        $this->logger = $logger;
        $this->logLevelResolver = $logLevelResolver ?? new LogLevelResolver();
        $this->logLevel = $this->logLevelResolver->normalise($logLevel);
        $this->validationHelper = new RuntimeValidation();
        $this->pipelineBuilder = new InterceptorPipeline();
        $this->runtimeValidation = $this->validationHelper->normalise($runtimeValidation);
    }

    public function config(): RequestClientConfig
    {
        return $this->config;
    }

    public function adapter(): HttpAdapter
    {
        return $this->adapter;
    }

    public function retryPolicy(): RetryPolicy
    {
        return $this->retryPolicy;
    }

    public function getRuntimeValidationConfig(): RuntimeValidationConfig
    {
        return $this->validationHelper->duplicate($this->runtimeValidation);
    }

    public function request(RequestOptions $options): Response
    {
        $meta = $options->meta();
        $mergedValidation = $this->validationHelper->merge($this->runtimeValidation, $meta['validation'] ?? null);
        $meta['validation'] = $mergedValidation;
        $requestOptions = $options->withMeta($meta);

        $pending = new PendingRequest($this->config, $requestOptions);
        $requestInterceptors = $this->extractInterceptors($requestOptions->meta());

        $baseInterceptors = $this->logger !== null
            ? [new LoggingInterceptor($this->logger, $this->logLevel, $this->logLevelResolver)]
            : [];

        $pipeline = $this->pipelineBuilder->compose(
            array_merge($requestInterceptors, $this->interceptors, $baseInterceptors),
            fn (PendingRequest $request): Response => $this->sendWithRetry($request)
        );

        return $pipeline($pending);
    }

    private function mapException(Throwable $exception): RequestApiException
    {
        if ($exception instanceof RequestApiException) {
            return $exception;
        }

        return new TransportException(
            $exception->getMessage(),
            null,
            null,
            null,
            null,
            null,
            null,
            $exception
        );
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, Interceptor>
     */
    private function extractInterceptors(array $meta): array
    {
        $interceptors = [];
        if (! isset($meta['interceptors'])) {
            return $interceptors;
        }

        $items = $meta['interceptors'];
        if (! is_array($items)) {
            throw new ConfigurationException('Request meta interceptors must be provided as an array.');
        }

        foreach ($items as $interceptor) {
            if (! $interceptor instanceof Interceptor) {
                throw new ConfigurationException('Meta interceptors must implement Interceptor interface.');
            }

            $interceptors[] = $interceptor;
        }

        return $interceptors;
    }

    private function sendWithRetry(PendingRequest $pending): Response
    {
        $attempt = 1;
        $lastError = null;

        while (true) {
            try {
                $response = $this->adapter->send($pending);
                if (! $this->retryPolicy->shouldRetry($attempt, $pending, $response, null)) {
                    return $response;
                }

                $delay = $this->retryPolicy->delayMilliseconds($attempt + 1, $pending, $response, null);
                $this->sleep($delay);
            } catch (Throwable $exception) {
                if (! $this->retryPolicy->shouldRetry($attempt, $pending, null, $exception)) {
                    throw $this->mapException($exception);
                }

                $lastError = $exception;
                $delay = $this->retryPolicy->delayMilliseconds($attempt + 1, $pending, null, $exception);
                $this->sleep($delay);
            }

            $attempt++;

            if ($attempt > $this->retryPolicy->maxAttempts()) {
                break;
            }
        }

        if ($lastError instanceof Throwable) {
            throw $this->mapException($lastError);
        }

        throw new TransportException(
            sprintf('Request failed after %d attempts using adapter %s', $attempt - 1, $this->adapter->description())
        );
    }

    private function sleep(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        usleep($milliseconds * 1000);
    }
}
