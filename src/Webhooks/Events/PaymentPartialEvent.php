<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\AbstractWebhookEvent;

final class PaymentPartialEvent extends AbstractWebhookEvent
{
    public const EVENT = 'payment.partial';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
