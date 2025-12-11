<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\ClientIds;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class ClientIdsApi extends JsonApi
{
    private const PATH_BASE = '/v2/client-ids';
    private const OP_LIST = 'ClientIdV2Controller_findAll_v2';
    private const OP_CREATE = 'ClientIdV2Controller_create_v2';
    private const OP_FIND_ONE = 'ClientIdV2Controller_findOne_v2';
    private const OP_UPDATE = 'ClientIdV2Controller_update_v2';
    private const OP_REVOKE = 'ClientIdV2Controller_delete_v2';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function list(array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_LIST,
            'method' => 'GET',
            'path' => self::PATH_BASE,
            'description' => 'List client IDs',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_LIST, 200),
            ],
        ];

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        return $result;
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function create(array $body, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_CREATE,
            'method' => 'POST',
            'path' => self::PATH_BASE,
            'body' => $body,
            'description' => 'Create client ID',
            'meta' => [
                'requestSchemaKey' => $this->schema()->request(self::OP_CREATE, 'application/json'),
                'responseSchemaKey' => $this->schema()->response(self::OP_CREATE, 201),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function findOne(string $clientId, array $options = []): array
    {
        $path = $this->buildPath(self::PATH_BASE . '/{id}', ['id' => $clientId]);

        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_FIND_ONE,
            'method' => 'GET',
            'path' => $path,
            'description' => 'Get client ID',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_FIND_ONE, 200),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function update(string $clientId, array $body, array $options = []): array
    {
        $path = $this->buildPath(self::PATH_BASE . '/{id}', ['id' => $clientId]);

        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_UPDATE,
            'method' => 'PUT',
            'path' => $path,
            'body' => $body,
            'description' => 'Update client ID',
            'meta' => [
                'requestSchemaKey' => $this->schema()->request(self::OP_UPDATE, 'application/json'),
                'responseSchemaKey' => $this->schema()->response(self::OP_UPDATE, 200),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function revoke(string $clientId, array $options = []): void
    {
        $path = $this->buildPath(self::PATH_BASE . '/{id}', ['id' => $clientId]);

        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_REVOKE,
            'method' => 'DELETE',
            'path' => $path,
            'description' => 'Revoke client ID',
            'meta' => [],
        ];

        $this->json->requestVoid($this->http, $this->applyOptions($request, $options));
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
