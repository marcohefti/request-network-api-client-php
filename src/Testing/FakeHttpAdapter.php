<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Testing;

use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RuntimeException;

use function array_merge;
use function array_shift;
use function is_array;
use function is_callable;
use function is_string;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Fake HTTP adapter for PHPUnit and local harnesses.
 *
 * Queue `Response` objects or callables that derive a response from the
 * captured `PendingRequest`. Every outbound request is recorded so tests can
 * assert payloads, headers, or meta (similar to Mollie's `assertSent`).
 */
final class FakeHttpAdapter implements HttpAdapter
{
    /**
     * @var array<int, Response|callable(PendingRequest): Response|array<string, mixed>>
     */
    private array $queue = [];

    /**
     * @var list<PendingRequest>
     */
    private array $sent = [];

    /**
     * @param array<int, Response|callable(PendingRequest): Response|array<string, mixed>> $responses
     */
    public function __construct(array $responses = [])
    {
        foreach ($responses as $response) {
            $this->queueResponse($response);
        }
    }

    /**
     * @param Response|callable(PendingRequest): Response|array{
     *     status?: int,
     *     headers?: array<string, string>,
     *     body?: string|array<string, mixed>
     * } $response
     */
    public function queueResponse(Response|callable|array $response): self
    {
        $this->queue[] = $response;

        return $this;
    }

    public function send(PendingRequest $request): Response
    {
        $this->sent[] = $request;

        if ($this->queue === []) {
            throw new RuntimeException('FakeHttpAdapter response queue is empty.');
        }

        $next = array_shift($this->queue);

        return $this->normaliseResponse($next, $request);
    }

    public function description(): string
    {
        return 'fake-http-adapter';
    }

    /**
     * @return list<PendingRequest>
     */
    public function sentRequests(): array
    {
        return $this->sent;
    }

    public function assertNothingSent(string $message = 'Expected no requests to be sent.'): void
    {
        if ($this->sent !== []) {
            throw new RuntimeException($message);
        }
    }

    /**
     * Ensure at least one captured request satisfies the predicate.
     *
     * @param callable(PendingRequest): bool $predicate
     */
    public function assertSent(callable $predicate, string $message = 'No matching request was sent.'): void
    {
        foreach ($this->sent as $request) {
            if ($predicate($request)) {
                return;
            }
        }

        throw new RuntimeException($message);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    public static function jsonResponse(array $body, int $status = 200, array $headers = []): Response
    {
        $mergedHeaders = array_merge(['content-type' => 'application/json'], $headers);

        return new Response(
            $status,
            $mergedHeaders,
            (string) json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param Response|callable(PendingRequest): Response|array{
     *     status?: int,
     *     headers?: array<string, string>,
     *     body?: string|array<string, mixed>
     * } $next
     */
    private function normaliseResponse(Response|callable|array $next, PendingRequest $request): Response
    {
        if ($next instanceof Response) {
            return $next;
        }

        if (is_callable($next)) {
            $response = $next($request);
            if (! $response instanceof Response) {
                throw new RuntimeException('FakeHttpAdapter callable must return a Response instance.');
            }

            return $response;
        }

        $status = (int) ($next['status'] ?? 200);
        $headers = (array) ($next['headers'] ?? []);
        $body = $next['body'] ?? '';
        if (is_array($body)) {
            $body = (string) json_encode($body, JSON_THROW_ON_ERROR);
            $headers = array_merge(['content-type' => 'application/json'], $headers);
        } elseif (! is_string($body)) {
            $body = (string) $body;
        }

        return new Response($status, $headers, $body);
    }
}
