<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Exceptions;

use RuntimeException;
use Throwable;

final class RequestWebhookSignatureException extends RuntimeException
{
    public const CODE = 'ERR_REQUEST_WEBHOOK_SIGNATURE_VERIFICATION_FAILED';

    public readonly string $headerName;

    public readonly ?string $signature;

    public readonly ?int $timestamp;

    public readonly string $reason;

    public readonly int $statusCode;

    public function __construct(
        string $message,
        string $headerName,
        string $reason,
        ?string $signature = null,
        ?int $timestamp = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->headerName = $headerName;
        $this->reason = $reason;
        $this->signature = $signature;
        $this->timestamp = $timestamp;
        $this->statusCode = 401;
    }

    public function errorCode(): string
    {
        return self::CODE;
    }

    public static function isSignatureException(Throwable $throwable): bool
    {
        return $throwable instanceof self;
    }
}
