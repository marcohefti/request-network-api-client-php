# Request PHP Client - Before vs After

This guide shows how common Request REST API flows look in “raw HTTP” PHP code (cURL/PSR‑18) versus
using `RequestSuite\RequestPhpClient\RequestClient`. The goal is to make it obvious what you can
delete once the client is wired in.

## 1) Create Request / Invoice (Server, API key)

### Before (manual HTTP client)

```php
use GuzzleHttp\Client;

$http = new Client([
    'base_uri' => 'https://api.request.network',
    'timeout' => 5,
]);

try {
    $response = $http->post('/v2/request', [
        'headers' => [
            'x-api-key' => $_ENV['REQUEST_API_KEY'],
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'amount' => '1250',
            'invoiceCurrency' => 'USD',
            'paymentCurrency' => 'USDC-sepolia',
            'payee' => '0xpayee',
            'reference' => 'order-123',
        ],
    ]);
} catch (\Throwable $e) {
    // log + rethrow
    throw $e;
}

if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
    $body = (string) $response->getBody();
    $message = 'Failed to create request: HTTP ' . $response->getStatusCode();
    $decoded = json_decode($body, true);
    if (is_array($decoded) && isset($decoded['message'])) {
        $message = (string) $decoded['message'];
    }
    throw new \RuntimeException($message);
}

$payload = json_decode((string) $response->getBody(), true);
$requestId = $payload['requestId'] ?? null;
```

### After (Request PHP client)

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
]);

$request = $client->requests()->create([
    'amount' => '1250',
    'invoiceCurrency' => 'USD',
    'paymentCurrency' => 'USDC-sepolia',
    'payee' => '0xpayee',
    'reference' => 'order-123',
]);

$requestId = $request['requestId'] ?? null;
```

---

## 2) Payment Routes (Server, API key)

### Before

```php
use GuzzleHttp\Client;

$wallet = '0xpayer';
$feePercentage = $_ENV['FEE_PERCENTAGE'] ?? null;
$feeAddress = $_ENV['FEE_ADDRESS'] ?? null;

$query = ['wallet' => $wallet];
if ($feePercentage !== null) {
    $query['feePercentage'] = $feePercentage;
}
if ($feeAddress !== null) {
    $query['feeAddress'] = $feeAddress;
}

$http = new Client(['base_uri' => 'https://api.request.network']);
$response = $http->get('/v2/request/' . rawurlencode($requestId) . '/routes', [
    'headers' => ['x-api-key' => $_ENV['REQUEST_API_KEY']],
    'query' => $query,
]);

if ($response->getStatusCode() !== 200) {
    throw new \RuntimeException('Failed to fetch payment routes');
}

$data = json_decode((string) $response->getBody(), true);
$routes = $data['routes'] ?? [];
$platformFee = $data['platformFee'] ?? null;
```

### After

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
]);

[$routes, $platformFee] = (function () use ($client, $requestId): array {
    $response = $client->requests()->getPaymentRoutes($requestId, [
        'wallet' => '0xpayer',
        'feePercentage' => $_ENV['FEE_PERCENTAGE'] ?? null,
        'feeAddress' => $_ENV['FEE_ADDRESS'] ?? null,
    ]);

    return [$response['routes'] ?? [], $response['platformFee'] ?? null];
})();
```

---

## 3) Compliance (Server, API key)

### Before

```php
use GuzzleHttp\Client;

$http = new Client(['base_uri' => 'https://api.request.network']);
$clientUserId = 'merchant_user_123';

// Submit compliance data
$http->post('/v2/payer', [
    'headers' => [
        'x-api-key' => $_ENV['REQUEST_API_KEY'],
        'Content-Type' => 'application/json',
    ],
    'json' => $formData,
]);

// Get status (404 -> default)
try {
    $resp = $http->get('/v2/payer/' . rawurlencode($clientUserId), [
        'headers' => ['x-api-key' => $_ENV['REQUEST_API_KEY']],
    ]);
    $status = json_decode((string) $resp->getBody(), true);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
        $status = [
            'kycStatus' => 'not_started',
            'agreementStatus' => 'not_started',
            'isCompliant' => false,
        ];
    } else {
        throw $e;
    }
}

// Update agreement
$http->patch('/v2/payer/' . rawurlencode($clientUserId), [
    'headers' => [
        'x-api-key' => $_ENV['REQUEST_API_KEY'],
        'Content-Type' => 'application/json',
    ],
    'json' => ['agreementCompleted' => true],
]);
```

### After

```php
use RequestSuite\RequestPhpClient\Domains\Payer\ComplianceStatusFormatter;
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
]);

$payer = $client->payer();

// Submit compliance data
$payer->createComplianceData($formData);

// Get status
$status = $payer->getComplianceStatus('merchant_user_123');
$summary = ComplianceStatusFormatter::summary([
    'kycStatus' => $status['status']['kycStatus'] ?? null,
    'agreementStatus' => $status['status']['agreementStatus'] ?? null,
    'clientUserId' => $status['userId'] ?? null,
]);

// Update agreement
$payer->updateComplianceStatus('merchant_user_123', [
    'agreementCompleted' => true,
]);
```

---

## 4) Webhook Verification and Parsing

### Before

```php
// Manual HMAC verification + JSON decoding
$rawBody = (string) $psrRequest->getBody();
$signatureHeader = $psrRequest->getHeaderLine('x-request-network-signature');
$timestamp = $psrRequest->getHeaderLine('x-request-network-timestamp');

if ($signatureHeader === '' || $timestamp === '') {
    throw new \RuntimeException('Missing webhook signature headers');
}

[$algo, $signature] = explode('=', $signatureHeader, 2);
$expected = hash_hmac($algo, $rawBody, $_ENV['REQUEST_WEBHOOK_SECRET']);

if (! hash_equals($expected, $signature)) {
    throw new \RuntimeException('Invalid webhook signature');
}

$payload = json_decode($rawBody, true);
if (! is_array($payload) || ! isset($payload['event'])) {
    throw new \RuntimeException('Invalid webhook payload');
}

// Now switch on $payload['event'], manually inspect fields, etc.
```

### After

```php
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

$secret = $_ENV['REQUEST_WEBHOOK_SECRET'] ?? '';

if ($secret === '') {
    throw new \RuntimeException('REQUEST_WEBHOOK_SECRET must be configured.');
}

$verifier = new WebhookSignatureVerifier();
$parser = new WebhookParser();

try {
    $verification = $verifier->verifyFromRequest(
        $psrRequest,
        [$secret],
        [
            'timestampHeader' => 'x-request-network-timestamp',
            'toleranceMs' => 5 * 60 * 1000,
        ]
    );

    $parsed = $parser->parse([
        'rawBody' => (string) $psrRequest->getBody(),
        'headers' => $psrRequest,
        'secret' => [$secret],
        'timestampHeader' => 'x-request-network-timestamp',
        'toleranceMs' => 5 * 60 * 1000,
    ]);
} catch (RequestWebhookSignatureException $exception) {
    // Surface as 401 or log replay attempts based on $exception->reason
    throw $exception;
}

if ($parsed->event() instanceof PaymentConfirmedEvent) {
    $data = $parsed->event()->data();
    $requestId = $data->string('requestId', 'requestID');
    $amount = $data->string('amount');
    // …update local state…
}
```

