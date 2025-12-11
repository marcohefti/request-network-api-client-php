<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Payer\V2;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;

final class PayerV2Api extends JsonApi
{
    private const OP_CREATE_COMPLIANCE = 'PayerV2Controller_getComplianceData_v2';
    private const OP_GET_STATUS = 'PayerV2Controller_getComplianceStatus_v2';
    private const OP_UPDATE_STATUS = 'PayerV2Controller_updateComplianceStatus_v2';
    private const OP_CREATE_PAYMENT_DETAILS = 'PayerV2Controller_createPaymentDetails_v2';
    private const OP_GET_PAYMENT_DETAILS = 'PayerV2Controller_getPaymentDetails_v2';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createComplianceData(array $body, array $options = []): array
    {
        /** @var array{
         *   operationId: string,
         *   method: string,
         *   path: string,
         *   body: array<string, mixed>,
         *   description: string,
         *   meta?: array<string, mixed>,
         *   timeoutMs?: int
         * } $request
         */
        $request = [
            'operationId' => self::OP_CREATE_COMPLIANCE,
            'method' => 'POST',
            'path' => '/v2/payer',
            'body' => $body,
            'description' => 'Create compliance data',
            'meta' => $this->buildMeta(
                $this->schema()->request(self::OP_CREATE_COMPLIANCE, 'application/json'),
                $this->schema()->response(self::OP_CREATE_COMPLIANCE, 200)
            ),
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getComplianceStatus(string $clientUserId, array $options = []): array
    {
        $path = sprintf('/v2/payer/%s', rawurlencode($clientUserId));

        /** @var array{
         *   operationId: string,
         *   method: string,
         *   path: string,
         *   description: string,
         *   meta?: array<string, mixed>,
         *   timeoutMs?: int
         * } $request
         */
        $request = [
            'operationId' => self::OP_GET_STATUS,
            'method' => 'GET',
            'path' => $path,
            'description' => 'Get compliance status',
            'meta' => $this->buildMeta(
                null,
                $this->schema()->response(self::OP_GET_STATUS, 200)
            ),
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function updateComplianceStatus(string $clientUserId, array $body, array $options = []): array
    {
        $path = sprintf('/v2/payer/%s', rawurlencode($clientUserId));

        /** @var array{
         *   operationId: string,
         *   method: string,
         *   path: string,
         *   body: array<string, mixed>,
         *   description: string,
         *   meta?: array<string, mixed>,
         *   timeoutMs?: int
         * } $request
         */
        $request = [
            'operationId' => self::OP_UPDATE_STATUS,
            'method' => 'PATCH',
            'path' => $path,
            'body' => $body,
            'description' => 'Update compliance status',
            'meta' => $this->buildMeta(
                $this->schema()->request(self::OP_UPDATE_STATUS, 'application/json'),
                $this->schema()->response(self::OP_UPDATE_STATUS, 200)
            ),
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createPaymentDetails(string $clientUserId, array $body, array $options = []): array
    {
        $path = sprintf('/v2/payer/%s/payment-details', rawurlencode($clientUserId));

        /** @var array{
         *   operationId: string,
         *   method: string,
         *   path: string,
         *   body: array<string, mixed>,
         *   description: string,
         *   meta?: array<string, mixed>,
         *   timeoutMs?: int
         * } $request
         */
        $request = [
            'operationId' => self::OP_CREATE_PAYMENT_DETAILS,
            'method' => 'POST',
            'path' => $path,
            'body' => $body,
            'description' => 'Create payment details',
            'meta' => $this->buildMeta(
                $this->schema()->request(self::OP_CREATE_PAYMENT_DETAILS, 'application/json'),
                $this->schema()->response(self::OP_CREATE_PAYMENT_DETAILS, 201)
            ),
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getPaymentDetails(string $clientUserId, array $options = []): array
    {
        $path = sprintf('/v2/payer/%s/payment-details', rawurlencode($clientUserId));

        /** @var array{
         *   operationId: string,
         *   method: string,
         *   path: string,
         *   description: string,
         *   meta?: array<string, mixed>,
         *   timeoutMs?: int
         * } $request
         */
        $request = [
            'operationId' => self::OP_GET_PAYMENT_DETAILS,
            'method' => 'GET',
            'path' => $path,
            'description' => 'Get payment details',
            'meta' => $this->buildMeta(
                null,
                $this->schema()->response(self::OP_GET_PAYMENT_DETAILS, 200)
            ),
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
     * @return array<string, mixed>
     */
    private function buildMeta(?SchemaKey $requestKey, SchemaKey $responseKey): array
    {
        $meta = ['responseSchemaKey' => $responseKey];

        if ($requestKey !== null) {
            $meta['requestSchemaKey'] = $requestKey;
        }

        return $meta;
    }
}
