<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\AbstractWebhookEvent;

final class PaymentConfirmedEvent extends AbstractWebhookEvent
{
    public const EVENT = 'payment.confirmed';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
