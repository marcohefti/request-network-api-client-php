<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation\Overrides;

use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;

final class OverrideRegistrar
{
    private RequestStatusOverride $requestOverride;

    private RequestPaymentRoutesOverride $routesOverride;

    private PayerComplianceOverride $complianceOverride;

    public function __construct(
        ?RequestStatusOverride $requestOverride = null,
        ?RequestPaymentRoutesOverride $routesOverride = null,
        ?PayerComplianceOverride $complianceOverride = null
    ) {
        $this->requestOverride = $requestOverride ?? new RequestStatusOverride();
        $this->routesOverride = $routesOverride ?? new RequestPaymentRoutesOverride();
        $this->complianceOverride = $complianceOverride ?? new PayerComplianceOverride();
    }

    public static function defaults(): self
    {
        return new self();
    }

    public function registerDefaults(SchemaRegistry $registry): void
    {
        $this->requestOverride->register($registry);
        $this->routesOverride->register($registry);
        $this->complianceOverride->register($registry);
    }
}
