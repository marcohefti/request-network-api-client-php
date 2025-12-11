<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\PaymentDetailUpdatedEvent;

final class PaymentDetailFailedEvent extends PaymentDetailUpdatedEvent
{
    public const STATUS = 'failed';
}
