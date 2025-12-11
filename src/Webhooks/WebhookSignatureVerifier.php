<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use Stringable;

final class WebhookSignatureVerifier
{
    public const DEFAULT_SIGNATURE_HEADER = 'x-request-network-signature';
    public const DEFAULT_SIGNATURE_ALGORITHM = 'sha256';

    /**
     * @param string|array<int, string> $secret
     * @param string|array<int, string> $secret
     * @param array<string, mixed>|MessageInterface|null $headers
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    public static function verify(
        string|Stringable|StreamInterface $rawBody,
        string|array $secret,
        array|MessageInterface|null $headers = null,
        array $options = []
    ): VerifyWebhookSignatureResult {
        return self::doVerify($rawBody, $secret, $headers, $options);
    }

    /**
     * @param string|array<int, string> $secret
     * @param array<string, mixed>|MessageInterface|null $headers
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    public function verifySignature(
        string|Stringable|StreamInterface $rawBody,
        string|array $secret,
        array|MessageInterface|null $headers = null,
        array $options = []
    ): VerifyWebhookSignatureResult {
        return self::doVerify($rawBody, $secret, $headers, $options);
    }

    /**
     * @param string|array<int, string> $secret
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    public static function verifyFromRequest(
        ServerRequestInterface $request,
        string|array $secret,
        array $options = []
    ): VerifyWebhookSignatureResult {
        return self::doVerifyFromRequest($request, $secret, $options);
    }

    /**
     * @param string|array<int, string> $secret
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    public function verifyRequest(
        ServerRequestInterface $request,
        string|array $secret,
        array $options = []
    ): VerifyWebhookSignatureResult {
        return self::doVerifyFromRequest($request, $secret, $options);
    }

    /**
     * @param string|array<int, string> $secret
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    private static function doVerifyFromRequest(
        ServerRequestInterface $request,
        string|array $secret,
        array $options = []
    ): VerifyWebhookSignatureResult {
        return self::doVerify($request->getBody(), $secret, $request, $options);
    }

    /**
     * @param string|array<int, string> $secret
     * @param array<string, mixed>|MessageInterface|null $headers
     * @param array{
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestamp?: int|float|null,
     *     timestampHeader?: string,
     *     now?: callable(): int
     * } $options
     */
    private static function doVerify(
        string|Stringable|StreamInterface $rawBody,
        string|array $secret,
        array|MessageInterface|null $headers = null,
        array $options = []
    ): VerifyWebhookSignatureResult {
        $headerName = $options['headerName'] ?? self::DEFAULT_SIGNATURE_HEADER;
        $headerBag = new WebhookHeaders($headers);
        $normalisedHeaders = $headerBag->normalised();

        $signatureValue = $options['signature'] ?? $headerBag->pick($headerName);

        if ($signatureValue === null) {
            throw new RequestWebhookSignatureException(
                sprintf('Missing webhook signature header: %s', $headerName),
                $headerName,
                'missing_signature'
            );
        }

        [$signatureHex, $signatureBinary] = self::parseSignatureValue($signatureValue, $headerName);

        $timestamp = self::resolveTimestamp($options, $headerBag, $headerName);
        self::assertTolerance(
            $timestamp,
            $options['toleranceMs'] ?? null,
            $headerName,
            $signatureHex,
            $options['now'] ?? null
        );

        $body = self::coerceBody($rawBody);
        $secrets = self::normaliseSecrets($secret);

        if ($secrets === []) {
            throw new RequestWebhookSignatureException(
                'No webhook secrets configured',
                $headerName,
                'invalid_signature',
                $signatureHex,
                $timestamp
            );
        }

        $digests = array_map(
            static function (string $candidate) use ($body): string {
                return hash_hmac(
                    self::DEFAULT_SIGNATURE_ALGORITHM,
                    $body,
                    $candidate,
                    true
                );
            },
            $secrets
        );

        $expectedLength = strlen($digests[0]);
        if (strlen($signatureBinary) !== $expectedLength) {
            throw new RequestWebhookSignatureException(
                'Webhook signature length mismatch',
                $headerName,
                'invalid_format',
                $signatureHex,
                $timestamp
            );
        }

        foreach ($digests as $index => $digest) {
            if (hash_equals($signatureBinary, $digest)) {
                return new VerifyWebhookSignatureResult(
                    $signatureHex,
                    $secrets[$index],
                    $timestamp,
                    $normalisedHeaders
                );
            }
        }

        throw new RequestWebhookSignatureException(
            'Invalid webhook signature',
            $headerName,
            'invalid_signature',
            $signatureHex,
            $timestamp
        );
    }

