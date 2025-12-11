<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Webhooks\WebhookHeaders;

final class WebhookHeadersTest extends TestCase
{
    public function testNormaliseArrayHeaders(): void
    {
        $headers = new WebhookHeaders([
            'X-Test' => ['value', 'ignored'],
            'x-empty' => [''],
            'Mixed-Case' => 'Value',
            'number' => 42,
        ]);

        $normalised = $headers->normalised();

        self::assertSame('value', $normalised['x-test']);
        self::assertSame('Value', $normalised['mixed-case']);
        self::assertSame('42', $normalised['number']);
        self::assertArrayNotHasKey('x-empty', $normalised);
    }

    public function testNormalisePsrMessage(): void
    {
        $request = new Request('POST', 'https://example.test', [
            'X-Test' => ['abc', 'def'],
        ]);

        $headers = new WebhookHeaders($request);
        $normalised = $headers->normalised();

        self::assertArrayHasKey('x-test', $normalised);
        self::assertSame('abc', $normalised['x-test']);
    }

    public function testPickHeaderCaseInsensitive(): void
    {
        $headers = new WebhookHeaders([
            'X-Signature' => 'sha256=abc123',
        ]);

        self::assertSame('sha256=abc123', $headers->pick('x-signature'));
        self::assertSame('sha256=abc123', $headers->pick('X-SIGNATURE'));
    }

    public function testPickHeaderFromPsrMessage(): void
    {
        $request = new Request('POST', 'https://example.test', [
            'X-Custom' => 'value',
        ]);

        $headers = new WebhookHeaders($request);

        self::assertSame('value', $headers->pick('X-Custom'));
    }
}
