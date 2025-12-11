<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\CompliancePendingEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceRejectedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailFailedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailPendingEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailVerifiedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentFailedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\WebhookParseException;
use RequestSuite\RequestPhpClient\Webhooks\ParsedWebhookEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

final class WebhookParserTest extends TestCase
{
    public function testParseReturnsTypedEvent(): void
    {
        $parser = new WebhookParser();
        $payload = [
            'event' => 'payment.confirmed',
            'requestId' => 'req_123',
            'amount' => '1000',
        ];

        $result = $parser->parse([
            'rawBody' => (string) json_encode($payload, JSON_THROW_ON_ERROR),
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);

        self::assertInstanceOf(ParsedWebhookEvent::class, $result);
        self::assertInstanceOf(PaymentConfirmedEvent::class, $result->event());
        self::assertSame('payment.confirmed', $result->eventName());
        self::assertSame('req_123', $result->event()->data()->string('requestId'));
        self::assertSame($payload, $result->payload());
        self::assertNull($result->signature());
        self::assertNull($result->matchedSecret());
    }

    public function testParseRunsSignatureVerification(): void
    {
        $parser = new WebhookParser();
        $payload = [
            'event' => 'payment.confirmed',
            'requestId' => 'req_sig',
        ];
        $body = (string) json_encode($payload, JSON_THROW_ON_ERROR);
        $secret = 'rk_test_secret';
        $signature = hash_hmac(WebhookSignatureVerifier::DEFAULT_SIGNATURE_ALGORITHM, $body, $secret);

        $result = $parser->parse([
            'rawBody' => $body,
            'headers' => [
                'X-Request-Network-Signature' => 'sha256=' . $signature,
                'X-Request-Network-Timestamp' => '1730918400000',
            ],
            'secret' => $secret,
            'timestampHeader' => 'X-Request-Network-Timestamp',
            'toleranceMs' => 10 * 60 * 1000,
            'now' => static fn (): int => 1730918400000,
        ]);

        self::assertSame(strtolower($signature), $result->signature());
        self::assertSame($secret, $result->matchedSecret());
        self::assertSame(1730918400000, $result->timestamp());
    }

    public function testParseRejectsUnknownEvent(): void
    {
        $parser = new WebhookParser();
        $payload = [
            'event' => 'unknown.event',
        ];

        $this->expectException(WebhookParseException::class);
        $this->expectExceptionMessage('Unknown webhook event');

        $parser->parse([
            'rawBody' => (string) json_encode($payload, JSON_THROW_ON_ERROR),
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);
    }

    public function testParseRejectsInvalidJson(): void
    {
        $parser = new WebhookParser();

        $this->expectException(WebhookParseException::class);

        $parser->parse([
            'rawBody' => '{invalid',
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);
    }

    public function testEventSpecificGetters(): void
    {
        $parser = new WebhookParser();
        $payload = [
            'event' => 'payment.failed',
            'requestId' => 'req_fail',
            'failureReason' => 'bank_rejected',
            'retryAfter' => '2025-11-08T00:00:00Z',
            'subStatus' => 'bounced',
        ];

        $result = $parser->parse([
            'rawBody' => (string) json_encode($payload, JSON_THROW_ON_ERROR),
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);

        self::assertInstanceOf(PaymentFailedEvent::class, $result->event());
        $data = $result->event()->data();
        self::assertSame('bank_rejected', $data->string('failureReason'));
        self::assertSame('2025-11-08T00:00:00Z', $data->string('retryAfter'));
        self::assertSame('bounced', $data->string('subStatus'));
    }

    /**
     * @dataProvider paymentDetailEventProvider
     * @param class-string<PaymentDetailApprovedEvent|PaymentDetailFailedEvent|PaymentDetailPendingEvent|PaymentDetailVerifiedEvent> $expectedClass
     */
    public function testPaymentDetailStatusesHydrateSpecificEvents(string $fixture, string $expectedClass): void
    {
        $parser = new WebhookParser();
        $payload = $this->loadFixture($fixture);

        $result = $parser->parse([
            'rawBody' => (string) json_encode($payload, JSON_THROW_ON_ERROR),
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);

        self::assertInstanceOf($expectedClass, $result->event());
        self::assertSame($payload['status'], $result->event()->data()->string('status'));
    }

    /**
     * @dataProvider complianceEventProvider
     * @param class-string<ComplianceApprovedEvent|CompliancePendingEvent|ComplianceRejectedEvent> $expectedClass
     */
    public function testComplianceStatusesHydrateSpecificEvents(string $fixture, string $expectedClass): void
    {
        $parser = new WebhookParser();
        $payload = $this->loadFixture($fixture);

        $result = $parser->parse([
            'rawBody' => (string) json_encode($payload, JSON_THROW_ON_ERROR),
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);

        self::assertInstanceOf($expectedClass, $result->event());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function paymentDetailEventProvider(): iterable
    {
        yield 'approved' => ['payment-detail-approved', PaymentDetailApprovedEvent::class];
        yield 'failed' => ['payment-detail-failed', PaymentDetailFailedEvent::class];
        yield 'pending' => ['payment-detail-pending', PaymentDetailPendingEvent::class];
        yield 'verified' => ['payment-detail-verified', PaymentDetailVerifiedEvent::class];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function complianceEventProvider(): iterable
    {
        yield 'approved' => ['compliance-approved', ComplianceApprovedEvent::class];
        yield 'pending' => ['compliance-pending', CompliancePendingEvent::class];
        yield 'rejected' => ['compliance-rejected', ComplianceRejectedEvent::class];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFixture(string $name): array
    {
        $path = dirname(__DIR__, 3) . '/specs/fixtures/webhooks/' . $name . '.json';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read fixture: %s', $path));
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new \RuntimeException(sprintf('Invalid fixture contents: %s', $path));
        }

        return $decoded;
    }
}
