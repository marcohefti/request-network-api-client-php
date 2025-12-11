<?php

declare(strict_types=1);

use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

require __DIR__ . '/../vendor/autoload.php';

$secret = getenv('REQUEST_WEBHOOK_SECRET') ?: 'rk_test_secret';

// Simulated incoming webhook payload.
$payload = [
    'event' => 'payment.confirmed',
    'requestId' => 'req_example',
    'amount' => '100',
];

$rawBody = json_encode($payload, JSON_THROW_ON_ERROR);

$timestamp = (string) (int) (microtime(true) * 1000);
$algorithm = WebhookSignatureVerifier::DEFAULT_SIGNATURE_ALGORITHM;
$signature = hash_hmac($algorithm, $rawBody, $secret);

$headers = [
    'x-request-network-timestamp' => $timestamp,
    'x-request-network-signature' => $algorithm . '=' . $signature,
];

$verifier = new WebhookSignatureVerifier();
$parser = new WebhookParser();

try {
    $verification = $verifier->verify($rawBody, $headers, [
        'secret' => $secret,
        'timestampHeader' => 'x-request-network-timestamp',
        'toleranceMs' => 5 * 60 * 1000,
    ]);

    $parsed = $parser->parse([
        'rawBody' => $rawBody,
        'headers' => $headers,
        'secret' => [$secret],
        'timestampHeader' => 'x-request-network-timestamp',
        'toleranceMs' => 5 * 60 * 1000,
    ]);
} catch (RequestWebhookSignatureException $exception) {
    fwrite(STDERR, "Webhook signature verification failed: {$exception->getMessage()}\n");
    exit(1);
}

echo "Webhook verified. Signature: {$verification->signature}\n";

$event = $parsed->event();

if ($event instanceof PaymentConfirmedEvent) {
    $data = $event->data();
    $requestId = $data->string('requestId', 'requestID');
    $amount = $data->string('amount');

    echo "Received payment.confirmed for request {$requestId}, amount={$amount}\n";
} else {
    echo "Received event: " . get_class($event) . "\n";
}

