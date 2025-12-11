<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Interceptor;

use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;

interface Interceptor
{
    /**
     * @param callable(PendingRequest): Response $next
     */
    public function handle(PendingRequest $request, callable $next): Response;
}
