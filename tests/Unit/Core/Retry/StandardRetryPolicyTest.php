<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Retry;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\RetryConfig;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

final class StandardRetryPolicyTest extends TestCase
{
    public function testShouldRetryForServerError(): void
    {
        $policy = $this->policy();
        $pending = $this->pending('GET');
        $response = new Response(500, [], '');

        self::assertTrue($policy->shouldRetry(1, $pending, $response, null));
        self::assertFalse($policy->shouldRetry(3, $pending, $response, null));
    }

    public function testSkipsRetryForDisallowedMethod(): void
    {
        $policy = $this->policy();
        $pending = $this->pending('POST');
        $response = new Response(500, [], '');

        self::assertFalse($policy->shouldRetry(1, $pending, $response, null));
    }

    public function testDelayUsesRetryAfterHeaderWhenPresent(): void
    {
        $policy = $this->policy();
        $pending = $this->pending('GET');
        $response = new Response(429, ['Retry-After' => '120'], '');

        $delayWithHeader = $policy->delayMilliseconds(2, $pending, $response, null);
        $delayWithoutHeader = $policy->delayMilliseconds(2, $pending, new Response(429, [], ''), null);

        self::assertSame(5_000, $delayWithHeader);
        self::assertGreaterThan($delayWithoutHeader, $delayWithHeader);
    }

    public function testDelayFallsBackToExponentialBackoff(): void
    {
        $policy = $this->policy();
        $pending = $this->pending('GET');
        $response = new Response(500, [], '');

        $delayFirstRetry = $policy->delayMilliseconds(2, $pending, $response, null);
        $delaySecondRetry = $policy->delayMilliseconds(3, $pending, $response, null);

        self::assertSame(250, $delayFirstRetry);
        self::assertSame(500, $delaySecondRetry);
    }

    public function testRetryAfterFromExceptionIsRespected(): void
    {
        $policy = $this->policy();
        $pending = $this->pending('GET');
        $exception = new RequestApiException('error', 503, null, null, null, null, 60000);

        self::assertTrue($policy->shouldRetry(1, $pending, null, $exception));
        $delay = $policy->delayMilliseconds(2, $pending, null, $exception);
        self::assertSame(5_000, $delay);
    }

    private function policy(): StandardRetryPolicy
    {
        return new StandardRetryPolicy(
            new RetryConfig(
                3,
                250,
                5_000,
                2.0,
                RetryConfig::JITTER_NONE,
                [408, 429, 500, 503],
                ['GET', 'HEAD']
            )
        );
    }

    private function pending(string $method)
    {
        $config = RequestClientConfig::fromArray([]);

        return new PendingRequest(
            $config,
            new RequestOptions($method, '/resource')
        );
    }
}
