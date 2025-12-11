<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Interceptor;

use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use Throwable;

final class LoggingInterceptor implements Interceptor
{
    /**
     * @var callable(string, array<string, mixed>): void
     */
    private $logger;

    private string $level;

    private LogLevelResolver $resolver;

    /**
     * @param callable(string, array<string, mixed>): void $logger
     */
    public function __construct(callable $logger, string $level, ?LogLevelResolver $resolver = null)
    {
        $this->logger = $logger;
        $this->resolver = $resolver ?? new LogLevelResolver();
        $this->level = $this->resolver->normalise($level);
    }

    public function handle(PendingRequest $request, callable $next): Response
    {
        $start = microtime(true);
        $this->emit('request:start', [
            'method' => $request->method(),
            'url' => $request->url(),
            'meta' => $request->meta(),
        ]);

        try {
            $response = $next($request);
            $this->emit('request:response', [
                'method' => $request->method(),
                'url' => $request->url(),
                'status' => $response->status(),
                'durationMs' => $this->durationSince($start),
                'meta' => $request->meta(),
            ]);

            return $response;
        } catch (Throwable $exception) {
            $this->emit('request:error', [
                'method' => $request->method(),
                'url' => $request->url(),
                'durationMs' => $this->durationSince($start),
                'error' => $exception,
                'meta' => $request->meta(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function emit(string $event, array $meta): void
    {
        $threshold = $this->resolver->thresholdForEvent($event);

        if (! $this->resolver->shouldLog($this->level, $threshold)) {
            return;
        }

        ($this->logger)($event, $meta);
    }

    private function durationSince(float $start): float
    {
        return (microtime(true) - $start) * 1000;
    }
}
