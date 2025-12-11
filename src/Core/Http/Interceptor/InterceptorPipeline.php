<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Interceptor;

use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;

final class InterceptorPipeline
{
    /**
     * @param array<int, Interceptor> $interceptors
     * @param callable(PendingRequest): Response $terminal
     * @return callable(PendingRequest): Response
     */
    public function compose(array $interceptors, callable $terminal): callable
    {
        return array_reduce(
            array_reverse($interceptors),
            /**
             * @param callable(PendingRequest): Response $next
             * @param Interceptor $interceptor
             */
            static function (callable $next, Interceptor $interceptor): callable {
                return static function (PendingRequest $request) use ($interceptor, $next): Response {
                    return $interceptor->handle($request, $next);
                };
            },
            $terminal
        );
    }
}
