<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\CompliancePendingEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceRejectedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailFailedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailPendingEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailVerifiedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentFailedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentPartialEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentProcessingEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentRefundedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\RequestRecurringEvent;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\WebhookParseException;

final class WebhookEventFactory
{
    /**
     * @var array<string, class-string<WebhookEventInterface>>
     */
    private const BASE_CLASS_MAP = [
        PaymentConfirmedEvent::EVENT => PaymentConfirmedEvent::class,
        PaymentFailedEvent::EVENT => PaymentFailedEvent::class,
        PaymentProcessingEvent::EVENT => PaymentProcessingEvent::class,
        PaymentDetailUpdatedEvent::EVENT => PaymentDetailUpdatedEvent::class,
        ComplianceUpdatedEvent::EVENT => ComplianceUpdatedEvent::class,
        PaymentPartialEvent::EVENT => PaymentPartialEvent::class,
        PaymentRefundedEvent::EVENT => PaymentRefundedEvent::class,
        RequestRecurringEvent::EVENT => RequestRecurringEvent::class,
    ];

    /**
     * @var array<string, class-string<PaymentDetailUpdatedEvent>>
     */
    private const PAYMENT_DETAIL_STATUS_CLASS_MAP = [
        'approved' => PaymentDetailApprovedEvent::class,
        'failed' => PaymentDetailFailedEvent::class,
        'pending' => PaymentDetailPendingEvent::class,
        'verified' => PaymentDetailVerifiedEvent::class,
    ];

    /**
     * @var list<string>
     */
    private const COMPLIANCE_APPROVED_STATUSES = ['approved', 'completed', 'signed'];

    /**
     * @var list<string>
     */
    private const COMPLIANCE_REJECTED_STATUSES = ['rejected', 'failed'];

    /**
     * @var list<string>
     */
    private const COMPLIANCE_PENDING_STATUSES = ['pending', 'initiated', 'not_started'];

    /**
     * @return list<string>
     */
    public function supportedEvents(): array
    {
        return array_keys(self::BASE_CLASS_MAP);
    }

    public function supports(string $eventName): bool
    {
        return isset(self::BASE_CLASS_MAP[$eventName]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(string $eventName, array $payload): WebhookEventInterface
    {
        if ($eventName === PaymentDetailUpdatedEvent::EVENT) {
            return $this->createPaymentDetailEvent($payload);
        }

        if ($eventName === ComplianceUpdatedEvent::EVENT) {
            return $this->createComplianceEvent($payload);
        }

        $className = self::BASE_CLASS_MAP[$eventName] ?? null;
        if ($className === null) {
            throw new WebhookParseException(sprintf('Unknown webhook event: %s', $eventName));
        }

        return new $className($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createPaymentDetailEvent(array $payload): WebhookEventInterface
    {
        $status = $this->normaliseStatus($payload['status'] ?? null);
        if ($status !== null && isset(self::PAYMENT_DETAIL_STATUS_CLASS_MAP[$status])) {
            $className = self::PAYMENT_DETAIL_STATUS_CLASS_MAP[$status];

            return new $className($payload);
        }

        return new PaymentDetailUpdatedEvent($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createComplianceEvent(array $payload): WebhookEventInterface
    {
        $kycStatus = $this->normaliseStatus($payload['kycStatus'] ?? null);
        $agreementStatus = $this->normaliseStatus($payload['agreementStatus'] ?? null);
        $isCompliant = isset($payload['isCompliant'])
            ? filter_var($payload['isCompliant'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $classification = $this->classifyComplianceStatus($kycStatus, $agreementStatus, $isCompliant);

        return match ($classification) {
            'approved' => new ComplianceApprovedEvent($payload),
            'rejected' => new ComplianceRejectedEvent($payload),
            'pending' => new CompliancePendingEvent($payload),
            default => new ComplianceUpdatedEvent($payload),
        };
    }

    private function classifyComplianceStatus(
        ?string $kycStatus,
        ?string $agreementStatus,
        ?bool $isCompliant
    ): ?string {
        if ($isCompliant === true) {
            return 'approved';
        }

        if ($isCompliant === false) {
            return 'rejected';
        }

        if (
            $this->matchesStatus($kycStatus, self::COMPLIANCE_REJECTED_STATUSES)
            || $this->matchesStatus($agreementStatus, self::COMPLIANCE_REJECTED_STATUSES)
        ) {
            return 'rejected';
        }

        if (
            $this->matchesStatus($kycStatus, self::COMPLIANCE_APPROVED_STATUSES)
            || $this->matchesStatus($agreementStatus, self::COMPLIANCE_APPROVED_STATUSES)
        ) {
            return 'approved';
        }

        if (
            $this->matchesStatus($kycStatus, self::COMPLIANCE_PENDING_STATUSES)
            || $this->matchesStatus($agreementStatus, self::COMPLIANCE_PENDING_STATUSES)
        ) {
            return 'pending';
        }

        return null;
    }

    /**
     * @param list<string> $candidates
     */
    private function matchesStatus(?string $status, array $candidates): bool
    {
        if ($status === null) {
            return false;
        }

        return in_array($status, $candidates, true);
    }

    private function normaliseStatus(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return strtolower($value);
    }
}
