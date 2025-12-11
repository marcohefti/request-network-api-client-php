<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Pay\V1;

use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\JsonApi;
use RequestSuite\RequestPhpClient\Core\Http\JsonRequestHelper;

final class PayV1Api extends JsonApi
{
    private const OP_PAY_REQUEST = 'PayV1Controller_payRequest_v1';
    private const PAY_PATH = '/v1/pay';
    private const DESCRIPTION_PAY_REQUEST = 'Legacy pay request';

    public function __construct(HttpClient $http, ?JsonRequestHelper $json = null)
    {
        parent::__construct($http, $json);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function payRequest(array $body, array $options = []): array
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
            'operationId' => self::OP_PAY_REQUEST,
            'method' => 'POST',
            'path' => self::PAY_PATH,
            'body' => $body,
            'description' => self::DESCRIPTION_PAY_REQUEST,
            'meta' => $this->buildDefaultMeta(),
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
    private function buildDefaultMeta(): array
    {
        return [
            'requestSchemaKey' => $this->schema()->request(self::OP_PAY_REQUEST, 'application/json'),
            'responseSchemaKey' => $this->schema()->response(self::OP_PAY_REQUEST, 201, 'application/json'),
        ];
    }
}
