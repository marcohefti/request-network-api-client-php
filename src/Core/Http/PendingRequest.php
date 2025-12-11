<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

use RequestSuite\RequestPhpClient\Core\Auth\CredentialHeaderBuilder;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\TelemetryHeaderBuilder;

final class PendingRequest
{
    private CredentialHeaderBuilder $credentialHeaders;
    private TelemetryHeaderBuilder $telemetryHeaders;
    private HeaderBag $headerBag;
    private QueryStringBuilder $queryStringBuilder;

    public function __construct(
        private readonly RequestClientConfig $config,
        private readonly RequestOptions $options,
        ?CredentialHeaderBuilder $credentialBuilder = null,
        ?TelemetryHeaderBuilder $telemetryBuilder = null,
        ?HeaderBag $headerBag = null,
        ?QueryStringBuilder $queryBuilder = null
    ) {
        $this->credentialHeaders = $credentialBuilder ?? new CredentialHeaderBuilder();
        $this->telemetryHeaders = $telemetryBuilder ?? new TelemetryHeaderBuilder();
        $this->headerBag = $headerBag ?? new HeaderBag();
        $this->queryStringBuilder = $queryBuilder ?? new QueryStringBuilder();
    }

    public function method(): string
    {
        return $this->options->method();
    }

    public function url(): string
    {
        $base = rtrim($this->config->baseUrl(), '/');
        $path = '/' . ltrim($this->options->path(), '/');
        $url = $base . $path;

        $query = $this->queryStringBuilder->build(
            $this->options->query(),
            $this->options->querySerializer()
        );
        if ($query !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headerBag->merge(
            $this->credentialHeaders->build([
                'apiKey' => $this->config->apiKey(),
                'clientId' => $this->config->clientId(),
                'origin' => $this->config->origin(),
            ]),
            $this->telemetryHeaders->build($this->config->userAgent(), $this->config->sdk()),
            $this->config->headers(),
            $this->options->headers()
        );
    }

    /**
     * @return string|array<string, mixed>|null
     */
    public function body(): string|array|null
    {
        return $this->options->body();
    }

    public function timeoutMs(): ?int
    {
        return $this->options->timeoutMs();
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->options->meta();
    }
}
