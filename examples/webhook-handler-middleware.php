<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\ComplianceRejectedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentFailedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookDispatcher;
use RequestSuite\RequestPhpClient\Webhooks\WebhookMiddleware;

require __DIR__ . '/../vendor/autoload.php';

/**
 * PSR-15 Middleware Integration Example
 *
 * Demonstrates how to integrate webhook handling into a PSR-15 middleware stack
 * (e.g., Slim, Mezzio, Symfony HttpKernel).
 */

// Configuration
$webhookSecret = getenv('REQUEST_WEBHOOK_SECRET') ?: '';

if ($webhookSecret === '') {
    fwrite(STDERR, "REQUEST_WEBHOOK_SECRET environment variable is required\n");
    exit(1);
}

// Create PSR-17 factories (using Nyholm as an example)
$psr17 = new Psr17Factory();

// Set up the webhook event dispatcher
$dispatcher = new WebhookDispatcher();

// Register event handlers
$dispatcher->registerListener('payment.confirmed', function (PaymentConfirmedEvent $event): void {
    $data = $event->data();
    $requestId = $data->string('requestId', 'requestID');
    $amount = $data->string('amount');
    $txHash = $data->string('txHash', 'transactionHash');

    echo "[payment.confirmed] Request {$requestId} paid {$amount}\n";
    echo "  Transaction: {$txHash}\n";

    // Your business logic here:
    // - Update database order status
    // - Send confirmation email
    // - Trigger fulfillment process
    updateOrderStatus($requestId, 'paid', $amount, $txHash);
});

$dispatcher->registerListener('payment.failed', function (PaymentFailedEvent $event): void {
    $data = $event->data();
    $requestId = $data->string('requestId', 'requestID');
    $reason = $data->string('reason', 'error');

    echo "[payment.failed] Request {$requestId} failed\n";
    echo "  Reason: {$reason}\n";

    // Your business logic here:
    // - Notify customer of failure
    // - Log for investigation
    // - Retry payment if appropriate
    handlePaymentFailure($requestId, $reason);
});

$dispatcher->registerListener('compliance.approved', function (ComplianceApprovedEvent $event): void {
    $data = $event->data();
    $userId = $data->string('clientUserId', 'userId');

    echo "[compliance.approved] User {$userId} is now compliant\n";

    // Your business logic here:
    // - Enable payment features for user
    // - Send welcome email
    // - Update user permissions
    enablePaymentFeatures($userId);
});

$dispatcher->registerListener('compliance.rejected', function (ComplianceRejectedEvent $event): void {
    $data = $event->data();
    $userId = $data->string('clientUserId', 'userId');
    $reason = $data->string('reason', 'status');

    echo "[compliance.rejected] User {$userId} compliance rejected\n";
    echo "  Reason: {$reason}\n";

    // Your business logic here:
    // - Notify user of rejection
    // - Request additional documentation
    // - Disable payment features
    handleComplianceRejection($userId, $reason);
});

// Create the webhook middleware
$webhookMiddleware = new WebhookMiddleware([
    'secret' => $webhookSecret,
    'dispatcher' => $dispatcher,
    'timestampHeader' => 'x-request-network-timestamp',
    'toleranceMs' => 5 * 60 * 1000, // 5 minutes
], $psr17, $psr17);

// Create a simple request handler that processes the webhook after middleware
class WebhookHandler implements RequestHandlerInterface
{
    public function __construct(private Psr17Factory $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // The middleware has already verified and dispatched the webhook
        // The parsed event is attached to the request attributes
        $parsedEvent = $request->getAttribute('webhook_event');

        if ($parsedEvent !== null) {
            echo "\n✓ Webhook processed successfully\n";
            echo "  Event type: " . $parsedEvent->eventName() . "\n";

            return $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/json');
        }

        return $this->responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json');
    }
}

// Simulate an incoming webhook request
// In a real application, this would come from your web server
function simulateWebhookRequest(Psr17Factory $factory, string $secret): ServerRequestInterface
{
    $payload = [
        'event' => 'payment.confirmed',
        'requestId' => 'req_example_123',
        'amount' => '100.50',
        'txHash' => '0xabcdef1234567890',
        'timestamp' => time(),
    ];

    $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) (int) (microtime(true) * 1000);

    // Generate valid signature
    $signature = hash_hmac('sha256', $rawBody, $secret);

    return $factory->createServerRequest('POST', '/webhooks/request-network')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('x-request-network-signature', 'sha256=' . $signature)
        ->withHeader('x-request-network-timestamp', $timestamp)
        ->withBody($factory->createStream($rawBody));
}

// Process the webhook
try {
    echo "=== Webhook Middleware Example ===\n\n";

    // Simulate incoming webhook (in production, this comes from your web server)
    $request = simulateWebhookRequest($psr17, $webhookSecret);

    echo "Received webhook request\n";
    echo "Processing...\n\n";

    // Create handler
    $handler = new WebhookHandler($psr17);

    // Process through middleware
    $response = $webhookMiddleware->process($request, $handler);

    echo "\nResponse status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() === 200) {
        echo "✓ Webhook handled successfully!\n";
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "\n❌ Error: {$e->getMessage()}\n");
    fwrite(STDERR, "  File: {$e->getFile()}:{$e->getLine()}\n");
    exit(1);
}

// Example business logic functions (implement these for your application)

function updateOrderStatus(string $requestId, string $status, string $amount, string $txHash): void
{
    // Example: Update your database
    echo "  → Updating order status in database...\n";
    // $db->query("UPDATE orders SET status = ?, paid_amount = ?, tx_hash = ? WHERE request_id = ?",
    //     [$status, $amount, $txHash, $requestId]);
}

function handlePaymentFailure(string $requestId, string $reason): void
{
    // Example: Log failure and notify customer
    echo "  → Logging payment failure...\n";
    // error_log("Payment failed for request {$requestId}: {$reason}");
    // sendCustomerEmail($requestId, 'payment_failed', ['reason' => $reason]);
}

function enablePaymentFeatures(string $userId): void
{
    // Example: Update user permissions
    echo "  → Enabling payment features for user...\n";
    // $db->query("UPDATE users SET can_receive_payments = TRUE WHERE id = ?", [$userId]);
}

function handleComplianceRejection(string $userId, string $reason): void
{
    // Example: Notify user and request additional info
    echo "  → Notifying user of compliance rejection...\n";
    // sendEmail($userId, 'compliance_rejected', ['reason' => $reason]);
}
