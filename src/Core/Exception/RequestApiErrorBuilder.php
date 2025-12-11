<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Exception;

use Throwable;

final class RequestApiErrorBuilder
{
    public const REQUEST_ID_HEADER = 'x-request-id';
    public const CORRELATION_ID_HEADER = 'x-correlation-id';
    public const RETRY_AFTER_HEADER = 'retry-after';
    private const DEFAULT_MESSAGE = 'Request Network API error';

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string|null>|null $headers
     * @param array<string, mixed>|null $meta
     */
    public function build(
        ?array $payload,
        int $status,
        ?array $headers = null,
        ?string $fallbackMessage = null,
        ?array $meta = null,
        ?Throwable $previous = null
    ): RequestApiException {
        $message = $this->resolveMessage($payload, $fallbackMessage);
        $code = $this->resolveCode($payload, $status);
        $normalisedHeaders = $this->normaliseHeaders($headers);
        $retryAfterMs = $this->parseRetryAfter($normalisedHeaders[self::RETRY_AFTER_HEADER] ?? null);

        $context = new RequestApiExceptionContext($payload, $normalisedHeaders, $this->normaliseMeta($meta));

        return new RequestApiException(
            $message,
            $status,
            $code,
            $normalisedHeaders[self::REQUEST_ID_HEADER] ?? null,
            $normalisedHeaders[self::CORRELATION_ID_HEADER] ?? null,
            $payload,
            $retryAfterMs,
            $previous,
            $context
        );
    }

    public static function isRequestApiError(mixed $value): bool
    {
        if ($value instanceof RequestApiException) {
            return true;
        }

        if (is_array($value) && ($value['name'] ?? null) === RequestApiException::ERROR_NAME) {
            return true;
        }

        if (is_object($value)) {
            $props = get_object_vars($value);

            return isset($props['name']) && $props['name'] === RequestApiException::ERROR_NAME;
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveMessage(?array $payload, ?string $fallback): string
    {
        if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
            return $payload['message'];
        }

        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        return self::DEFAULT_MESSAGE;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveCode(?array $payload, int $status): string
    {
        if (isset($payload['code']) && is_string($payload['code']) && $payload['code'] !== '') {
            return $payload['code'];
        }

        return sprintf('HTTP_%d', $status);
    }

    /**
     * @param array<string, string|null>|null $headers
     * @return array<string, string|null>
     */
    private function normaliseHeaders(?array $headers): array
    {
        return [
            self::REQUEST_ID_HEADER => $this->headerValue($headers, self::REQUEST_ID_HEADER),
            self::CORRELATION_ID_HEADER => $this->headerValue($headers, self::CORRELATION_ID_HEADER),
            self::RETRY_AFTER_HEADER => $this->headerValue($headers, self::RETRY_AFTER_HEADER),
        ];
    }

    /**
     * @param array<string, string|null>|null $headers
     */
    private function headerValue(?array $headers, string $target): ?string
    {
        if ($headers === null) {
            return null;
        }

        foreach ($headers as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            if (strcasecmp($name, $target) === 0) {
                $trimmed = $value === null ? null : trim((string) $value);
                if ($trimmed === '') {
                    return null;
                }

                return $trimmed;
            }
        }

        return null;
    }

    private function parseRetryAfter(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int) $header * 1000;
        }

        $timestamp = strtotime($header);
        if ($timestamp === false) {
            return null;
        }

        $diff = ($timestamp - time()) * 1000;

        return $diff > 0 ? $diff : 0;
    }

    /**
     * @param array<string, mixed>|null $meta
     * @return array<string, mixed>|null
     */
    private function normaliseMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        $filtered = array_filter(
            $meta,
            static fn ($value): bool => $value !== null
        );

        return $filtered === [] ? null : $filtered;
    }
}
