<?php

declare(strict_types=1);

use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\RequestClient;

require __DIR__ . '/../vendor/autoload.php';

/**
 * End-to-end example: Create an invoice, get payment routes, and check status.
 *
 * This demonstrates a complete invoice workflow from creation to payment verification.
 */

$apiKey = getenv('REQUEST_API_KEY') ?: '';

if ($apiKey === '') {
    fwrite(STDERR, "REQUEST_API_KEY environment variable is required\n");
    exit(1);
}

// Initialize the client
$client = RequestClient::create([
    'apiKey' => $apiKey,
]);

try {
    echo "=== Creating Invoice Request ===\n\n";

    // Step 1: Create a payment request (invoice)
    $invoiceData = [
        'amount' => '250.50',
        'invoiceCurrency' => 'USD',
        'paymentCurrency' => 'USDC-sepolia',
        'payee' => '0x1234567890123456789012345678901234567890', // Replace with actual payee address
        'reference' => 'invoice-' . uniqid(),
        'reason' => 'Professional Services - December 2024',
        'dueDate' => date('c', strtotime('+30 days')),
    ];

    echo "Creating invoice with data:\n";
    echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";

    $request = $client->requests()->create($invoiceData);

    $requestId = $request['requestId'] ?? null;

    if ($requestId === null) {
        throw new \RuntimeException('Failed to get requestId from response');
    }

    echo "✓ Invoice created successfully!\n";
    echo "  Request ID: {$requestId}\n\n";

    // Step 2: Get payment routes for a specific payer wallet
    echo "=== Getting Payment Routes ===\n\n";

    $payerWallet = '0x0987654321098765432109876543210987654321'; // Replace with actual payer address

    $routesResponse = $client->requests()->getPaymentRoutes($requestId, [
        'wallet' => $payerWallet,
    ]);

    $routes = $routesResponse['routes'] ?? [];

    echo "✓ Found " . count($routes) . " payment route(s)\n";

    foreach ($routes as $index => $route) {
        echo "\nRoute #" . ($index + 1) . ":\n";
        echo "  Network: " . ($route['network'] ?? 'unknown') . "\n";
        echo "  Currency: " . ($route['currency'] ?? 'unknown') . "\n";
        echo "  To Address: " . ($route['to'] ?? 'unknown') . "\n";

        if (isset($route['value'])) {
            echo "  Amount: " . $route['value'] . "\n";
        }
    }

    echo "\n";

    // Step 3: Check request status
    echo "=== Checking Request Status ===\n\n";

    $status = $client->requests()->getRequestStatus($requestId);

    echo "Status Information:\n";
    echo "  Request ID: " . ($status['requestId'] ?? 'unknown') . "\n";
    echo "  Status: " . ($status['status'] ?? 'unknown') . "\n";
    echo "  Balance: " . ($status['balance'] ?? '0') . "\n";
    echo "  Currency: " . ($status['currency']['value'] ?? 'unknown') . "\n";

    if (isset($status['expectedAmount'])) {
        echo "  Expected Amount: " . $status['expectedAmount'] . "\n";
    }

    if (isset($status['payer'])) {
        echo "  Payer: " . ($status['payer']['value'] ?? 'not set') . "\n";
    }

    echo "\n";

    // Step 4: Search for payments (if any)
    echo "=== Searching for Payments ===\n\n";

    $payments = $client->payments()->search(['requestId' => $requestId]);

    $paymentCount = count($payments);
    echo "✓ Found {$paymentCount} payment(s)\n";

    foreach ($payments as $index => $payment) {
        echo "\nPayment #" . ($index + 1) . ":\n";
        echo "  Amount: " . ($payment['amount'] ?? 'unknown') . "\n";
        echo "  Transaction Hash: " . ($payment['txHash'] ?? 'pending') . "\n";
        echo "  Status: " . ($payment['status'] ?? 'unknown') . "\n";

        if (isset($payment['timestamp'])) {
            $date = date('Y-m-d H:i:s', $payment['timestamp']);
            echo "  Timestamp: {$date}\n";
        }
    }

    echo "\n=== Invoice Workflow Complete ===\n";
    echo "\nNext steps:\n";
    echo "1. Share payment routes with the payer\n";
    echo "2. Set up webhook listeners to receive payment notifications\n";
    echo "3. Monitor request status for payment confirmation\n";
} catch (RequestApiException $e) {
    fwrite(STDERR, "\n❌ API Error occurred:\n");
    fwrite(STDERR, "  Message: {$e->getMessage()}\n");
    fwrite(STDERR, "  Status Code: {$e->statusCode()}\n");
    fwrite(STDERR, "  Error Code: {$e->errorCode()}\n");

    if ($e->requestId()) {
        fwrite(STDERR, "  Request ID: {$e->requestId()}\n");
    }

    if ($e->correlationId()) {
        fwrite(STDERR, "  Correlation ID: {$e->correlationId()}\n");
    }

    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, "\n❌ Unexpected error: {$e->getMessage()}\n");
    fwrite(STDERR, "  File: {$e->getFile()}:{$e->getLine()}\n");
    exit(1);
}
