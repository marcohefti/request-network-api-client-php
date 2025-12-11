<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Logging\Redactor;

final class RedactorTest extends TestCase
{
    public function testRedactsSensitiveKeys(): void
    {
        $context = [
            'authorization' => 'secret-token',
            'x-api-key' => 'rk_live',
            'safe' => 'value',
        ];

        $redactor = new Redactor();
        $result = $redactor->redactContext($context);

        self::assertSame('***REDACTED***', $result['authorization']);
        self::assertSame('***REDACTED***', $result['x-api-key']);
        self::assertSame('value', $result['safe']);
    }

    public function testRedactValueHandlesArrays(): void
    {
        $value = [
            'authorization' => 'secret',
            'nested' => 'value',
        ];

        $redactor = new Redactor();
        $result = $redactor->redactValue('headers', $value);

        self::assertIsArray($result);
        self::assertSame('***REDACTED***', $result['authorization']);
        self::assertSame('value', $result['nested']);
    }
}
