<?php

declare(strict_types=1);

use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Exception\RequestApiException;
use RequestSuite\RequestPhpClient\Core\Exception\TransportException;
use RequestSuite\RequestPhpClient\RequestClient;
use RequestSuite\RequestPhpClient\Validation\SchemaValidationException;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Error Handling Patterns
 *
 * Demonstrates proper exception handling for different error scenarios.
 */

echo "=== Error Handling Examples ===\n\n";

// Example 1: Configuration Errors
echo "1. Configuration Error Handling\n";
echo "---\n";

try {
    // Missing required API key
    $client = RequestClient::create([]);
} catch (ConfigurationException $e) {
    echo "✓ Caught configuration error:\n";
    echo "  {$e->getMessage()}\n\n";
}

// Example 2: API Errors with Error Details
echo "2. API Error with Details\n";
echo "---\n";

$apiKey = getenv('REQUEST_API_KEY') ?: 'rk_test_invalid_key_for_demo';

try {
    $client = RequestClient::create(['apiKey' => $apiKey]);

    // Intentionally create a request with invalid data
    $client->requests()->create([
        'amount' => 'invalid',  // Should be numeric string
        'invoiceCurrency' => 'INVALID_CURRENCY',
    ]);
} catch (RequestApiException $e) {
    echo "✓ Caught API error:\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status Code: {$e->statusCode()}\n";
    echo "  Error Code: {$e->errorCode()}\n";

    if ($e->requestId()) {
        echo "  Request ID: {$e->requestId()}\n";
    }

    if ($e->correlationId()) {
        echo "  Correlation ID: {$e->correlationId()}\n";
    }

    if ($e->retryAfterMs()) {
        echo "  Retry After: {$e->retryAfterMs()}ms\n";
    }

    // Access detailed error information
    $errorArray = $e->toArray();
    if (isset($errorArray['errors'])) {
        echo "  Detailed Errors:\n";

        foreach ($errorArray['errors'] as $error) {
            echo "    - " . ($error['message'] ?? 'Unknown error') . "\n";

            if (isset($error['path'])) {
                echo "      Path: {$error['path']}\n";
            }
        }
    }

    echo "\n";
}

// Example 3: Transport Errors (Network Issues)
echo "3. Transport Error Handling\n";
echo "---\n";

try {
    $client = RequestClient::create([
        'apiKey' => 'rk_test_demo',
        'baseUrl' => 'https://invalid-domain-that-does-not-exist.example',
    ]);

    $client->requests()->create(['amount' => '100']);
} catch (TransportException $e) {
    echo "✓ Caught transport error:\n";
    echo "  {$e->getMessage()}\n\n";
}

// Example 4: Schema Validation Errors
echo "4. Schema Validation Error Handling\n";
echo "---\n";

try {
    $client = RequestClient::create([
        'apiKey' => 'rk_test_demo',
        'runtimeValidation' => true,
    ]);

    // Missing required fields
    $client->requests()->create([
        'amount' => '100',
        // Missing: invoiceCurrency, paymentCurrency, payee
    ]);
} catch (SchemaValidationException $e) {
    echo "✓ Caught validation error:\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Validation Errors:\n";

    foreach ($e->errors() as $error) {
        echo "    - Field: " . ($error['path'] ?? 'unknown') . "\n";
        echo "      Issue: " . ($error['message'] ?? 'validation failed') . "\n";
    }

    echo "\n";
}

// Example 5: Comprehensive Error Handling Pattern
echo "5. Production Error Handling Pattern\n";
echo "---\n";

function createInvoiceWithErrorHandling(RequestClient $client, array $data): ?array
{
    try {
        $request = $client->requests()->create($data);
        echo "✓ Invoice created: {$request['requestId']}\n";

        return $request;
    } catch (SchemaValidationException $e) {
        // Validation errors - likely a client-side issue
        error_log("Validation error creating invoice: {$e->getMessage()}");
        echo "❌ Validation failed:\n";

        foreach ($e->errors() as $error) {
            echo "  - {$error['path']}: {$error['message']}\n";
        }

        return null;
    } catch (RequestApiException $e) {
        // API errors - server-side or data issues
        error_log("API error creating invoice: {$e->getMessage()}");

        if ($e->statusCode() === 401 || $e->statusCode() === 403) {
            echo "❌ Authentication/Authorization error\n";
            echo "  Check your API key and permissions\n";
        } elseif ($e->statusCode() === 429) {
            echo "❌ Rate limit exceeded\n";
            $retryAfter = $e->retryAfterMs() ?? 60000;
            echo "  Retry after: " . ($retryAfter / 1000) . " seconds\n";
        } elseif ($e->statusCode() >= 500) {
            echo "❌ Server error\n";
            echo "  The Request Network API may be experiencing issues\n";
            echo "  Correlation ID: {$e->correlationId()}\n";
        } else {
            echo "❌ API error: {$e->getMessage()}\n";
            echo "  Status: {$e->statusCode()}\n";
            echo "  Error Code: {$e->errorCode()}\n";
        }

        return null;
    } catch (TransportException $e) {
        // Network errors
        error_log("Transport error creating invoice: {$e->getMessage()}");
        echo "❌ Network error\n";
        echo "  Could not connect to Request Network API\n";
        echo "  Check your internet connection and firewall settings\n";

        return null;
    } catch (\Throwable $e) {
        // Unexpected errors
        error_log("Unexpected error creating invoice: {$e->getMessage()}");
        echo "❌ Unexpected error: {$e->getMessage()}\n";

        return null;
    }
}

// Test the comprehensive error handler
if ($apiKey !== 'rk_test_invalid_key_for_demo') {
    $client = RequestClient::create(['apiKey' => $apiKey]);

    $result = createInvoiceWithErrorHandling($client, [
        'amount' => '99.99',
        'invoiceCurrency' => 'USD',
        'paymentCurrency' => 'USDC-sepolia',
        'payee' => '0x1234567890123456789012345678901234567890',
        'reference' => 'example-invoice',
    ]);

    if ($result !== null) {
        echo "  Processing successful!\n";
    }
}

echo "\n=== Error Handling Best Practices ===\n\n";
echo "1. Always catch specific exception types first\n";
echo "2. Log errors with context (correlation IDs, request IDs)\n";
echo "3. Provide user-friendly error messages\n";
echo "4. Handle rate limiting with appropriate retry delays\n";
echo "5. Distinguish between client errors (4xx) and server errors (5xx)\n";
echo "6. Use validation errors to improve data quality\n";
echo "7. Have a fallback for unexpected errors\n";