    private static function coerceBody(string|Stringable|StreamInterface $rawBody): string
    {
        if ($rawBody instanceof StreamInterface) {
            return (string) $rawBody;
        }

        return (string) $rawBody;
    }

    /**
     * @param string|array<int, string> $secret
     * @return list<string>
     */
    private static function normaliseSecrets(string|array $secret): array
    {
        if (is_string($secret)) {
            return [$secret];
        }

        $secrets = [];
        foreach ($secret as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $secrets[] = $candidate;
        }

        return $secrets;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function parseSignatureValue(string $signature, string $headerName): array
    {
        $stripped = self::stripAlgorithmPrefix($signature, $headerName);
        $trimmed = trim($stripped);

        if ($trimmed === '' || preg_match('/^[0-9a-f]+$/i', $trimmed) !== 1) {
            throw new RequestWebhookSignatureException(
                'Invalid webhook signature format',
                $headerName,
                'invalid_format',
                $signature
            );
        }

        $lower = strtolower($trimmed);
        if (strlen($lower) % 2 !== 0) {
            throw new RequestWebhookSignatureException(
                'Invalid webhook signature length',
                $headerName,
                'invalid_format',
                $signature
            );
        }

        $binary = hex2bin($lower);
        if ($binary === false) {
            throw new RequestWebhookSignatureException(
                'Invalid webhook signature format',
                $headerName,
                'invalid_format',
                $signature
            );
        }

        return [$lower, $binary];
    }

    private static function stripAlgorithmPrefix(string $signature, string $headerName): string
    {
        $trimmed = trim($signature);
        $equalityIndex = strpos($trimmed, '=');

        if ($equalityIndex === false) {
            return $trimmed;
        }

        $prefix = strtolower(substr($trimmed, 0, $equalityIndex));
        if ($prefix !== self::DEFAULT_SIGNATURE_ALGORITHM) {
            throw new RequestWebhookSignatureException(
                'Unsupported signature algorithm',
                $headerName,
                'invalid_format',
                $signature
            );
        }

        return substr($trimmed, $equalityIndex + 1);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function resolveTimestamp(
        array $options,
        WebhookHeaders $headerBag,
        string $signatureHeader
    ): ?int {
        if (array_key_exists('timestamp', $options) && $options['timestamp'] !== null) {
            $numeric = (float) $options['timestamp'];
            if (! self::isFinite($numeric)) {
                throw new RequestWebhookSignatureException(
                    'Invalid webhook timestamp',
                    $signatureHeader,
                    'invalid_format',
                    $headerBag->pick($signatureHeader)
                );
            }

            return self::normaliseTimestamp($numeric);
        }

        $timestampHeader = $options['timestampHeader'] ?? null;
        if ($timestampHeader === null) {
            return null;
        }

        $value = $headerBag->pick($timestampHeader);

        if ($value === null) {
            return null;
        }

        $parsed = self::parseTimestamp($value);
        if ($parsed === null) {
            throw new RequestWebhookSignatureException(
                'Invalid webhook timestamp header',
                $timestampHeader,
                'invalid_format',
                $headerBag->pick($signatureHeader)
            );
        }

        return $parsed;
    }

    private static function parseTimestamp(string $value): ?int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^-?\\d+(\\.\\d+)?$/', $trimmed) === 1) {
            $numeric = (float) $trimmed;
            if (! self::isFinite($numeric)) {
                return null;
            }

            return self::normaliseTimestamp($numeric);
        }

        $parsed = strtotime($trimmed);

        return $parsed === false ? null : $parsed * 1000;
    }

    private static function normaliseTimestamp(float $timestamp): int
    {
        if ($timestamp > 1_000_000_000) {
            return (int) floor($timestamp);
        }

        return (int) floor($timestamp * 1000);
    }

    private static function assertTolerance(
        ?int $timestamp,
        ?int $toleranceMs,
        string $headerName,
        string $signature,
        ?callable $now
    ): void {
        if ($timestamp === null || $toleranceMs === null || $toleranceMs < 0) {
            return;
        }

        $clock = $now ?? static fn(): int => (int) floor(microtime(true) * 1000);
        $distance = abs($clock() - $timestamp);

        if ($distance > $toleranceMs) {
            throw new RequestWebhookSignatureException(
                'Webhook signature timestamp outside tolerance',
                $headerName,
                'tolerance_exceeded',
                $signature,
                $timestamp
            );
        }
    }

    private static function isFinite(float $value): bool
    {
        return ! is_infinite($value) && ! is_nan($value);
    }
}
