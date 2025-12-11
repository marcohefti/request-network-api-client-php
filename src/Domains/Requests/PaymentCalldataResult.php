<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests;

final class PaymentCalldataResult
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $kind,
        private readonly array $payload
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind] + $this->payload;
    }
}
