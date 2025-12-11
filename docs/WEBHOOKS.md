# Webhooks

The PHP client ships a dedicated webhook module that mirrors the TypeScript SDK: signature
verification, typed event objects, a parser, dispatcher, PSR‑15 middleware, and testing helpers.

## Signature Verification

Use `WebhookSignatureVerifier` to validate the HMAC signature and timestamp before parsing:

```php
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

$secret = $_ENV['REQUEST_WEBHOOK_SECRET'] ?? '';

if ($secret === '') {
    throw new \RuntimeException('REQUEST_WEBHOOK_SECRET must be configured.');
}

$verifier = new WebhookSignatureVerifier();

try {
    $result = $verifier->verifyFromRequest(
        $psrRequest, // \Psr\Http\Message\ServerRequestInterface
        [$secret],   // support rolling secrets
        [
            'timestampHeader' => 'x-request-network-timestamp',
            'toleranceMs' => 5 * 60 * 1000, // 5 minutes
        ]
    );

    // $result->signature (hex), $result->matchedSecret, $result->timestamp, $result->headers
} catch (RequestWebhookSignatureException $exception) {
    // Surface as 401 or log replay attempts based on $exception->reason
    throw $exception;
}
```

`WebhookSignatureVerifier::verify()` also accepts a raw JSON string and an associative header array for
non‑PSR contexts:

```php
$rawBody = (string) $psrRequest->getBody();
$headers = [
    'x-request-network-signature' => $psrRequest->getHeaderLine('x-request-network-signature'),
    'x-request-network-timestamp' => $psrRequest->getHeaderLine('x-request-network-timestamp'),
];

$result = $verifier->verify($rawBody, $headers, ['secret' => $secret]);
```

## Parsing Typed Events

After verification, `WebhookParser` deserialises the payload, validates it against the shared webhook
spec, and hydrates event value objects:

```php
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;

$parser = new WebhookParser();

$parsed = $parser->parse([
    'rawBody' => (string) $psrRequest->getBody(),
    'headers' => $psrRequest,
    'secret' => [$secret],
    'timestampHeader' => 'x-request-network-timestamp',
    'toleranceMs' => 5 * 60 * 1000,
]);

if ($parsed->event() instanceof PaymentConfirmedEvent) {
    $data = $parsed->event()->data();
    $requestId = $data->string('requestId', 'requestID');
    $amount = $data->string('amount');
}
```

The parser returns a `ParsedWebhookEvent` with:

- `event()` – typed event object (`PaymentConfirmedEvent`, `PaymentFailedEvent`, `ComplianceUpdatedEvent`, …).
- `rawBody()` – original JSON string.
- `headers()` – normalised headers.
- `signature()` – extracted signature (if available).
- `matchedSecret()` / `timestamp()` – verification metadata.

Set `skipSignatureVerification => true` in the options when you want to validate payload shape only
(for example, in tests or when an upstream reverse proxy already verified signatures).

## Dispatcher & PSR‑15 Middleware

`WebhookDispatcher` lets you register event handlers by name, and `WebhookMiddleware` wires verification,
parsing, and dispatching into a PSR‑15 stack:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookDispatcher;
use RequestSuite\RequestPhpClient\Webhooks\WebhookMiddleware;

$dispatcher = new WebhookDispatcher();

$dispatcher->registerListener('payment.confirmed', function (PaymentConfirmedEvent $event): void {
    $data = $event->data();
    $requestId = $data->string('requestId', 'requestID');
    // …update local state…
});

$psr17 = new Psr17Factory();

$middleware = new WebhookMiddleware([
    'secret' => $_ENV['REQUEST_WEBHOOK_SECRET'],
    'dispatcher' => $dispatcher,
], $psr17, $psr17);
```

Mount the middleware in your framework (e.g., Symfony HTTP kernel, Mezzio, Slim) where you receive the
Request Network webhook, then implement controllers/handlers that read the parsed event from the PSR‑7
request attributes.

## Testing Helpers

`WebhookTestHelper` mirrors the TypeScript testing utilities:

- `WebhookTestHelper::generateSignature($payload, $secret)` – compute a test signature.
- `WebhookTestHelper::createMockRequest([...])` – fabricate a PSR‑7 request with body + headers.
- `WebhookTestHelper::createMockResponse()` – build an empty PSR‑7 response.
- `WebhookTestHelper::withVerificationDisabled(fn () => ...)` – temporarily bypass verification.

Example:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use RequestSuite\RequestPhpClient\Webhooks\Testing\WebhookTestHelper;
use RequestSuite\RequestPhpClient\Webhooks\WebhookMiddleware;

$psr17 = new Psr17Factory();

$request = WebhookTestHelper::createMockRequest([
    'payload' => ['event' => 'payment.confirmed', 'requestId' => 'req_123'],
    'secret' => 'rk_test_secret',
    'requestFactory' => $psr17,
    'streamFactory' => $psr17,
]);

$response = WebhookTestHelper::createMockResponse($psr17);

WebhookTestHelper::withVerificationDisabled(function () use ($middleware, $request, $handler): void {
    $middleware->process($request, $handler);
});
```

Use the testing helpers inside PHPUnit suites or downstream packages to avoid re‑implementing HMAC
logic and PSR‑7/PSR‑15 boilerplate.

