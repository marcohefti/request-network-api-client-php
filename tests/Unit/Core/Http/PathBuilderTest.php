<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\PathBuilder;

final class PathBuilderTest extends TestCase
{
    public function testBuildReplacesTokens(): void
    {
        $builder = new PathBuilder();
        $path = $builder->build('/v2/requests/{requestId}/payments/{paymentId}', [
            'requestId' => 'req_123',
            'paymentId' => 'pay 456',
        ]);

        self::assertSame('/v2/requests/req_123/payments/pay%20456', $path);
    }

    public function testMissingParametersProduceEmptySegments(): void
    {
        $builder = new PathBuilder();
        $path = $builder->build('/v2/requests/{requestId}/payments/{paymentId}', [
            'requestId' => 'req_123',
        ]);

        self::assertSame('/v2/requests/req_123/payments/', $path);
    }
}
