<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use RequestSuite\RequestPhpClient\Webhooks\Events\Internal\WebhookEventInterface;

final class ParsedWebhookEvent
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly WebhookEventInterface $event,
        private readonly string $rawBody,
        private readonly array $headers,
        private readonly ?string $signature,
        private readonly ?string $matchedSecret,
        private readonly ?int $timestamp
    ) {
    }

    public function event(): WebhookEventInterface
    {
        return $this->event;
    }

    public function eventName(): string
    {
        return $this->event->name();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->event->payload();
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function signature(): ?string
    {
        return $this->signature;
    }

    public function matchedSecret(): ?string
    {
        return $this->matchedSecret;
    }

    public function timestamp(): ?int
    {
        return $this->timestamp;
    }
}
