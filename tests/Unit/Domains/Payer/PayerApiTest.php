<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payer;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\HttpClient;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;
use RequestSuite\RequestPhpClient\Domains\Payer\PayerApi;
use RequestSuite\RequestPhpClient\Domains\Payer\V1\PayerV1Api;
use RequestSuite\RequestPhpClient\Domains\Payer\V2\PayerV2Api;

final class PayerApiTest extends TestCase
{
    public function testPayerApiForwardsToV2AndExposesLegacy(): void
    {
        $adapter = new RecordingAdapter([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'userId' => 'v2-user',
                'status' => ['agreementStatus' => 'pending', 'kycStatus' => 'initiated'],
            ], JSON_THROW_ON_ERROR)),
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'userId' => 'legacy-user',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $http = new HttpClient(
            RequestClientConfig::fromArray([]),
            $adapter,
            StandardRetryPolicy::default()
        );

        $api = new PayerApi(new PayerV2Api($http), new PayerV1Api($http));

        $api->createComplianceData(['clientUserId' => 'merchant-user']);
        $path = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v2/payer', $path);

        self::assertInstanceOf(PayerV1Api::class, $api->legacy);
        $api->legacy->createComplianceData(['clientUserId' => 'legacy-user']);
        $pathLegacy = parse_url($adapter->lastRequest?->url() ?? '', PHP_URL_PATH);
        self::assertSame('/v1/payer', $pathLegacy);
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
