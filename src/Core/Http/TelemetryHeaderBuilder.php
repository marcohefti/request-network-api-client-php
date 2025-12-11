<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

/**
 * Builds telemetry headers (user-agent + x-sdk) aligned with the TS client.
 */
final class TelemetryHeaderBuilder
{
    /**
     * @param array{name: string, version?: string}|null $sdk
     *
     * @return array<string, string>
     */
    public function build(?string $userAgent, ?array $sdk): array
    {
        $headers = [];

        $userAgentValue = $this->trim($userAgent);
        if ($userAgentValue !== null) {
            $headers['user-agent'] = $userAgentValue;
        }

        $sdkHeader = $this->buildSdkHeader($sdk);
        if ($sdkHeader !== null) {
            $headers['x-sdk'] = $sdkHeader;
        }

        return $headers;
    }

    /**
     * @param array{name: string, version?: string}|null $sdk
     */
    private function buildSdkHeader(?array $sdk): ?string
    {
        if ($sdk === null) {
            return null;
        }

        $name = $this->trim($sdk['name'] ?? null);
        if ($name === null) {
            return null;
        }

        $version = $this->trim($sdk['version'] ?? null);
        if ($version === null) {
            return $name;
        }

        return sprintf('%s/%s', $name, $version);
    }

    private function trim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
