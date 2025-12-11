<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Webhooks\Testing\WebhookTestHelper;

use function hash_hmac;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class WebhookTestHelperTest extends TestCase
{
    public function testGenerateSignatureMatchesHash(): void
    {
        $payload = ['event' => 'payment.confirmed'];
        $signature = WebhookTestHelper::generateSignature($payload, 'rk_secret');

        self::assertSame(hash_hmac('sha256', (string) json_encode($payload, JSON_THROW_ON_ERROR), 'rk_secret'), $signature);
    }

    public function testVerificationBypassStateResetsAfterCallback(): void
    {
        self::assertFalse(WebhookTestHelper::isVerificationBypassed());

        WebhookTestHelper::withVerificationDisabled(function (): void {
            self::assertTrue(WebhookTestHelper::isVerificationBypassed());
        });

        self::assertFalse(WebhookTestHelper::isVerificationBypassed());
    }

    public function testCreateMockRequestSetsSignatureHeader(): void
    {
        $factory = new Psr17Factory();
        $request = WebhookTestHelper::createMockRequest([
            'payload' => ['event' => 'payment.confirmed'],
            'secret' => 'rk_secret',
            'requestFactory' => $factory,
            'streamFactory' => $factory,
        ]);

        self::assertTrue($request->hasHeader('x-request-network-signature'));
        self::assertSame('application/json', $request->getHeaderLine('content-type'));
    }
}
