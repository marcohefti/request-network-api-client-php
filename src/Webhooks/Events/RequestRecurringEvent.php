<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\AbstractWebhookEvent;

final class RequestRecurringEvent extends AbstractWebhookEvent
{
    public const EVENT = 'request.recurring';

    public static function eventName(): string
    {
        return self::EVENT;
    }

    public function originalRequestId(): ?string
    {
        return $this->extractString('originalRequestId');
    }

    public function originalRequestPaymentReference(): ?string
    {
        return $this->extractString('originalRequestPaymentReference');
    }
}
