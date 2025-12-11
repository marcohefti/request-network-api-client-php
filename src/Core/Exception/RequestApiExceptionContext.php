<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Exception;

/**
 * Lightweight container that exposes optional API error context (payload, headers, meta).
 */
final class RequestApiExceptionContext
{
    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string|null>|null $headers
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        private readonly ?array $payload,
        private readonly ?array $headers,
        private readonly ?array $meta
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(): ?array
    {
        return $this->payload;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function headers(): ?array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function meta(): ?array
    {
        return $this->meta;
    }
}
