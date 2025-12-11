<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Exception;

use RuntimeException;
use Throwable;

class RequestApiException extends RuntimeException
{
    public const ERROR_NAME = 'RequestApiError';

    private ?int $statusCode;

    private ?string $errorCode;

    private ?string $requestId;

    private ?string $correlationId;

    private ?int $retryAfterMs;

    private mixed $detail;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $errors;

    private RequestApiExceptionContext $context;

    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?string $correlationId = null,
        ?array $payload = null,
        ?int $retryAfterMs = null,
        ?Throwable $previous = null,
        ?RequestApiExceptionContext $context = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);

        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->correlationId = $correlationId;
        $this->retryAfterMs = $retryAfterMs;
        $this->context = $this->buildContext($context, $payload);

        $payloadData = $this->context->payload();
        $errors = $payloadData['errors'] ?? null;
        $this->detail = is_array($payloadData) ? ($payloadData['detail'] ?? null) : null;
        $this->errors = $this->normaliseErrors($errors);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function retryAfterMs(): ?int
    {
        return $this->retryAfterMs;
    }

    public function detail(): mixed
    {
        return $this->detail;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function errors(): ?array
    {
        return $this->errors;
    }

    public function context(): RequestApiExceptionContext
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => self::ERROR_NAME,
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
            'code' => $this->errorCode,
            'detail' => $this->detail,
            'errors' => $this->errors,
            'requestId' => $this->requestId,
            'correlationId' => $this->correlationId,
            'retryAfterMs' => $this->retryAfterMs,
            'headers' => $this->context->headers(),
            'meta' => $this->context->meta(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function normaliseErrors(mixed $errors): ?array
    {
        if (! is_array($errors)) {
            return null;
        }

        $normalised = [];

        foreach ($errors as $error) {
            $entry = $this->normaliseErrorEntry($error);
            if ($entry !== null) {
                $normalised[] = $entry;
            }
        }

        return $normalised === [] ? null : $normalised;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normaliseErrorEntry(mixed $error): ?array
    {
        if (! is_array($error)) {
            return null;
        }

        $entry = $this->pickStringValues($error, ['message', 'code', 'field']);
        $source = $this->normaliseErrorSource($error['source'] ?? null);
        if ($source !== null) {
            $entry['source'] = $source;
        }

        if (isset($error['meta']) && is_array($error['meta'])) {
            $entry['meta'] = $error['meta'];
        }

        return $entry === [] ? null : $entry;
    }

    /**
     * @return array<string, string>|null
     */
    private function normaliseErrorSource(mixed $source): ?array
    {
        if (! is_array($source)) {
            return null;
        }

        $normalised = $this->pickStringValues($source, ['pointer', 'parameter']);

        return $normalised === [] ? null : $normalised;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    private function pickStringValues(array $input, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $candidate = $input[$key] ?? null;
            if (is_string($candidate) && $candidate !== '') {
                $values[$key] = $candidate;
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function buildContext(
        ?RequestApiExceptionContext $context,
        ?array $payload
    ): RequestApiExceptionContext {
        if ($context !== null) {
            return $context;
        }

        return new RequestApiExceptionContext($payload, null, null);
    }
}
