<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Events\Internal;

/**
 * @method ?string requestId()
 * @method ?string paymentReference()
 * @method ?string explorer()
 * @method ?string amount()
 * @method ?string totalAmountPaid()
 * @method ?string expectedAmount()
 * @method ?string timestamp()
 * @method ?string txHash()
 * @method ?string network()
 * @method ?string currency()
 * @method ?string paymentCurrency()
 * @method ?bool   isCryptoToFiat()
 * @method ?string subStatus()
 * @method ?string paymentProcessor()
 * @method array<array-key, array<string, mixed>> fees()
 * @method ?string clientUserId()
 * @method array<string, mixed>|null rawPayload()
 */
interface WebhookEventInterface
{
    public static function eventName(): string;

    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    public function data(): WebhookEventData;
}
