<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\ComplianceUpdatedEvent;

final class ComplianceApprovedEvent extends ComplianceUpdatedEvent
{
    public const VARIANT = 'approved';
}
