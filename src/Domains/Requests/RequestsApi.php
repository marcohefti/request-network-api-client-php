<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests;

use InvalidArgumentException;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class RequestsApi extends JsonApi
{
    private const OP_CREATE = 'RequestControllerV2_createRequest_v2';
    private const OP_PAYMENT_ROUTES = 'RequestControllerV2_getRequestPaymentRoutes_v2';
    private const OP_PAYMENT_CALLDATA = 'RequestControllerV2_getPaymentCalldata_v2';
    private const OP_UPDATE = 'RequestControllerV2_updateRequest_v2';
    private const OP_SEND_PAYMENT_INTENT = 'RequestControllerV2_sendPaymentIntent_v2';
    private const OP_REQUEST_STATUS = 'RequestControllerV2_getRequestStatus_v2';

    private RequestQueryBuilder $queryBuilder;
    private RequestStatusNormalizer $statusNormalizer;

    public function __construct(
        HttpClient $http,
        ?JsonRequestHelper $json = null,
        ?RequestQueryBuilder $queryBuilder = null,
        ?RequestStatusNormalizer $statusNormalizer = null
    ) {
        parent::__construct($http, $json);
        $this->queryBuilder = $queryBuilder ?? new RequestQueryBuilder();
        $this->statusNormalizer = $statusNormalizer ?? new RequestStatusNormalizer();
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
            'path' => '/v2/request',
            'body' => $body,
            'description' => 'Create request',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getPaymentRoutes(string $requestId, array $options): array
    {
        if (! isset($options['wallet'])) {
            throw new InvalidArgumentException('wallet is required in options.');
        }

        $query = $this->queryBuilder->build([
            'wallet' => $options['wallet'],
            'amount' => $options['amount'] ?? null,
            'feePercentage' => $options['feePercentage'] ?? null,
            'feeAddress' => $options['feeAddress'] ?? null,
        ]);

        /** @var array{operationId: string, method: string, path: string, query?: array<string, array<int, bool|float|int|string>|bool|float|int|string>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_PAYMENT_ROUTES,
            'method' => 'GET',
            'path' => sprintf('/v2/request/%s/routes', rawurlencode($requestId)),
            'query' => $query,
            'description' => 'Payment routes',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getPaymentCalldata(string $requestId, array $options = []): PaymentCalldataResult
    {
        $query = $this->queryBuilder->build([
            'wallet' => $options['wallet'] ?? null,
            'chain' => $options['chain'] ?? null,
            'token' => $options['token'] ?? null,
            'amount' => $options['amount'] ?? null,
            'clientUserId' => $options['clientUserId'] ?? null,
            'paymentDetailsId' => $options['paymentDetailsId'] ?? null,
            'feePercentage' => $options['feePercentage'] ?? null,
            'feeAddress' => $options['feeAddress'] ?? null,
        ]);

        /** @var array{operationId: string, method: string, path: string, query?: array<string, array<int, bool|float|int|string>|bool|float|int|string>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_PAYMENT_CALLDATA,
            'method' => 'GET',
            'path' => sprintf('/v2/request/%s/pay', rawurlencode($requestId)),
            'query' => $query,
            'description' => 'Payment calldata',
        ];

        $payload = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        if (is_array($payload) && array_key_exists('transactions', $payload)) {
            return new PaymentCalldataResult('calldata', $payload);
        }

        if (is_array($payload) && array_key_exists('paymentIntentId', $payload)) {
            return new PaymentCalldataResult('paymentIntent', $payload);
        }

        throw new InvalidArgumentException('Unexpected payment calldata payload.');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getRequestStatus(string $requestId, array $options = []): RequestStatusResult
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_REQUEST_STATUS,
            'method' => 'GET',
            'path' => sprintf('/v2/request/%s', rawurlencode($requestId)),
            'description' => 'Request status',
        ];

        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));

        if (! is_array($result)) {
            throw new InvalidArgumentException('Request status response must be an object.');
        }

        return $this->statusNormalizer->normalize($result);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function update(string $requestId, array $options = []): void
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_UPDATE,
            'method' => 'PATCH',
            'path' => sprintf('/v2/request/%s', rawurlencode($requestId)),
            'description' => 'Update request',
        ];

        $this->json->requestVoid($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     */
    public function sendPaymentIntent(string $paymentIntentId, array $body, array $options = []): void
    {
        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_SEND_PAYMENT_INTENT,
            'method' => 'POST',
            'path' => sprintf('/v2/request/payment-intents/%s', rawurlencode($paymentIntentId)),
            'body' => $body,
            'description' => 'Send payment intent',
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
