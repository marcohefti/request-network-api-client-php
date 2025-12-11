<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidation;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;

final class RuntimeValidationTest extends TestCase
{
    public function testNormaliseBoolean(): void
    {
        $runtime = new RuntimeValidation();
        $config = $runtime->normalise(true);

        self::assertTrue($config->requests);
        self::assertTrue($config->responses);
        self::assertTrue($config->errors);
    }

    public function testNormaliseArray(): void
    {
        $runtime = new RuntimeValidation();
        $config = $runtime->normalise([
            'requests' => false,
            'responses' => true,
        ]);

        self::assertFalse($config->requests);
        self::assertTrue($config->responses);
        self::assertTrue($config->errors);
    }

    public function testMergeOverrides(): void
    {
        $base = new RuntimeValidationConfig(true, true, false);
        $runtime = new RuntimeValidation();
        $merged = $runtime->merge($base, ['responses' => false]);

        self::assertTrue($merged->requests);
        self::assertFalse($merged->responses);
        self::assertFalse($merged->errors);
    }
}
