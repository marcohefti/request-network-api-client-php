<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Pay;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Domains\Pay\V1\PayV1Api;

/**
 * Mirrors the TypeScript `createPayApi` helper by exposing legacy pay endpoints
 * plus a `legacy` alias pointing at the underlying facade.
 */
final class PayApi
{
    public PayV1Api $legacy;

    public function __construct(HttpClient $http, ?PayV1Api $legacy = null)
    {
        $this->legacy = $legacy ?? new PayV1Api($http);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function payRequest(array $body, array $options = []): array
    {
        return $this->legacy->payRequest($body, $options);
    }
}
