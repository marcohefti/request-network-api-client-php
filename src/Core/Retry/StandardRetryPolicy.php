<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Retry;

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\RetryConfigFactory;
use Throwable;

final class StandardRetryPolicy implements RetryPolicy
{
    private RetryConfig $config;

    public function __construct(RetryConfig $config)
    {
        $this->config = $config;
    }

    public static function default(): self
    {
        $factory = new RetryConfigFactory();

        return new self($factory->default());
    }

    public function config(): RetryConfig
    {
        return $this->config;
    }

    public function maxAttempts(): int
    {
        return $this->config->maxAttempts;
    }

    public function shouldRetry(int $attempt, PendingRequest $request, ?Response $response, ?Throwable $exception): bool
    {
        if ($attempt >= $this->config->maxAttempts) {
            return false;
        }

        if (! $this->isMethodAllowed($request->method())) {
            return false;
        }

        if ($this->config->shouldRetry !== null) {
            return (bool) ($this->config->shouldRetry)($request, $response, $exception);
        }

        if ($exception !== null && ! $exception instanceof RequestApiException) {
            // Transport-level failures (network, timeouts, etc.) are eligible for retry.
            return true;
        }

        $status = $response?->status() ?? ($exception instanceof RequestApiException ? $exception->statusCode() : null);
        if ($status === null) {
            return false;
        }

        return in_array($status, $this->config->retryStatusCodes, true);
    }

    public function delayMilliseconds(
        int $nextAttempt,
        PendingRequest $request,
        ?Response $response,
        ?Throwable $exception
    ): int {
        if ($nextAttempt <= 1) {
            return 0;
        }

        $retryAfter = $this->extractRetryAfter($response, $exception);
        if ($retryAfter !== null) {
            return $this->clamp($retryAfter);
        }

        $exponent = max(0, $nextAttempt - 2);
        $delay = (int) round($this->config->initialDelayMs * ($this->config->backoffFactor ** $exponent));
        $delay = $this->clamp($delay);

        return $this->applyJitter($delay);
    }

    private function isMethodAllowed(string $method): bool
    {
        return in_array(strtoupper($method), $this->config->allowedMethods, true);
    }

    private function clamp(int $delay): int
    {
        if ($delay < 0) {
            return 0;
        }

        return $delay > $this->config->maxDelayMs ? $this->config->maxDelayMs : $delay;
    }

    private function applyJitter(int $delay): int
    {
        if ($delay <= 0) {
            return 0;
        }

        return match ($this->config->jitter) {
            RetryConfig::JITTER_NONE => $delay,
            RetryConfig::JITTER_HALF => $this->randomInRange((int) floor($delay / 2), $delay),
            RetryConfig::JITTER_FULL => $this->randomInRange(0, $delay),
            default => throw new ConfigurationException('Unsupported jitter strategy provided.'),
        };
    }

    private function randomInRange(int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }

        return random_int($min, $max);
    }

    private function extractRetryAfter(?Response $response, ?Throwable $exception): ?int
    {
        if ($response !== null) {
            $header = $response->header('Retry-After');
            if ($header !== null) {
                $parsed = $this->parseRetryAfterHeader($header);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        if ($exception instanceof RequestApiException) {
            $retryAfter = $exception->retryAfterMs();
            if ($retryAfter !== null) {
                return $retryAfter;
            }
        }

        return null;
    }

    private function parseRetryAfterHeader(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value * 1000;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        $diffMs = ($timestamp - time()) * 1000;

        return $diffMs > 0 ? $diffMs : 0;
    }
}
