<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Config;

use RequestSuite\RequestPhpClient\RequestClient;
use RequestSuite\RequestPhpClient\RequestClientFactory;

/**
 * Mirrors TypeScript {@code createRequestClientFromEnv} helper.
 */
final class EnvironmentClientFactory
{
    /**
     * @param array<string, string>|null $env
     */
    public static function createRequestClient(?array $env = null): RequestClient
    {
        return (new self())->createFromEnvironment($env);
    }

    public function __construct(private readonly RequestClientFactory $factory = new RequestClientFactory())
    {
    }

    /**
     * @param array<string, string>|null $env
     */
    public function createFromEnvironment(?array $env = null): RequestClient
    {
        $source = $env ?? $this->gatherEnvironment();

        $baseUrl = $this->firstNonEmpty($source, ['REQUEST_API_URL', 'REQUEST_SDK_BASE_URL']);
        $apiKey = $this->firstNonEmpty($source, ['REQUEST_API_KEY', 'REQUEST_SDK_API_KEY']);
        $clientId = $this->firstNonEmpty($source, ['REQUEST_CLIENT_ID', 'REQUEST_SDK_CLIENT_ID']);

        $options = array_filter(
            [
                'baseUrl' => $baseUrl,
                'apiKey' => $apiKey,
                'clientId' => $clientId,
            ],
            static fn($value) => $value !== null && $value !== ''
        );

            return $this->factory->create($options);
    }

    /**
     * @return array<string, string>
     */
    private function gatherEnvironment(): array
    {
        $keys = [
            'REQUEST_API_URL',
            'REQUEST_SDK_BASE_URL',
            'REQUEST_API_KEY',
            'REQUEST_SDK_API_KEY',
            'REQUEST_CLIENT_ID',
            'REQUEST_SDK_CLIENT_ID',
        ];

        $vars = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
                $vars[$key] = (string) $_ENV[$key];
                continue;
            }

            $value = getenv($key);
            if ($value !== false && $value !== '') {
                $vars[$key] = (string) $value;
            }
        }

        return $vars;
    }

    /**
     * @param array<string, string> $env
     * @param array<int, string> $keys
     */
    private function firstNonEmpty(array $env, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $env)) {
                continue;
            }

            $value = trim($env[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
