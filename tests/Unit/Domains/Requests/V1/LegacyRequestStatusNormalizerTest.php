<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Requests\V1;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Domains\Requests\V1\LegacyRequestStatusNormalizer;

final class LegacyRequestStatusNormalizerTest extends TestCase
{
    public function testPaidStatus(): void
    {
        $normalizer = new LegacyRequestStatusNormalizer();
        $result = $normalizer->normalize([
            'paymentReference' => 'ref-1',
            'hasBeenPaid' => true,
            'txHash' => '0xabc',
        ]);

        self::assertSame('paid', $result->kind);
        self::assertTrue($result->hasBeenPaid);
        self::assertSame('ref-1', $result->paymentReference);
    }

    public function testPendingStatus(): void
    {
        $normalizer = new LegacyRequestStatusNormalizer();
        $result = $normalizer->normalize([
            'paymentReference' => 'ref-2',
            'hasBeenPaid' => false,
        ]);

        self::assertSame('pending', $result->kind);
        self::assertFalse($result->hasBeenPaid);
    }
}
