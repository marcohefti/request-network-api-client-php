<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests;

final class RequestStatusResult
{
    public readonly string $kind;

    public readonly bool $hasBeenPaid;

    public readonly ?string $paymentReference;

    public readonly ?string $requestId;

    public readonly ?bool $isListening;

    public readonly ?string $txHash;

    public readonly ?string $status;

    /**
     * @var array<int|string, mixed>|null
     */
    public readonly ?array $recurrence;

    public readonly ?string $originalRequestId;

    public readonly ?string $originalPaymentRef;

    public readonly ?bool $isRecurrenceStopped;

    public readonly ?bool $cryptoFiatAvailable;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    public readonly ?array $payments;

    public readonly ?RequestStatusCustomerInfo $customerInfo;

    public readonly ?string $reference;

    /**
     * @param array{
     *   paymentReference?: ?string,
     *   requestId?: ?string,
     *   isListening?: ?bool,
     *   txHash?: ?string,
     *   status?: ?string,
     *   recurrence?: array<int|string, mixed>|null,
     *   originalRequestId?: ?string,
     *   originalRequestPaymentReference?: ?string,
     *   isRecurrenceStopped?: ?bool,
     *   isCryptoToFiatAvailable?: ?bool,
     *   payments?: array<int, array<string, mixed>>|null,
     *   customerInfo?: ?RequestStatusCustomerInfo,
     *   reference?: ?string
     * } $attributes
     */
    public function __construct(
        string $kind,
        bool $hasBeenPaid,
        array $attributes = []
    ) {
        $defaults = [
            'paymentReference' => null,
            'requestId' => null,
            'isListening' => null,
            'txHash' => null,
            'status' => null,
            'recurrence' => null,
            'originalRequestId' => null,
            'originalRequestPaymentReference' => null,
            'isRecurrenceStopped' => null,
            'isCryptoToFiatAvailable' => null,
            'payments' => null,
            'customerInfo' => null,
            'reference' => null,
        ];

        $data = array_merge($defaults, $attributes);

        $this->kind = $kind;
        $this->hasBeenPaid = $hasBeenPaid;
        $this->paymentReference = $data['paymentReference'];
        $this->requestId = $data['requestId'];
        $this->isListening = $data['isListening'];
        $this->txHash = $data['txHash'];
        $this->status = $data['status'];
        $this->recurrence = $data['recurrence'];
        $this->originalRequestId = $data['originalRequestId'];
        $this->originalPaymentRef = $data['originalRequestPaymentReference'];
        $this->isRecurrenceStopped = $data['isRecurrenceStopped'];
        $this->cryptoFiatAvailable = $data['isCryptoToFiatAvailable'];
        $this->payments = $data['payments'];
        $this->customerInfo = $data['customerInfo'];
        $this->reference = $data['reference'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'paymentReference' => $this->paymentReference,
            'requestId' => $this->requestId,
            'isListening' => $this->isListening,
            'txHash' => $this->txHash,
            'hasBeenPaid' => $this->hasBeenPaid,
            'status' => $this->status,
            'recurrence' => $this->recurrence,
            'originalRequestId' => $this->originalRequestId,
            'originalRequestPaymentReference' => $this->originalPaymentRef,
            'isRecurrenceStopped' => $this->isRecurrenceStopped,
            'isCryptoToFiatAvailable' => $this->cryptoFiatAvailable,
            'payments' => $this->payments,
            'customerInfo' => $this->customerInfo?->toArray(),
            'reference' => $this->reference,
        ];
    }
}
