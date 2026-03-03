<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\SecurePayments;

use InvalidArgumentException;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class SecurePaymentsApi extends JsonApi
{
    private const PATH_BASE = '/v2/secure-payments';
    private const OP_FIND = 'SecurePaymentController_findSecurePayment_v2';
    private const OP_CREATE = 'SecurePaymentController_createSecurePayment_v2';
    private const OP_GET_BY_TOKEN = 'SecurePaymentController_getSecurePaymentByToken_v2';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function findByRequestId(string $requestId, string $authorization, array $options = []): array
    {
        if (trim($requestId) === '') {
            throw new InvalidArgumentException('requestId must not be empty.');
        }

        if (trim($authorization) === '') {
            throw new InvalidArgumentException('authorization must not be empty.');
        }

        /** @var array{operationId: string, method: string, path: string, query: array{requestId: string}, headers: array{Authorization: string}, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_FIND,
            'method' => 'GET',
            'path' => self::PATH_BASE,
            'query' => ['requestId' => $requestId],
            'headers' => ['Authorization' => $authorization],
            'description' => 'Find secure payment by request ID',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_FIND, 200),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
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
            'description' => 'Create secure payment',
            'meta' => [
                'requestSchemaKey' => $this->schema()->request(self::OP_CREATE, 'application/json'),
                'responseSchemaKey' => $this->schema()->response(self::OP_CREATE, 201),
            ],
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array{wallet?: string}|null $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getByToken(string $token, ?array $query = null, array $options = []): array
    {
        if (trim($token) === '') {
            throw new InvalidArgumentException('token must not be empty.');
        }

        $path = $this->buildPath(self::PATH_BASE . '/{token}', ['token' => $token]);

        /** @var array{operationId: string, method: string, path: string, query?: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_GET_BY_TOKEN,
            'method' => 'GET',
            'path' => $path,
            'query' => $this->normaliseQuery($query),
            'description' => 'Get secure payment by token',
            'meta' => [
                'responseSchemaKey' => $this->schema()->response(self::OP_GET_BY_TOKEN, 200),
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

        if (isset($options['headers']) && is_array($options['headers'])) {
            $request['headers'] = array_merge($request['headers'] ?? [], $options['headers']);
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
}
