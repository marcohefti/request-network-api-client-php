<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Payments;

use InvalidArgumentException;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestQueryBuilder;

final class PaymentsApi extends JsonApi
{
    private const OP_SEARCH = 'PaymentControllerV2_getPayments_v2';

    private RequestQueryBuilder $queryBuilder;

    public function __construct(
        HttpClient $http,
        ?JsonRequestHelper $json = null,
        ?RequestQueryBuilder $queryBuilder = null
    ) {
        parent::__construct($http, $json);
        $this->queryBuilder = $queryBuilder ?? new RequestQueryBuilder();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function search(array $query = [], array $options = []): array
    {
        $requestQuery = $this->queryBuilder->build($query);

        if ($requestQuery === null) {
            throw new InvalidArgumentException('Payment search query must contain at least one filter.');
        }

        /** @var array{operationId: string, method: string, path: string, query: array<string, array<int, bool|float|int|string>|bool|float|int|string>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_SEARCH,
            'method' => 'GET',
            'path' => '/v2/payments',
            'query' => $requestQuery,
            'description' => 'Search payments',
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
}
