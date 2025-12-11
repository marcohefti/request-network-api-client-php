<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\AbstractWebhookEvent;

final class PaymentFailedEvent extends AbstractWebhookEvent
{
    public const EVENT = 'payment.failed';

    public static function eventName(): string
    {
        return self::EVENT;
    }

    public function failureReason(): ?string
    {
        return $this->extractString('failureReason');
    }

    public function retryAfter(): ?string
    {
        return $this->extractString('retryAfter');
    }
}
