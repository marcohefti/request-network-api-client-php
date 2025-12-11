<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Currencies\V1;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class CurrenciesV1Api extends JsonApi
{
    private const PATH_BASE = '/v1/currencies';
    private const CONVERSION_ROUTES_SEGMENT = 'conversion-routes';
    private const OP_LIST = 'CurrenciesV1Controller_getNetworkTokens_v1';
    private const OP_CONVERSION_ROUTES = 'CurrenciesV1Controller_getConversionRoutes_v1';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function list(?array $query = null, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, query?: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_LIST,
            'method' => 'GET',
            'path' => self::PATH_BASE,
            'query' => $this->normaliseQuery($query),
            'description' => 'Legacy currencies list',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_LIST, 200),
            ],
        ];

        $payload = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        if (is_array($payload) && $this->isCurrencyArray($payload)) {
            return $payload;
        }

        if (is_array($payload)) {
            return [$payload];
        }

        return [];
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getConversionRoutes(string $currencyId, ?array $query = null, array $options = []): array
    {
        $path = $this->buildPath(sprintf('%s/{id}/%s', self::PATH_BASE, self::CONVERSION_ROUTES_SEGMENT), [
            'id' => $currencyId,
        ]);

        /** @var array{operationId: string, method: string, path: string, query?: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_CONVERSION_ROUTES,
            'method' => 'GET',
            'path' => $path,
            'query' => $this->normaliseQuery($query),
            'description' => 'Legacy conversion routes',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_CONVERSION_ROUTES, 200),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @template T of array<string, mixed>
     * @param T $request
     * @param array<string, mixed> $options
     * @return T
     */
    private function applyOptions(array $request, array $options): array
    {
        if (isset($options['timeoutMs'])) {
            $request['timeoutMs'] = (int) $options['timeoutMs'];
        }

        if (isset($options['meta']) && is_array($options['meta'])) {
            $request['meta'] = array_merge($request['meta'] ?? [], $options['meta']);
        }

        if (array_key_exists('validation', $options)) {
            $meta = $request['meta'] ?? [];
            $meta['validation'] = $options['validation'];
            $request['meta'] = $meta;
        }

        return $request;
    }

    /**
     * @param array<string, mixed>|null $query
     * @return array<string, mixed>|null
     */
    private function normaliseQuery(?array $query): ?array
    {
        if ($query === null) {
            return null;
        }

        $result = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $filtered = array_values(array_filter(
                    $value,
                    static fn ($item): bool => $item !== null
                ));

                if ($filtered === []) {
                    continue;
                }

                $result[(string) $key] = $filtered;
                continue;
            }

            $result[(string) $key] = $value;
        }

        return $result === [] ? null : $result;
    }

    /**
     * @param array<mixed> $payload
     */
    private function isCurrencyArray(array $payload): bool
    {
        if ($payload === []) {
            return true;
        }

        return array_is_list($payload) && array_reduce(
            $payload,
            static fn (bool $carry, mixed $item): bool => $carry && is_array($item),
            true
        );
    }
}
