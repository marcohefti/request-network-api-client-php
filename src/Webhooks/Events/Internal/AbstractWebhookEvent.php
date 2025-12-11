<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

use BadMethodCallException;

abstract class AbstractWebhookEvent implements WebhookEventInterface
{
    private WebhookEventData $data;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(protected readonly array $payload)
    {
        $this->data = new WebhookEventData($payload);
    }

    abstract public static function eventName(): string;

    public function name(): string
    {
        return static::eventName();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function data(): WebhookEventData
    {
        return $this->data;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $map = [
            'requestId' => fn (): ?string => $this->data->string('requestId', 'requestID'),
            'paymentReference' => fn (): ?string => $this->data->string('paymentReference'),
            'explorer' => fn (): ?string => $this->data->string('explorer'),
            'amount' => fn (): ?string => $this->data->string('amount'),
            'totalAmountPaid' => fn (): ?string => $this->data->string('totalAmountPaid'),
            'expectedAmount' => fn (): ?string => $this->data->string('expectedAmount'),
            'timestamp' => fn (): ?string => $this->data->string('timestamp'),
            'txHash' => fn (): ?string => $this->data->string('txHash'),
            'network' => fn (): ?string => $this->data->string('network'),
            'currency' => fn (): ?string => $this->data->string('currency'),
            'paymentCurrency' => fn (): ?string => $this->data->string('paymentCurrency'),
            'isCryptoToFiat' => fn (): ?bool => $this->data->bool('isCryptoToFiat'),
            'subStatus' => fn (): ?string => $this->data->string('subStatus'),
            'paymentProcessor' => fn (): ?string => $this->data->string('paymentProcessor'),
            'fees' => fn (): array => $this->data->listOfArrays('fees'),
            'clientUserId' => fn (): ?string => $this->data->string('clientUserId'),
            'rawPayload' => fn (): ?array => $this->data->array('rawPayload'),
        ];

        if (isset($map[$name])) {
            return $map[$name]();
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    protected function extractString(string $key): ?string
    {
        return $this->data->string($key);
    }

    protected function extractBool(string $key): ?bool
    {
        return $this->data->bool($key);
    }
}
