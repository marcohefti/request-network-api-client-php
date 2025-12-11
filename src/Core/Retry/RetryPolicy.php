<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Retry;

use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use Throwable;

interface RetryPolicy
{
    public function maxAttempts(): int;

    public function shouldRetry(
        int $attempt,
        PendingRequest $request,
        ?Response $response,
        ?Throwable $exception
    ): bool;

    public function delayMilliseconds(
        int $nextAttempt,
        PendingRequest $request,
        ?Response $response,
        ?Throwable $exception
    ): int;
}
