<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

final class VerifyWebhookSignatureResult
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $signature,
        public readonly string $matchedSecret,
        public readonly ?int $timestamp,
        public readonly array $headers
    ) {
    }
}
