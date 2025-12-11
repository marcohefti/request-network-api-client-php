<?php

declare(strict_types=1);

use RequestSuite\RequestPhpClient\RequestClient;

require __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('REQUEST_API_KEY') ?: '';

if ($apiKey === '') {
    fwrite(STDERR, "REQUEST_API_KEY is required\n");
    exit(1);
}

$client = RequestClient::create([
    'apiKey' => $apiKey,
]);

echo "Creating example request...\n";

$request = $client->requests()->create([
    'amount' => '100',
    'invoiceCurrency' => 'USD',
    'paymentCurrency' => 'USDC-sepolia',
    'payee' => '0x0000000000000000000000000000000000000000',
    'reference' => 'example-order-1',
]);

$requestId = $request['requestId'] ?? null;

echo "Created request. requestId=" . ($requestId ?? '(none)') . PHP_EOL;

