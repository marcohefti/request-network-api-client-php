<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Requests;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusNormalizer;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusResult;

final class RequestStatusNormalizerTest extends TestCase
{
    public function testMapsPaidStatus(): void
    {
        $raw = [
            'status' => 'completed',
            'hasBeenPaid' => true,
            'requestId' => 'req-123',
            'txHash' => '0x123',
        ];

        $normalizer = new RequestStatusNormalizer();
        $result = $normalizer->normalize($raw);

        self::assertInstanceOf(RequestStatusResult::class, $result);
        self::assertSame('paid', $result->kind);
        self::assertTrue($result->hasBeenPaid);
        self::assertSame('req-123', $result->requestId);
    }

    public function testMapsPendingStatusWhenUnknown(): void
    {
        $raw = [
            'status' => null,
            'hasBeenPaid' => false,
        ];

        $normalizer = new RequestStatusNormalizer();
        $result = $normalizer->normalize($raw);

        self::assertSame('pending', $result->kind);
        self::assertFalse($result->hasBeenPaid);
    }
}
