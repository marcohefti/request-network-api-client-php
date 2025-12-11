<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

final class WebhookSignatureVerifierTest extends TestCase
{
    public function testVerifyReturnsMatchedSecretAndTimestamp(): void
    {
        $body = '{"hello":"world"}';
        $secret = 'rk_test_secret';
        $signature = hash_hmac('sha256', $body, $secret);

        $result = WebhookSignatureVerifier::verify(
            $body,
            $secret,
            [
                'X-Request-Network-Signature' => 'sha256=' . $signature,
                'X-Request-Network-Timestamp' => '1730918400000',
            ],
            ['timestampHeader' => 'X-Request-Network-Timestamp']
        );

        self::assertSame(strtolower($signature), $result->signature);
        self::assertSame($secret, $result->matchedSecret);
        self::assertSame(1730918400000, $result->timestamp);
        self::assertSame('sha256=' . $signature, $result->headers['x-request-network-signature']);
    }

    public function testVerifySupportsSecretRotation(): void
    {
        $body = '{"foo":"bar"}';
        $secret = 'rk_prod_new';
        $signature = hash_hmac('sha256', $body, $secret);

        $result = WebhookSignatureVerifier::verify(
            $body,
            ['rk_old', $secret],
            ['x-request-network-signature' => $signature]
        );

        self::assertSame($secret, $result->matchedSecret);
    }

    public function testVerifyThrowsWhenMissingSignature(): void
    {
        $this->expectException(RequestWebhookSignatureException::class);
        $this->expectExceptionMessage('Missing webhook signature header');

        WebhookSignatureVerifier::verify('{}', 'secret');
    }

    public function testVerifyEnforcesTolerance(): void
    {
        $body = '{}';
        $secret = 'secret';
        $signature = hash_hmac('sha256', $body, $secret);

        $this->expectException(RequestWebhookSignatureException::class);
        $this->expectExceptionMessage('Webhook signature timestamp outside tolerance');

        WebhookSignatureVerifier::verify(
            $body,
            $secret,
            [
                'x-request-network-signature' => $signature,
                'x-request-network-timestamp' => (string) (1_600_000_000_000),
            ],
            [
                'timestampHeader' => 'x-request-network-timestamp',
                'toleranceMs' => 1000,
                'now' => static fn(): int => 1_600_000_005_000,
            ]
        );
    }

    public function testVerifyFromRequestReadsPsrRequest(): void
    {
        $body = '{"foo":true}';
        $secret = 'secret';
        $signature = hash_hmac('sha256', $body, $secret);

        $request = new ServerRequest(
            'POST',
            'https://hooks.test',
            ['X-Request-Network-Signature' => 'sha256=' . $signature],
            $body
        );

        $result = WebhookSignatureVerifier::verifyFromRequest($request, $secret);

        self::assertSame($secret, $result->matchedSecret);
    }
}
