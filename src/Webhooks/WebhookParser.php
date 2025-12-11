<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaValidationException;
use RequestSuite\RequestPhpClient\Validation\SchemaValidationOptions;
use RequestSuite\RequestPhpClient\Validation\SchemaValidator;
use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\WebhookEventFactory;
use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\WebhookEventInterface;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\WebhookParseException;
use Stringable;

final class WebhookParser
{
    private SchemaValidator $validator;

    private RuntimeValidationConfig $validationConfig;

    private WebhookEventFactory $eventFactory;

    private WebhookSignatureVerifier $signatureVerifier;

    private SchemaKeyFactory $schemaKeys;

    private WebhookSchemaRegistrar $schemaRegistrar;

    private SchemaValidationOptions $schemaOptions;

    public function __construct(
        ?SchemaValidator $validator = null,
        ?RuntimeValidationConfig $validationConfig = null,
        ?WebhookEventFactory $eventFactory = null,
        ?WebhookSignatureVerifier $signatureVerifier = null,
        ?SchemaKeyFactory $schemaKeys = null,
        ?WebhookSchemaRegistrar $schemaRegistrar = null,
        ?SchemaValidationOptions $schemaOptions = null
    ) {
        $this->validator = $validator ?? new SchemaValidator();
        $this->validationConfig = $validationConfig ?? new RuntimeValidationConfig(true, true, true);
        $this->eventFactory = $eventFactory ?? new WebhookEventFactory();
        $this->signatureVerifier = $signatureVerifier ?? new WebhookSignatureVerifier();
        $this->schemaKeys = $schemaKeys ?? new SchemaKeyFactory();
        $this->schemaOptions = $schemaOptions ?? new SchemaValidationOptions();

        $registry = $this->validator->registry();
        $this->schemaRegistrar = $schemaRegistrar ?? new WebhookSchemaRegistrar($registry, $this->schemaKeys);
        $this->schemaRegistrar->registerDefaults();
    }

    /**
     * @param array{
     *     rawBody: string|Stringable|StreamInterface,
     *     headers: array<string, mixed>|MessageInterface,
     *     secret?: string|array<int, string>,
     *     signature?: ?string,
     *     headerName?: string,
     *     toleranceMs?: int,
     *     timestampHeader?: string,
     *     now?: callable(): int,
     *     skipSignatureVerification?: bool
     * } $options
     *
     * @throws WebhookParseException
     * @throws RequestWebhookSignatureException
     * @throws SchemaValidationException
     */
    public function parse(array $options): ParsedWebhookEvent
    {
        $rawBody = $this->stringifyBody($options['rawBody'] ?? null);
        $headersInput = $options['headers'] ?? null;
        if ($headersInput === null) {
            throw new WebhookParseException('Webhook headers are required.');
        }

        $headerBag = new WebhookHeaders($headersInput);

        $skipSignature = (bool) ($options['skipSignatureVerification'] ?? false);
        $verification = null;
        if (! $skipSignature) {
            $secret = $options['secret'] ?? null;
            if ($secret === null || ($secret === [])) {
                throw new WebhookParseException('Webhook secret is required when signature verification is enabled.');
            }

            $verificationOptions = [
                'signature' => $options['signature'] ?? null,
            ];

            if (isset($options['headerName'])) {
                $verificationOptions['headerName'] = (string) $options['headerName'];
            }

            if (isset($options['toleranceMs'])) {
                $verificationOptions['toleranceMs'] = (int) $options['toleranceMs'];
            }

            if (isset($options['timestampHeader'])) {
                $verificationOptions['timestampHeader'] = (string) $options['timestampHeader'];
            }

            if (isset($options['now']) && is_callable($options['now'])) {
                $verificationOptions['now'] = $options['now'];
            }

            $verification = $this->signatureVerifier->verifySignature(
                $rawBody,
                $secret,
                $headersInput,
                $verificationOptions
            );
        }

        $normalisedHeaders = $verification?->headers ?? $headerBag->normalised();
        $headerName = $options['headerName'] ?? WebhookSignatureVerifier::DEFAULT_SIGNATURE_HEADER;
        $signature = $verification?->signature
            ?? $options['signature']
            ?? $headerBag->pick($headerName);

        $matchedSecret = $verification?->matchedSecret ?? null;
        $timestamp = $verification?->timestamp ?? null;

        $payload = $this->decodePayload($rawBody);
        $eventName = $this->resolveEventName($payload);

        $validatedPayload = $this->validatePayload($eventName, $payload);
        $event = $this->hydrateEvent($eventName, $validatedPayload);

        return new ParsedWebhookEvent(
            $event,
            $rawBody,
            $normalisedHeaders,
            $signature,
            $matchedSecret,
            $timestamp
        );
    }

    private function stringifyBody(mixed $rawBody): string
    {
        if ($rawBody instanceof StreamInterface) {
            return (string) $rawBody;
        }

        if ($rawBody instanceof Stringable || is_string($rawBody)) {
            return (string) $rawBody;
        }

        if ($rawBody === null) {
            throw new WebhookParseException('Webhook raw body is required.');
        }

        throw new WebhookParseException('Unsupported raw body type.');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $rawBody): array
    {
        $decoded = json_decode($rawBody, true);
        if (! is_array($decoded)) {
            throw new WebhookParseException('Webhook payload must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveEventName(array $payload): string
    {
        $event = $payload['event'] ?? null;
        if (! is_string($event) || $event === '') {
            throw new WebhookParseException('Webhook payload missing `event` field.');
        }

        if (! $this->eventFactory->supports($event)) {
            throw new WebhookParseException(sprintf('Unknown webhook event: %s', $event));
        }

        return $event;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws SchemaValidationException
     */
    private function validatePayload(string $eventName, array $payload): array
    {
        $options = $this->schemaOptions->withDescription(sprintf('Webhook event %s', $eventName));

        $result = $this->validator->parseWithRegistry(
            $this->schemaKeys->webhook($eventName),
            $payload,
            $this->validationConfig,
            $options
        );

        if (! $result->isSuccess()) {
            $error = $result->error();
            if ($error instanceof SchemaValidationException) {
                throw $error;
            }

            throw new SchemaValidationException(sprintf('Invalid webhook payload for %s', $eventName));
        }

        $data = $result->data();
        if (is_array($data)) {
            return $data;
        }

        $encoded = json_encode($data, JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $converted */
        $converted = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return $converted;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateEvent(string $eventName, array $payload): WebhookEventInterface
    {
        return $this->eventFactory->create($eventName, $payload);
    }
}
