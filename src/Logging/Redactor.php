<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Logging;

use function is_array;

final class Redactor
{
    public function __construct()
    {
        $this->sensitiveKeys = self::SENSITIVE_KEYS;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function redactContext(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            if ($this->shouldRedact($key)) {
                $redacted[$key] = self::REDACTED_PLACEHOLDER;
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redactContext($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    public function redactValue(string $key, mixed $value): mixed
    {
        if ($this->shouldRedact($key)) {
            return self::REDACTED_PLACEHOLDER;
        }

        if (is_array($value)) {
            return $this->redactContext($value);
        }

        return $value;
    }

    private const REDACTED_PLACEHOLDER = '***REDACTED***';

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'authorization',
        'x-api-key',
        'x-client-id',
        'x-request-network-signature',
        'x-request-network-secret',
        'signature',
        'secret',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys;

    private function shouldRedact(string $key): bool
    {
        $lower = strtolower($key);

        foreach ($this->sensitiveKeys as $candidate) {
            if ($lower === $candidate) {
                return true;
            }
        }

        return false;
    }
}
