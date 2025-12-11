<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

class ComplianceUpdatedEvent extends AbstractWebhookEvent
{
    public const EVENT = 'compliance.updated';

    public static function eventName(): string
    {
        return self::EVENT;
    }

    public function kycStatus(): ?string
    {
        return $this->extractString('kycStatus');
    }

    public function agreementStatus(): ?string
    {
        return $this->extractString('agreementStatus');
    }

    public function isCompliant(): ?bool
    {
        return $this->extractBool('isCompliant');
    }
}
