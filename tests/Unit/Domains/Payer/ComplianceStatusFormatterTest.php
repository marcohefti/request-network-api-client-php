<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Payer;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Domains\Payer\ComplianceStatusFormatter;

final class ComplianceStatusFormatterTest extends TestCase
{
    public function testSummaryFormatsStatuses(): void
    {
        $summary = ComplianceStatusFormatter::summary([
            'kycStatus' => 'approved',
            'agreementStatus' => 'signed',
            'clientUserId' => 'user-1',
        ]);

        self::assertSame('KYC: approved | Agreement: signed | Client user: user-1', $summary);
    }

    public function testSummaryHandlesPendingAndRejected(): void
    {
        $summary = ComplianceStatusFormatter::summary([
            'kycStatus' => 'pending_review',
            'agreementStatus' => 'rejected',
            'clientUserId' => ['nested' => 'user'],
        ]);

        self::assertSame(
            'KYC: pending review | Agreement: rejected | Client user: {"nested":"user"}',
            $summary
        );
    }

    public function testSummaryFromExceptionFallsBackToPayload(): void
    {
        $exception = new RequestApiException(
            'Forbidden',
            403,
            'compliance_blocked',
            null,
            null,
            ['kycStatus' => 'failed', 'agreementStatus' => 'pending']
        );

        $summary = ComplianceStatusFormatter::summaryFromException($exception);

        self::assertSame('KYC: failed | Agreement: pending', $summary);
    }
}
