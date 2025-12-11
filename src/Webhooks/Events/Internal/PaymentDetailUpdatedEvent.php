<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

class PaymentDetailUpdatedEvent extends AbstractWebhookEvent
{
    public const EVENT = 'payment_detail.updated';

    public static function eventName(): string
    {
        return self::EVENT;
    }

    public function status(): ?string
    {
        return $this->extractString('status');
    }

    public function paymentDetailsId(): ?string
    {
        return $this->extractString('paymentDetailsId');
    }

    public function paymentAccountId(): ?string
    {
        return $this->extractString('paymentAccountId');
    }

    public function rejectionMessage(): ?string
    {
        return $this->extractString('rejectionMessage');
    }
}
