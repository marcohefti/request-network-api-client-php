<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\ComplianceUpdatedEvent;

final class CompliancePendingEvent extends ComplianceUpdatedEvent
{
    public const VARIANT = 'pending';
}
