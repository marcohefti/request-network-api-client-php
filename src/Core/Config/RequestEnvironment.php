<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Config;

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;

/**
 * Canonical Request Network environments mirrored from the TypeScript client.
 */
final class RequestEnvironment
{
    public const PRODUCTION = 'production';

    /**
     * @var array<string, string>
     */
    private array $urls;

    /**
     * @param array<string, string> $urls
     */
    public function __construct(array $urls = [
        self::PRODUCTION => 'https://api.request.network',
    ])
    {
        $this->urls = $urls;
    }

    /**
     * @return array<string, string>
     */
    public function urls(): array
    {
        return $this->urls;
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->urls);
    }

    public function baseUrl(?string $environment = null): string
    {
        if ($environment === null) {
            return $this->urls[self::PRODUCTION];
        }

        $name = $this->normalise($environment);

        return $this->urls[$name];
    }

    public function isKnown(string $environment): bool
    {
        $name = strtolower(trim($environment));

        return $name !== '' && array_key_exists($name, $this->urls);
    }

    public function normalise(string $environment): string
    {
        $name = strtolower(trim($environment));
        if ($name === '') {
            throw new ConfigurationException('Environment cannot be empty.');
        }

        if (! array_key_exists($name, $this->urls)) {
            throw new ConfigurationException(sprintf('Unknown Request environment "%s".', $environment));
        }

        return $name;
    }
}
