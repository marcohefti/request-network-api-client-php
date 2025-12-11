<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payouts;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Payouts\PayoutsApi;

final class PayoutsApiTest extends TestCase
{
    public function testCreatePayout(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'id' => 'payout_1',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->create(['amount' => 1000]);

        self::assertSame('payout_1', $result['id'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/payouts', $path);
    }

    public function testGetRecurringStatus(): void
    {
        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], json_encode([
            'status' => 'pending',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->getRecurringStatus('rec-1');

        self::assertSame('pending', $result['status'] ?? null);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/payouts/recurring/rec-1', $path);
    }

    public function testSubmitRecurringSignature(): void
    {
        $api = $this->apiWithResponses([new Response(201, ['content-type' => 'application/json'], json_encode([
            'signatureId' => 'sig_1',
        ], JSON_THROW_ON_ERROR))], $adapter);

        $result = $api->submitRecurringSignature('rec-1', ['signature' => 'abc']);

        self::assertSame('sig_1', $result['signatureId'] ?? null);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PayoutsApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PayoutsApi($http);
    }
}

final class RecordingAdapter implements HttpAdapter
{
    /**
     * @var array<int, Response>
     */
    private array $responses;

    public ?PendingRequest $lastRequest = null;

    /**
     * @param array<int, Response> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function send(PendingRequest $request): Response
    {
        $this->lastRequest = $request;

        if ($this->responses === []) {
            return new Response(200, ['content-type' => 'application/json'], '{}');
        }

        return array_shift($this->responses);
    }

    public function description(): string
    {
        return 'recording';
    }
}
