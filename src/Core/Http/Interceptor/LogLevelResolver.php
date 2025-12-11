<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Interceptor;

final class LogLevelResolver
{
    /**
     * @var array<string, int>
     */
    private const RANK = [
        LogLevel::SILENT => 0,
        LogLevel::ERROR => 1,
        LogLevel::INFO => 2,
        LogLevel::DEBUG => 3,
    ];

    public function normalise(?string $level): string
    {
        $value = strtolower($level ?? LogLevel::INFO);

        return array_key_exists($value, self::RANK) ? $value : LogLevel::INFO;
    }

    public function shouldLog(string $level, string $threshold): bool
    {
        return (self::RANK[$level] ?? 0) >= (self::RANK[$threshold] ?? 0);
    }

    public function thresholdForEvent(string $event): string
    {
        return match ($event) {
            'request:start' => LogLevel::DEBUG,
            'request:error' => LogLevel::ERROR,
            default => LogLevel::INFO,
        };
    }
}
