<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

use Stringable;

final class WebhookEventData
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function string(string $key, ?string $fallbackKey = null): ?string
    {
        $value = $this->payload[$key] ?? null;
        if ($value === null && $fallbackKey !== null) {
            $value = $this->payload[$fallbackKey] ?? null;
        }

        return $this->coerceString($value);
    }

    public function bool(string $key): ?bool
    {
        $value = $this->payload[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOfArrays(string $key): array
    {
        $value = $this->payload[$key] ?? [];
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn ($entry): bool => is_array($entry)
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function array(string $key): ?array
    {
        $value = $this->payload[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    private function coerceString(mixed $value): ?string
    {
        if (! ($value instanceof Stringable) && ! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
