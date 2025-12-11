<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Exception;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiErrorBuilder;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RuntimeException;

final class RequestApiErrorBuilderTest extends TestCase
{
    public function testBuildRequestApiErrorMapsPayloadHeadersAndMeta(): void
    {
        $payload = [
            'message' => 'Forbidden',
            'code' => 'requests.forbidden',
            'detail' => ['reason' => 'Missing field'],
            'errors' => [
                [
                    'message' => 'Amount must be positive',
                    'code' => 'validation.amount',
                    'field' => 'amount',
                    'source' => [
                        'pointer' => '/amount',
                        'parameter' => 'amount',
                    ],
                    'meta' => ['limit' => 1],
                ],
                null,
            ],
        ];

        $builder = new RequestApiErrorBuilder();
        $exception = $builder->build(
            $payload,
            403,
            [
                'X-Request-Id' => 'req-123',
                'X-Correlation-Id' => 'corr-456',
                'Retry-After' => '120',
            ],
            'Fallback message',
            [
                'operationId' => 'payments.create',
                'description' => null,
            ]
        );

        self::assertInstanceOf(RequestApiException::class, $exception);
        self::assertSame('Forbidden', $exception->getMessage());
        self::assertSame(403, $exception->statusCode());
        self::assertSame('requests.forbidden', $exception->errorCode());
        self::assertSame('req-123', $exception->requestId());
        self::assertSame('corr-456', $exception->correlationId());
        self::assertSame(120_000, $exception->retryAfterMs());
        self::assertSame(['reason' => 'Missing field'], $exception->detail());
        self::assertSame(['operationId' => 'payments.create'], $exception->context()->meta());

        $headers = $exception->context()->headers();
        self::assertNotNull($headers);
        self::assertSame('req-123', $headers['x-request-id']);
        self::assertSame('corr-456', $headers['x-correlation-id']);
        self::assertSame('120', $headers['retry-after']);

        $errors = $exception->errors();
        self::assertNotNull($errors);
        self::assertCount(1, $errors);
        self::assertSame('Amount must be positive', $errors[0]['message']);
        self::assertSame('validation.amount', $errors[0]['code']);
        self::assertSame('amount', $errors[0]['field']);
        self::assertSame('/amount', $errors[0]['source']['pointer']);
        self::assertSame('amount', $errors[0]['source']['parameter']);
        self::assertSame(['limit' => 1], $errors[0]['meta']);

        $serialized = $exception->toArray();
        self::assertSame('RequestApiError', $serialized['name']);
        self::assertSame('requests.forbidden', $serialized['code']);
        self::assertSame(403, $serialized['status']);
        self::assertSame('req-123', $serialized['requestId']);
        self::assertSame('corr-456', $serialized['correlationId']);
    }

    public function testBuildRequestApiErrorUsesFallbacksWhenPayloadIsMissing(): void
    {
        $future = time() + 120;
        $retryAfter = gmdate('D, d M Y H:i:s \\G\\M\\T', $future);

        $builder = new RequestApiErrorBuilder();
        $exception = $builder->build(
            null,
            500,
            ['Retry-After' => $retryAfter],
            'Operation failed'
        );

        self::assertSame('Operation failed', $exception->getMessage());
        self::assertSame('HTTP_500', $exception->errorCode());

        $retryAfterMs = $exception->retryAfterMs();
        self::assertNotNull($retryAfterMs);
        self::assertGreaterThanOrEqual(0, $retryAfterMs);
        self::assertLessThanOrEqual(120_000, $retryAfterMs);
    }

    public function testBuildRequestApiErrorFallsBackToDefaultMessageWhenNoFallbackProvided(): void
    {
        $builder = new RequestApiErrorBuilder();
        $exception = $builder->build([], 404);

        self::assertSame('Request Network API error', $exception->getMessage());
        self::assertSame('HTTP_404', $exception->errorCode());
    }

    public function testIsRequestApiErrorHelperDetectsDifferentShapes(): void
    {
        $builder = new RequestApiErrorBuilder();
        $exception = $builder->build(null, 400);

        self::assertTrue(RequestApiErrorBuilder::isRequestApiError($exception));
        self::assertTrue(RequestApiErrorBuilder::isRequestApiError($exception->toArray()));
        self::assertFalse(RequestApiErrorBuilder::isRequestApiError(['name' => 'OtherError']));
        self::assertFalse(RequestApiErrorBuilder::isRequestApiError(new RuntimeException('boom')));
    }
}
