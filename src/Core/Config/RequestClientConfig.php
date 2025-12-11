<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Config;

/**
 * Immutable configuration object backing the Request PHP client.
 */
final class RequestClientConfig
{
    private string $baseUrl;

    private ?string $environment;

    private ?string $apiKey;

    private ?string $clientId;

    private ?string $origin;

    /**
     * @var array<string, string>
     */
    private array $headers;

    private ?string $userAgent;

    /**
     * @var array{name: string, version?: string}|null
     */
    private ?array $sdk;

    /**
     * @param array<string, string> $headers
     * @param array{name: string, version?: string}|null $sdk
     */
    public function __construct(
        string $baseUrl,
        ?string $environment,
        ?string $apiKey,
        ?string $clientId,
        ?string $origin,
        array $headers,
        ?string $userAgent,
        ?array $sdk
    ) {
        $this->baseUrl = $baseUrl;
        $this->environment = $environment;
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->origin = $origin;
        $this->headers = $headers;
        $this->userAgent = $userAgent;
        $this->sdk = $sdk;
    }

    /**
     * @param array<string, mixed> $input
     * @deprecated Prefer RequestClientConfigFactory::create().
     */
    public static function fromArray(array $input): self
    {
        $factory = new RequestClientConfigFactory();

        return $factory->create($input);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function environment(): ?string
    {
        return $this->environment;
    }

    public function apiKey(): ?string
    {
        return $this->apiKey;
    }

    public function clientId(): ?string
    {
        return $this->clientId;
    }

    public function origin(): ?string
    {
        return $this->origin;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @return array{name: string, version?: string}|null
     */
    public function sdk(): ?array
    {
        return $this->sdk;
    }
}
