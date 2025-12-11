<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests\V1;

use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusAddress;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusCustomerInfo;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestStatusResult;

final class LegacyRequestStatusNormalizer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function normalize(array $payload): RequestStatusResult
    {
        $hasBeenPaid = $this->boolValue($payload['hasBeenPaid'] ?? null) ?? false;
        $kind = $hasBeenPaid ? 'paid' : 'pending';

        return new RequestStatusResult(
            $kind,
            $hasBeenPaid,
            [
                'paymentReference' => $this->stringValue($payload['paymentReference'] ?? null),
                'requestId' => $this->stringValue($payload['requestId'] ?? null),
                'isListening' => $this->boolValue($payload['isListening'] ?? null),
                'txHash' => $this->stringValue($payload['txHash'] ?? null),
                'status' => $this->stringValue($payload['status'] ?? null),
                'customerInfo' => $this->normalizeCustomerInfo($payload['customerInfo'] ?? null),
                'reference' => $this->stringValue($payload['reference'] ?? null),
            ]
        );
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return $string === '' ? null : $string;
    }

    private function boolValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['true', '1'], true)) {
                return true;
            }
            if (in_array($normalized, ['false', '0'], true)) {
                return false;
            }
        }

        return null;
    }

    private function normalizeCustomerInfo(mixed $value): ?RequestStatusCustomerInfo
    {
        if ($value === null || ! is_array($value)) {
            return null;
        }

        $address = null;
        if (isset($value['address']) && is_array($value['address'])) {
            $address = new RequestStatusAddress(
                $this->stringValue($value['address']['street'] ?? null),
                $this->stringValue($value['address']['city'] ?? null),
                $this->stringValue($value['address']['state'] ?? null),
                $this->stringValue($value['address']['postalCode'] ?? null),
                $this->stringValue($value['address']['country'] ?? null),
            );
        }

        return new RequestStatusCustomerInfo(
            $this->stringValue($value['firstName'] ?? null),
            $this->stringValue($value['lastName'] ?? null),
            $this->stringValue($value['email'] ?? null),
            $address
        );
    }
}
