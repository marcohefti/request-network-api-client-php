<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests\V1;

use InvalidArgumentException;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;
use RequestSuite\RequestPhpClient\Domains\Requests\PaymentCalldataResult;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestQueryBuilder;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusResult;

final class RequestsV1Api extends JsonApi
{
    private const OP_CREATE = 'RequestControllerV1_createRequest_v1';
    private const OP_PAYMENT_ROUTES = 'RequestControllerV1_getRequestPaymentRoutes_v1';
    private const OP_PAYMENT_CALLDATA = 'RequestControllerV1_getRequestPaymentCalldata_v1';
    private const OP_REQUEST_STATUS = 'RequestControllerV1_getRequestStatus_v1';
    private const OP_SEND_PAYMENT_INTENT = 'RequestControllerV1_sendPaymentIntent_v1';
    private const OP_STOP_RECURRENCE = 'RequestControllerV1_stopRecurrenceRequest_v1';

    private RequestQueryBuilder $queryBuilder;
    private LegacyRequestStatusNormalizer $statusNormalizer;

    public function __construct(
        HttpClient $http,
        ?JsonRequestHelper $json = null,
        ?RequestQueryBuilder $queryBuilder = null,
        ?LegacyRequestStatusNormalizer $statusNormalizer = null
    ) {
        parent::__construct($http, $json);
        $this->queryBuilder = $queryBuilder ?? new RequestQueryBuilder();
        $this->statusNormalizer = $statusNormalizer ?? new LegacyRequestStatusNormalizer();
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
            'path' => '/v1/request',
            'body' => $body,
            'description' => 'Create legacy request',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getPaymentRoutes(string $paymentReference, array $options): array
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
            'path' => sprintf('/v1/request/%s/routes', rawurlencode($paymentReference)),
            'query' => $query,
            'description' => 'Legacy payment routes',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getPaymentCalldata(string $paymentReference, array $options = []): PaymentCalldataResult
    {
        $query = $this->queryBuilder->build([
            'wallet' => $options['wallet'] ?? null,
            'chain' => $options['chain'] ?? null,
            'token' => $options['token'] ?? null,
            'amount' => $options['amount'] ?? null,
        ]);

        /** @var array{operationId: string, method: string, path: string, query?: array<string, array<int, bool|float|int|string>|bool|float|int|string>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_PAYMENT_CALLDATA,
            'method' => 'GET',
            'path' => sprintf('/v1/request/%s/pay', rawurlencode($paymentReference)),
            'query' => $query,
            'description' => 'Legacy payment calldata',
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
    public function getRequestStatus(string $paymentReference, array $options = []): RequestStatusResult
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_REQUEST_STATUS,
            'method' => 'GET',
            'path' => sprintf('/v1/request/%s', rawurlencode($paymentReference)),
            'description' => 'Legacy request status',
        ];

        $result = $this->json->requestJson($this->http, $this->applyOptions($request, $options));
        if (! is_array($result)) {
            throw new InvalidArgumentException('Request status response must be an object.');
        }

        return $this->statusNormalizer->normalize($result);
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
            'path' => sprintf('/v1/request/%s/send', rawurlencode($paymentIntentId)),
            'body' => $body,
            'description' => 'Legacy send payment intent',
        ];

        $this->json->requestVoid($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function stopRecurrence(string $paymentReference, array $options = []): void
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_STOP_RECURRENCE,
            'method' => 'PATCH',
            'path' => sprintf('/v1/request/%s/stop-recurrence', rawurlencode($paymentReference)),
            'description' => 'Legacy stop recurrence',
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
