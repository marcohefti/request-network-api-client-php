<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Config;

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;

final class RequestClientConfigFactory
{
    private RequestEnvironment $environment;

    public function __construct(?RequestEnvironment $environment = null)
    {
        $this->environment = $environment ?? new RequestEnvironment();
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): RequestClientConfig
    {
        $environment = $this->normalizeEnvironment($input['environment'] ?? null);
        $baseUrl = $this->resolveBaseUrl($input['baseUrl'] ?? null, $environment);
        $headers = $this->normalizeHeaders($input['headers'] ?? []);
        $sdk = $this->normalizeSdk($input['sdk'] ?? null);

        $apiKey = $this->nullableString($input['apiKey'] ?? null);
        $clientId = $this->nullableString($input['clientId'] ?? null);
        $origin = $this->nullableString($input['origin'] ?? null);
        $userAgent = $this->nullableString($input['userAgent'] ?? null);

        return new RequestClientConfig($baseUrl, $environment, $apiKey, $clientId, $origin, $headers, $userAgent, $sdk);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return $string === '' ? null : $string;
    }

    private function normalizeEnvironment(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new ConfigurationException('Environment option must be a string.');
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new ConfigurationException('Environment option cannot be empty.');
        }

        return $this->environment->normalise($trimmed);
    }

    private function resolveBaseUrl(mixed $value, ?string $environment): string
    {
        if ($value !== null) {
            return $this->normalizeBaseUrl($value);
        }

        return $this->environment->baseUrl($environment);
    }

    private function normalizeBaseUrl(mixed $value): string
    {
        $baseUrl = rtrim((string) $value, '/');
        if ($baseUrl === '') {
            throw new ConfigurationException('Base URL cannot be empty.');
        }

        return $baseUrl;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(mixed $headers): array
    {
        if ($headers === null || $headers === []) {
            return [];
        }

        if (! is_array($headers)) {
            throw new ConfigurationException('Headers option must be an associative array.');
        }

        $out = [];
        foreach ($headers as $key => $value) {
            if (! is_string($key) || $key === '') {
                throw new ConfigurationException('Header keys must be non-empty strings.');
            }
            $out[$key] = (string) $value;
        }

        return $out;
    }

    /**
     * @return array{name: string, version?: string}|null
     */
    private function normalizeSdk(mixed $sdk): ?array
    {
        if ($sdk === null) {
            return null;
        }

        if (! is_array($sdk)) {
            throw new ConfigurationException('sdk option must be an array with name/version keys.');
        }

        if (! isset($sdk['name']) || ! is_string($sdk['name']) || $sdk['name'] === '') {
            throw new ConfigurationException('sdk.name must be a non-empty string.');
        }

        $out = ['name' => $sdk['name']];
        if (isset($sdk['version'])) {
            $out['version'] = (string) $sdk['version'];
        }

        return $out;
    }
}
