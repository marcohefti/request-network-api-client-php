<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Payer;

use JsonException;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;

final class ComplianceStatusFormatter
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function summary(array $payload): string
    {
        /** @var mixed $kycStatus */
        $kycStatus = $payload['kycStatus'] ?? null;
        /** @var mixed $agreementStatus */
        $agreementStatus = $payload['agreementStatus'] ?? null;

        $parts = [
            sprintf('KYC: %s', self::normaliseStatus($kycStatus)),
            sprintf('Agreement: %s', self::normaliseStatus($agreementStatus)),
        ];

        /** @var mixed $clientUserIdRaw */
        $clientUserIdRaw = $payload['clientUserId'] ?? null;
        $clientUserId = self::normaliseClientUserId($clientUserIdRaw);
        if ($clientUserId !== null) {
            $parts[] = sprintf('Client user: %s', $clientUserId);
        }

        return implode(' | ', $parts);
    }

    /**
     * Attempts to derive a compliance summary from an API exception payload.
     */
    public static function summaryFromException(RequestApiException $exception): ?string
    {
        $detail = $exception->detail();
        if (is_array($detail)) {
            $summary = self::maybeSummarise($detail);
            if ($summary !== null) {
                return $summary;
            }
        }

        $payload = $exception->context()->payload();
        if (is_array($payload)) {
            $summary = self::maybeSummarise($payload);
            if ($summary !== null) {
                return $summary;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function maybeSummarise(array $payload): ?string
    {
        $kyc = $payload['kycStatus'] ?? null;
        $agreement = $payload['agreementStatus'] ?? null;
        if ($kyc === null && $agreement === null) {
            return null;
        }

        return self::summary([
            'kycStatus' => $kyc,
            'agreementStatus' => $agreement,
            'clientUserId' => $payload['clientUserId'] ?? null,
        ]);
    }

    private static function normaliseStatus(mixed $value): string
    {
        $string = self::extractString($value);
        if ($string === null || $string === '') {
            return 'unknown';
        }

        return str_replace('_', ' ', strtolower($string));
    }

    private static function normaliseClientUserId(mixed $value): ?string
    {
        $string = self::extractString($value);
        if ($string !== null && $string !== '') {
            return $string;
        }

        if ($value === null) {
            return null;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    private static function extractString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function __construct()
    {
    }
}
