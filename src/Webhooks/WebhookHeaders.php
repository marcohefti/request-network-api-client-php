<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use Psr\Http\Message\MessageInterface;
use Stringable;

final class WebhookHeaders
{
    /**
     * @var array<string, string>
     */
    private array $normalised;

    /**
     * @param array<string, mixed>|MessageInterface|null $headers
     */
    public function __construct(private readonly array|MessageInterface|null $headers)
    {
        $this->normalised = $this->normaliseHeaders($headers);
    }

    /**
     * @return array<string, mixed>|MessageInterface|null
     */
    public function raw(): array|MessageInterface|null
    {
        return $this->headers;
    }

    /**
     * @return array<string, string>
     */
    public function normalised(): array
    {
        return $this->normalised;
    }

    public function pick(string $headerName): ?string
    {
        return $this->pickFromSource($this->headers, $headerName)
            ?? $this->pickFromArray($this->normalised, $headerName);
    }

    public function pickFromNormalised(string $headerName): ?string
    {
        return $this->pickFromArray($this->normalised, $headerName);
    }

    /**
     * @param array<string, mixed>|MessageInterface|null $headers
     * @return array<string, string>
     */
    private function normaliseHeaders(array|MessageInterface|null $headers): array
    {
        $result = [];
        if ($headers === null) {
            return $result;
        }

        if ($headers instanceof MessageInterface) {
            foreach ($headers->getHeaders() as $key => $values) {
                $coerced = $this->coerceHeaderValue($values);
                if ($coerced !== null) {
                    $result[strtolower($key)] = $coerced;
                }
            }

            return $result;
        }

        foreach ($headers as $key => $value) {
            $coerced = $this->coerceHeaderValue($value);
            if ($coerced !== null) {
                $result[strtolower((string) $key)] = $coerced;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed>|MessageInterface|null $headers
     */
    private function pickFromSource(array|MessageInterface|null $headers, string $headerName): ?string
    {
        if ($headers === null) {
            return null;
        }

        if ($headers instanceof MessageInterface) {
            $value = $headers->getHeaderLine($headerName);
            return $value !== '' ? $value : null;
        }

        $lower = strtolower($headerName);

        $direct = $this->coerceHeaderValue($headers[$headerName] ?? null);
        if ($direct !== null) {
            return $direct;
        }

        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $lower) {
                $coerced = $this->coerceHeaderValue($value);
                if ($coerced !== null) {
                    return $coerced;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $headers
     */
    private function pickFromArray(array $headers, string $headerName): ?string
    {
        $lower = strtolower($headerName);

        if (isset($headers[$lower])) {
            return $headers[$lower];
        }

        return $headers[$headerName] ?? null;
    }

    private function coerceHeaderValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $this->coerceFromArray($value);
        }

        return $this->coerceScalar($value);
    }

    /**
     * @param array<int, mixed> $candidates
     */
    private function coerceFromArray(array $candidates): ?string
    {
        foreach ($candidates as $entry) {
            $coerced = $this->coerceHeaderValue($entry);
            if ($coerced !== null && $coerced !== '') {
                return $coerced;
            }
        }

        return null;
    }

    private function coerceScalar(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (! ($value instanceof Stringable) && ! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
