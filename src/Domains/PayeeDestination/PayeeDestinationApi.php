<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\PayeeDestination;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class PayeeDestinationApi extends JsonApi
{
    private const PATH_BASE = '/v2/payee-destination';
    private const OP_GET_SIGNING_DATA = 'PayeeDestinationController_getSigningData_v2';
    private const OP_GET_ACTIVE = 'PayeeDestinationController_getActivePayeeDestination_v2';
    private const OP_CREATE = 'PayeeDestinationController_createPayeeDestination_v2';
    private const OP_GET_BY_ID = 'PayeeDestinationController_getPayeeDestination_v2';
    private const OP_DEACTIVATE = 'PayeeDestinationController_deactivatePayeeDestination_v2';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array{walletAddress: string, action: 'add'|'deactivate', tokenAddress: string, chainId: string} $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function getSigningData(array $query, array $options = []): ?array
    {
        /** @var array{operationId: string, method: string, path: string, query: array{walletAddress: string, action: 'add'|'deactivate', tokenAddress: string, chainId: string}, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_GET_SIGNING_DATA,
            'method' => 'GET',
            'path' => self::PATH_BASE . '/signing-data',
            'query' => $query,
            'description' => 'Get payee destination signing data',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_GET_SIGNING_DATA, 200),
            ],
        ];

        /** @var array<string, mixed>|null $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function getActive(string $walletAddress, array $options = []): ?array
    {
        /** @var array{operationId: string, method: string, path: string, query: array{walletAddress: string}, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_GET_ACTIVE,
            'method' => 'GET',
            'path' => self::PATH_BASE,
            'query' => ['walletAddress' => $walletAddress],
            'description' => 'Get active payee destination',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_GET_ACTIVE, 200),
            ],
        ];

        /** @var array<string, mixed>|null $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
    }

    /**
     * @param array{signature: string, nonce: string} $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function create(array $body, array $options = []): ?array
    {
        /** @var array{operationId: string, method: string, path: string, body: array{signature: string, nonce: string}, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_CREATE,
            'method' => 'POST',
            'path' => self::PATH_BASE,
            'body' => $body,
            'description' => 'Create payee destination',
            'meta' => [
                'requestSchemaKey' => $this->schema()->request(self::OP_CREATE, 'application/json'),
                'responseSchemaKey' => $this->schema()->response(self::OP_CREATE, 201),
            ],
        ];

        /** @var array<string, mixed>|null $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function getById(string $destinationId, array $options = []): ?array
    {
        $path = $this->buildPath(self::PATH_BASE . '/{destinationId}', ['destinationId' => $destinationId]);

        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_GET_BY_ID,
            'method' => 'GET',
            'path' => $path,
            'description' => 'Get payee destination by ID',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_GET_BY_ID, 200),
            ],
        ];

        /** @var array<string, mixed>|null $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
    }

    /**
     * @param array{signature: string, nonce: string} $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function deactivate(string $destinationId, array $body, array $options = []): ?array
    {
        $path = $this->buildPath(self::PATH_BASE . '/{destinationId}', ['destinationId' => $destinationId]);

        /** @var array{operationId: string, method: string, path: string, body: array{signature: string, nonce: string}, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_DEACTIVATE,
            'method' => 'DELETE',
            'path' => $path,
            'body' => $body,
            'description' => 'Deactivate payee destination',
            'meta' => [
                'requestSchemaKey' => $this->schema()->request(self::OP_DEACTIVATE, 'application/json'),
                'responseSchemaKey' => $this->schema()->response(self::OP_DEACTIVATE, 200),
            ],
        ];

        /** @var array<string, mixed>|null $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
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
