<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests;

final class RequestStatusCustomerInfo
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?RequestStatusAddress $address = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'address' => $this->address?->toArray(),
        ];
    }
}
