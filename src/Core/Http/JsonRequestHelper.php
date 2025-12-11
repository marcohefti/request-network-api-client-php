<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

use JsonException;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiErrorBuilder;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;

/**
 * Helper for JSON-centric requests that mirrors the TypeScript operation helper.
 */
final class JsonRequestHelper
{
    public function __construct(
        private readonly RequestApiErrorBuilder $errorBuilder = new RequestApiErrorBuilder()
    ) {
    }

    /**
     * @param array{
     *   operationId: string,
     *   method: string,
     *   path: string,
     *   query?: array<string, scalar|array<int, scalar>|null>,
     *   body?: array<string, mixed>|string|null,
     *   headers?: array<string, string>,
     *   timeoutMs?: int|null,
     *   querySerializer?: 'comma'|'repeat'|callable|null,
     *   meta?: array<string, mixed>,
     *   description?: string|null
     * } $options
     */
    public function requestJson(HttpClient $http, array $options): mixed
    {
        $response = $this->dispatch($http, $options);

        if ($response->status() >= 400) {
            $payload = $this->decodeIfJson($response->body());
            $message = sprintf(
                '%s request failed with status %d',
                $options['operationId'],
                $response->status()
            );

            throw $this->errorBuilder->build(
                $payload,
                $response->status(),
                $response->headers(),
                $message,
                $this->buildErrorMeta($options)
            );
        }

        $body = $response->body();
        if ($body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (JsonException $exception) {
            $message = sprintf(
                'Unable to decode JSON response for %s (%s)',
                $options['operationId'],
                $exception->getMessage()
            );

            throw new RequestApiException(
                $message,
                $response->status(),
                null,
                $response->header('x-request-id'),
                $response->header('x-correlation-id'),
                null,
                null,
                $exception
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function requestVoid(HttpClient $http, array $options): void
    {
        $response = $this->dispatch($http, $options);

        if ($response->status() >= 400) {
            $message = sprintf(
                '%s request failed with status %d',
                $options['operationId'],
                $response->status()
            );

            throw $this->errorBuilder->build(
                null,
                $response->status(),
                $response->headers(),
                $message,
                $this->buildErrorMeta($options)
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function dispatch(HttpClient $http, array $options): Response
    {
        $headers = $options['headers'] ?? [];

        if (! $this->hasHeader($headers, 'Accept')) {
            $headers['Accept'] = 'application/json';
        }

        $body = $options['body'] ?? null;
        $encodedBody = $this->encodeBody($body, $headers);

        $meta = $options['meta'] ?? [];
        $meta['operationId'] = $options['operationId'];

        $request = new RequestOptions(
            $options['method'],
            $options['path'],
            $options['query'] ?? [],
            $headers,
            $encodedBody,
            $options['timeoutMs'] ?? null,
            $options['querySerializer'] ?? null,
            $meta
        );

        return $http->request($request);
    }

    /**
     * @param array<string, string> $headers
     */
    private function encodeBody(mixed $body, array &$headers): ?string
    {
        if ($body === null) {
            return null;
        }

        if (is_array($body)) {
            try {
                $encoded = json_encode($body, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RequestApiException(
                    'Unable to encode JSON request body.',
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $exception
                );
            }

            if (! $this->hasHeader($headers, 'Content-Type')) {
                $headers['Content-Type'] = 'application/json';
            }

            return $encoded;
        }

        return (string) $body;
    }

    /**
     * @param array<string, string> $headers
     */
    private function hasHeader(array $headers, string $name): bool
    {
        foreach (array_keys($headers) as $key) {
            if (strcasecmp($key, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeIfJson(string $payload): ?array
    {
        if ($payload === '') {
            return null;
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    private function buildErrorMeta(array $options): ?array
    {
        $meta = [
            'operationId' => $options['operationId'] ?? null,
            'description' => $options['description'] ?? null,
        ];

        $filtered = array_filter(
            $meta,
            static fn($value): bool => $value !== null
        );

        return $filtered === [] ? null : $filtered;
    }
}
