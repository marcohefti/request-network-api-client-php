<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Payer;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Domains\Payer\V1\PayerV1Api;
use RequestSuite\RequestPhpClient\Domains\Payer\V2\PayerV2Api;

/**
 * Mirrors the TypeScript `createPayerApi` helper by exposing v2 endpoints
 * alongside a legacy alias for v1 behaviour.
 */
final class PayerApi
{
    public PayerV1Api $legacy;

    private PayerV2Api $current;

    public function __construct(PayerV2Api $current, PayerV1Api $legacy)
    {
        $this->current = $current;
        $this->legacy = $legacy;
    }

    public static function create(HttpClient $http): self
    {
        return new self(new PayerV2Api($http), new PayerV1Api($http));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createComplianceData(array $body, array $options = []): array
    {
        return $this->current->createComplianceData($body, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getComplianceStatus(string $clientUserId, array $options = []): array
    {
        return $this->current->getComplianceStatus($clientUserId, $options);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function updateComplianceStatus(string $clientUserId, array $body, array $options = []): array
    {
        return $this->current->updateComplianceStatus($clientUserId, $body, $options);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createPaymentDetails(string $clientUserId, array $body, array $options = []): array
    {
        return $this->current->createPaymentDetails($clientUserId, $body, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getPaymentDetails(string $clientUserId, array $options = []): array
    {
        return $this->current->getPaymentDetails($clientUserId, $options);
    }
}
