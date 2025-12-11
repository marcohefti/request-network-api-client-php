<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Auth;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Auth\CredentialHeaderBuilder;

final class CredentialHeaderBuilderTest extends TestCase
{
    public function testBuildReturnsExpectedHeaders(): void
    {
        $builder = new CredentialHeaderBuilder();
        $headers = $builder->build([
            'apiKey' => 'sk_123',
            'clientId' => 'client_abc',
            'origin' => 'WooCommerce',
        ]);

        self::assertSame(
            [
                'x-api-key' => 'sk_123',
                'x-client-id' => 'client_abc',
                'Origin' => 'WooCommerce',
            ],
            $headers
        );
    }

    public function testBuildSkipsEmptyValues(): void
    {
        $builder = new CredentialHeaderBuilder();
        $headers = $builder->build([
            'apiKey' => '   ',
            'clientId' => null,
            'origin' => '   ',
        ]);

        self::assertSame([], $headers);
    }
}
