<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Currencies;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Domains\Currencies\V1\CurrenciesV1Api;
use RequestSuite\RequestPhpClient\Domains\Currencies\V2\CurrenciesV2Api;

/**
 * Mirrors the TypeScript `createCurrenciesApi` helper by exposing v2 endpoints
 * and a legacy alias for the v1 facade.
 */
final class CurrenciesApi
{
    public CurrenciesV1Api $legacy;

    private CurrenciesV2Api $current;

    public function __construct(CurrenciesV2Api $current, CurrenciesV1Api $legacy)
    {
        $this->current = $current;
        $this->legacy = $legacy;
    }

    public static function create(HttpClient $http): self
    {
        return new self(new CurrenciesV2Api($http), new CurrenciesV1Api($http));
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function list(?array $query = null, array $options = []): array
    {
        return $this->current->list($query, $options);
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getConversionRoutes(string $currencyId, ?array $query = null, array $options = []): array
    {
        return $this->current->getConversionRoutes($currencyId, $query, $options);
    }
}
