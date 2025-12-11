<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payments;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Payments\PaymentsApi;

final class PaymentsApiTest extends TestCase
{
    public function testSearchPayments(): void
    {
        $response = json_encode([
            'items' => [
                ['id' => 'pay_1'],
            ],
            'pagination' => ['nextCursor' => null],
        ], JSON_THROW_ON_ERROR);

        $api = $this->apiWithResponses([new Response(200, ['content-type' => 'application/json'], $response)], $adapter);

        $result = $api->search(['wallet' => '0xabc', 'limit' => 10]);

        self::assertSame('pay_1', $result['items'][0]['id'] ?? null);
        $url = $adapter->lastRequest?->url() ?? '';
        $path = parse_url($url, PHP_URL_PATH);
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        self::assertSame('/v2/payments', $path);
        self::assertSame('0xabc', $query['wallet'] ?? null);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function apiWithResponses(array $responses, ?RecordingAdapter &$adapter): PaymentsApi
    {
        $adapter = new RecordingAdapter($responses);
        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        return new PaymentsApi($http);
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
