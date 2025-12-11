<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Payouts;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class PayoutsApi extends JsonApi
{
    private const OP_CREATE = 'PayoutV2Controller_payRequest_v2';
    private const OP_CREATE_BATCH = 'PayoutV2Controller_payBatchRequest_v2';
    private const OP_RECURRING_STATUS = 'PayoutV2Controller_getRecurringPaymentStatus_v2';
    private const OP_SUBMIT_SIGNATURE = 'PayoutV2Controller_submitRecurringPaymentSignature_v2';
    private const OP_UPDATE_RECURRING = 'PayoutV2Controller_updateRecurringPayment_v2';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
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
            'path' => '/v2/payouts',
            'body' => $body,
            'description' => 'Create payout',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createBatch(array $body, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_CREATE_BATCH,
            'method' => 'POST',
            'path' => '/v2/payouts/batch',
            'body' => $body,
            'description' => 'Create payout batch',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getRecurringStatus(string $recurringId, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_RECURRING_STATUS,
            'method' => 'GET',
            'path' => sprintf('/v2/payouts/recurring/%s', rawurlencode($recurringId)),
            'description' => 'Recurring payout status',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function submitRecurringSignature(string $recurringId, array $body, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_SUBMIT_SIGNATURE,
            'method' => 'POST',
            'path' => sprintf('/v2/payouts/recurring/%s', rawurlencode($recurringId)),
            'body' => $body,
            'description' => 'Submit recurring payout signature',
        ];

        return $this->json->requestJson($this->http, $this->applyOptions($request, $options));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function updateRecurring(string $recurringId, array $body, array $options = []): array
    {
        /** @var array{operationId: string, method: string, path: string, body: array<string, mixed>, description: string, meta?: array<string, mixed>, timeoutMs?: int} $request */
        $request = [
            'operationId' => self::OP_UPDATE_RECURRING,
            'method' => 'PATCH',
            'path' => sprintf('/v2/payouts/recurring/%s', rawurlencode($recurringId)),
            'body' => $body,
            'description' => 'Update recurring payout',
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
