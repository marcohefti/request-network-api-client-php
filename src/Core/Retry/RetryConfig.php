<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Retry;

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;

final class RetryConfig
{
    public const JITTER_NONE = 'none';
    public const JITTER_HALF = 'half';
    public const JITTER_FULL = 'full';

    public int $maxAttempts;
    public int $initialDelayMs;
    public int $maxDelayMs;
    public float $backoffFactor;
    public string $jitter;

    /**
     * @var int[]
     */
    public array $retryStatusCodes;

    /**
     * @var string[]
     */
    public array $allowedMethods;

    /**
     * @var callable|null
     */
    public $shouldRetry;

    /**
     * @param int[] $retryStatusCodes
     * @param string[] $allowedMethods
     * @param callable(
     *   \RequestSuite\RequestPhpClient\Core\Http\PendingRequest,
     *   ?\RequestSuite\RequestPhpClient\Core\Http\Response,
     *   ?\Throwable
     * ): bool|null $shouldRetry
     */
    public function __construct(
        int $maxAttempts,
        int $initialDelayMs,
        int $maxDelayMs,
        float $backoffFactor,
        string $jitter,
        array $retryStatusCodes,
        array $allowedMethods,
        ?callable $shouldRetry = null
    ) {
        if ($maxAttempts < 1) {
            throw new ConfigurationException('Retry maxAttempts must be at least 1.');
        }
        if ($initialDelayMs < 0) {
            throw new ConfigurationException('Retry initialDelayMs cannot be negative.');
        }
        if ($maxDelayMs < $initialDelayMs) {
            throw new ConfigurationException('Retry maxDelayMs must be greater than or equal to initialDelayMs.');
        }
        if ($backoffFactor < 1) {
            throw new ConfigurationException('Retry backoffFactor must be >= 1.');
        }
        $allowedJitter = [self::JITTER_NONE, self::JITTER_HALF, self::JITTER_FULL];
        if (! in_array($jitter, $allowedJitter, true)) {
            throw new ConfigurationException('Retry jitter must be one of: ' . implode(', ', $allowedJitter));
        }

        $this->maxAttempts = $maxAttempts;
        $this->initialDelayMs = $initialDelayMs;
        $this->maxDelayMs = $maxDelayMs;
        $this->backoffFactor = $backoffFactor;
        $this->jitter = $jitter;
        $this->retryStatusCodes = array_values(array_unique($retryStatusCodes));
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);
        $this->shouldRetry = $shouldRetry;
    }

    public function withShouldRetry(callable $callback): self
    {
        $clone = clone $this;
        $clone->shouldRetry = $callback;

        return $clone;
    }
}
