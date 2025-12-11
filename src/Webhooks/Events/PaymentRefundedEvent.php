<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\AbstractWebhookEvent;

final class PaymentRefundedEvent extends AbstractWebhookEvent
{
    public const EVENT = 'payment.refunded';

    public static function eventName(): string
    {
        return self::EVENT;
    }

    public function refundedTo(): ?string
    {
        return $this->extractString('refundedTo');
    }

    public function refundAmount(): ?string
    {
        return $this->extractString('refundAmount');
    }
}
