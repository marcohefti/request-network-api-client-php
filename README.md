# @marcohefti/request-network-api-client

> PHP client for the Request Network hosted REST API.

This package mirrors the TypeScript `@marcohefti/request-network-api-client` so WooCommerce and other PHP
runtimes can talk to Request without a Node bridge. It shares the same OpenAPI spec, webhook fixtures,
error semantics, and validation behaviour as the TypeScript client.

## Scope & Features

- PHP‑first `RequestClient` with domain facades for requests, payouts, payments, payer/compliance (v1+v2),
  client IDs, currencies (v1+v2), and legacy pay.
- Shared HTTP pipeline with retry, logging, and runtime validation driven by the synced OpenAPI spec.
- Env‑based factory (`RequestClient::createFromEnv()`) that honours `REQUEST_API_URL` / `REQUEST_API_KEY`
  / `REQUEST_CLIENT_ID` (with legacy `REQUEST_SDK_*` fallbacks).
- Webhook utilities: signature verifier, typed event objects, parser, dispatcher, PSR‑15 middleware, and
  testing helpers that mirror the TypeScript webhook module.
- Testing harness: PHPUnit unit suites, PHPStan, PHPCS/PHPMD, and OpenAPI/webhook parity scripts wired
  into the workspace validator.

### When to use this client

- Use this client when you are in a PHP runtime (WooCommerce, Laravel, Symfony, custom PSR‑15 stack) and
  want to call the Request Network hosted REST API (v2) via API key or client ID.
- It is **not** the Request protocol SDK and does not manage wallets or speak directly to Request Nodes.
  Keep on‑chain/protocol logic in your dapp and call this client for hosted endpoints (requests, payouts,
  payer/compliance, currencies, client IDs, payments).

See the WooCommerce decision log entry dated 2025‑11‑06 for additional background and scope.

## Documentation

- Architecture overview: `docs/ARCHITECTURE.md`
- Testing & validation guide: `docs/TESTING.md`
- Publishing checklist: `docs/PUBLISHING.md`
- Before/After examples: `docs/BEFORE-AFTER.md`
- HTTP client details: `docs/HTTP-CLIENT.md`
- Webhooks guide: `docs/WEBHOOKS.md`

## Examples

Minimal, runnable examples live under `examples/`:

- `examples/quick-start.php` – creates a client from `REQUEST_API_KEY`, creates a sample request, and prints
  the `requestId`.
- `examples/webhook-parse.php` – simulates a signed webhook payload, verifies the signature, parses the event,
  and prints a `payment.confirmed` summary.

## Project Layout

```
.
├── src/
│   ├── Core/          # Config, HTTP pipeline, retry, exceptions
│   ├── Domains/       # Facades mapped to Request API domains (to be populated)
│   ├── Validation/    # Schema registry and parity helpers
│   └── Webhooks/      # Signature utilities, events, dispatcher
├── generated/         # OpenAPI DTOs and validation fragments (codegen output)
├── scripts/           # Spec sync + parity guards
├── specs/             # Synced OpenAPI + webhook fixtures
└── tests/             # PHPUnit suites (Unit, Integration, Parity)
```

## Error handling

- All transport and API failures bubble up as `RequestApiException`, which now exposes structured accessors (`detail()`, `errors()`, `context()`) plus a `toArray()` helper mirroring the TypeScript `toJSON()`.
- `RequestApiErrorBuilder::buildRequestApiError()` mirrors the TypeScript error builder: it inspects JSON payloads, falls back to `HTTP_<status>` codes/messages, and captures `x-request-id`, `x-correlation-id`, and `retry-after` headers.
- Consumers that need type guards can call `RequestApiErrorBuilder::isRequestApiError($value)` to detect either exception instances or serialized arrays returned by `toArray()`.

## Validation

- `Validation\SchemaRegistry` mirrors the TypeScript registry: schemas are keyed by `operationId`, kind (`request`, `response`, `error`, `webhook`), and optional `status`/variant.
- `Validation\SchemaValidator` exposes `parseWithSchema()` and `parseWithRegistry()` helpers built on `opis/json-schema`. Results include structured `SchemaValidationException` objects when validation fails.
- Override fragments live under `src/Validation/Overrides/` to patch API quirks (request status nullability, payment route fee coercion, payer compliance status enums). Overrides register automatically when the registry boots.
- Runtime validation toggles flow through `RuntimeValidationConfig` (requests/responses/errors) so callers can opt out per-request or globally.

## Spec sync & codegen

- `composer update:spec` - copies OpenAPI + webhook fixtures from `/request-network-api-contracts` into `specs/`.
- `composer codegen` - generates `generated/OpenApi/Operations.php` (operation metadata) and the validation schema manifest consumed by `SchemaRegistry`.
- Generated assets live under `generated/`. Commit them alongside code so runtime validation works without extra steps.

## Installation

```sh
composer require marcohefti/request-network-api-client
```

`RequestClient::create(array $options = [])` mirrors the TypeScript factory. Typical usage:

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'clientId' => $_ENV['REQUEST_CLIENT_ID'],
    'origin' => 'woo-plugin',
]);

$request = $client->requests()->create([
    'currency' => 'USD',
    'expectedAmount' => '1000',
    'payer' => [...],
]);

$payments = $client->payments()->search(['requestId' => $request['requestId']]);
```

Use `RequestClient::createFromEnv()` (or the namespaced helper `createRequestClientFromEnv()`) to pull
credentials from environment variables (`REQUEST_API_KEY`, `REQUEST_CLIENT_ID`, `REQUEST_ORIGIN`, and
legacy `REQUEST_SDK_*` fallbacks). All entry points live under the `RequestSuite\RequestPhpClient`
namespace per PSR-4 conventions.
- `$client->http()` exposes the low-level `Core\Http\HttpClient`, letting advanced integrations
  register custom facades or interrogate interceptors directly.

## Usage (Requests API, WIP)

```php
use RequestSuite\RequestPhpClient\RequestClient;
use function RequestSuite\RequestPhpClient\createRequestClient;

$client = createRequestClient([
    'apiKey' => 'rk_...',
    'clientId' => 'merchant_123',
]);
// Or call RequestClient::create([...]) if you prefer the static factory.

$requests = $client->requests();
$response = $requests->create([
    'currency' => 'eur',
    'expectedAmount' => 10000,
    'payer' => ['email' => 'customer@example.com'],
]);

// Legacy (v1) flows for older integrations
$legacy = $client->requestsV1();
$legacy->stopRecurrence('payment_ref_123');

$payer = $client->payer();
$compliance = $payer->createComplianceData([
    'clientUserId' => 'merchant_user_123',
    'email' => 'customer@example.com',
    'firstName' => 'Jane',
    'lastName' => 'Doe',
    'beneficiaryType' => 'individual',
    'addressLine1' => '1 Main St',
    'city' => 'Paris',
    'country' => 'FR',
]);

$statusSummary = \RequestSuite\RequestPhpClient\Domains\Payer\ComplianceStatusFormatter::summary([
    'kycStatus' => $compliance['status']['kycStatus'] ?? null,
    'agreementStatus' => $compliance['status']['agreementStatus'] ?? null,
    'clientUserId' => $compliance['userId'] ?? null,
]);

$legacyPayer = $payer->legacy;
$legacyPayer->getComplianceStatus('legacy_user_123');

$clientIds = $client->clientIds();
$newClient = $clientIds->create([
    'name' => 'example-store',
    'description' => 'WooCommerce shop',
]);
$clientIds->revoke($newClient['id']);

$currencies = $client->currencies();
$tokens = $currencies->list(['network' => 'sepolia']);
$legacyRoutes = $currencies->legacy->getConversionRoutes('USD');

$pay = $client->pay();
$pay->payRequest([
    'currency' => 'eur',
    'expectedAmount' => 2500,
    'paymentReference' => 'legacy_123',
]);
$pay->legacy->payRequest(['paymentReference' => 'legacy_456', 'expectedAmount' => 1000]);

$payouts = $client->payouts();
$payouts->create(['requestId' => 'req_123', 'amount' => 5000]);

$payments = $client->payments();
$payments->search(['wallet' => '0xabc', 'limit' => 20]);
```

The pay facade mirrors the TypeScript `createPayApi` helper: `$client->pay()` proxies to the underlying
legacy implementation and also exposes `$client->pay()->legacy` when an explicit v1 reference is needed.

The payer facade mirrors the TypeScript `createPayerApi` surface: `$client->payer()` proxies to the v2 endpoints
while exposing `$client->payer()->legacy` for explicit v1 routing. `ComplianceStatusFormatter::summary()` and
`::summaryFromException()` help turn compliance responses or `RequestApiException` payloads into human-readable
messages so admin screens can explain why KYC blocks certain actions. Likewise, `$client->clientIds()` mirrors the
TypeScript `createClientIdsApi`, letting Woo provision, rotate, and revoke client identifiers directly, and
`$client->currencies()` exposes the `/v2/currencies` + `/v2/.../conversion-routes` lifecycle with a `.legacy`
facade for the `/v1` endpoints.

## Webhook utilities

```php
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

try {
    $result = WebhookSignatureVerifier::verifyFromRequest(
        $psrRequest, // \Psr\Http\Message\ServerRequestInterface with the raw JSON body
        ['rk_live_old', 'rk_live_new'], // support rolling secrets
        [
            'timestampHeader' => 'x-request-network-timestamp',
            'toleranceMs' => 5 * 60 * 1000, // 5 minutes
        ]
    );

    // $result->signature (lowercase hex), $result->matchedSecret, $result->timestamp, $result->headers
} catch (RequestWebhookSignatureException $exception) {
    if ($exception->reason === 'tolerance_exceeded') {
        // Log drift / replay attempt
    }

    throw $exception; // surface as 401 (statusCode) to the framework
}
```

`WebhookSignatureVerifier::verify()` also accepts raw strings + associative header arrays for non-PSR
contexts. `WebhookHeaders` mirrors the TypeScript helper by normalising headers (first non-empty
value wins, case-insensitive keys) so consumers can re-use it inside custom middleware. The exception
shares the same error code (`ERR_REQUEST_WEBHOOK_SIGNATURE_VERIFICATION_FAILED`) plus structured
fields (`headerName`, `signature`, `timestamp`, `reason`) that match the TS error object for parity.

After verification, `WebhookParser` deserialises the body, validates it against the webhook schemas,
and hydrates typed event objects:

```php
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;

$parser = new WebhookParser();
$parsed = $parser->parse([
    'rawBody' => (string) $psrRequest->getBody(),
    'headers' => $psrRequest,
    'secret' => ['rk_live_old', 'rk_live_new'],
    'timestampHeader' => 'x-request-network-timestamp',
    'toleranceMs' => 5 * 60 * 1000,
]);

if ($parsed->event() instanceof PaymentConfirmedEvent) {
    $data = $parsed->event()->data();
    $requestId = $data->string('requestId', 'requestID');
    $amount = $data->string('amount');
}
```

During local development you can pass `skipSignatureVerification => true` and associative headers
instead of PSR-7 requests. The parser returns a `ParsedWebhookEvent` with the typed payload object,
raw JSON, normalized headers, matched secret, signature, and timestamp metadata so downstream code
can stay strongly typed.

Payment detail updates automatically hydrate the status-specific classes so downstream handlers can
react with simple `instanceof` checks:

```php
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailApprovedEvent;
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentDetailFailedEvent;

if ($parsed->event() instanceof PaymentDetailApprovedEvent) {
    // status === "approved"
}

if ($parsed->event() instanceof PaymentDetailFailedEvent) {
    // status === "failed"
}
```

Likewise, compliance updates promote to `ComplianceApprovedEvent`, `CompliancePendingEvent`, or
`ComplianceRejectedEvent` depending on the payload (`kycStatus`, `agreementStatus`, `isCompliant`).
When statuses fall outside the known fixtures the parser falls back to the base
`ComplianceUpdatedEvent` so new upstream values remain compatible while still passing schema
validation.

### Dispatcher, middleware & testing helpers

`WebhookDispatcher` mirrors the TypeScript event bus and lets you register `on`/`once` handlers:

```php
use RequestSuite\RequestPhpClient\Webhooks\WebhookDispatcher;

$dispatcher = new WebhookDispatcher();
$dispatcher->registerListener('payment.confirmed', function ($event) {
    // handle event
});
```

`WebhookMiddleware` is a PSR-15 middleware that verifies the signature, parses the payload,
dispatches to the optional `WebhookDispatcher`, and attaches the parsed event to the PSR-7
request so frameworks such as Symfony or Laravel can keep their own controller logic simple:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use RequestSuite\RequestPhpClient\Webhooks\WebhookMiddleware;

$psr17 = new Psr17Factory();
$middleware = new WebhookMiddleware([
    'secret' => $_ENV['REQUEST_WEBHOOK_SECRET'],
    'dispatcher' => $dispatcher,
], $psr17, $psr17);
```

For local tests, `WebhookTestHelper` can generate valid signatures, fabricate PSR-7 requests, and
temporarily disable verification:

```php
use RequestSuite\RequestPhpClient\Webhooks\Testing\WebhookTestHelper;

$request = WebhookTestHelper::createMockRequest([
    'payload' => ['event' => 'payment.confirmed'],
    'secret' => 'rk_test_secret',
    'requestFactory' => $psr17,
    'streamFactory' => $psr17,
]);

WebhookTestHelper::withVerificationDisabled(function () use ($middleware, $request, $handler) {
    $middleware->process($request, $handler);
});
```

## Logging & redaction

Pass either a callable `(string $event, array $context) => void` or a PSR-3 `LoggerInterface` via
`RequestClient::create(['logger' => ...])` to receive lifecycle events (`request:start`,
`request:response`, `request:error`). When you supply a PSR-3 logger, the client automatically wraps it with
`Logging\PsrLoggerAdapter`, which redacts sensitive fields (API keys, Authorization headers, webhook signatures) via
`Logging\Redactor` before handing context to your logger:

```php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RequestSuite\RequestPhpClient\RequestClient;

$logger = new Logger('request-network-api-client');
$logger->pushHandler(new StreamHandler('php://stdout'));

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'logger' => $logger,
    'logLevel' => 'info',
]);
```

`WebhookMiddleware` shares the same logger plumbing and emits `webhook:verified`, `webhook:dispatched`, and
`webhook:error` events so you can trace webhook flows without leaking secrets.

## Testing harness

Unit tests (and downstream consumers) can inject the built-in `Testing\FakeHttpAdapter` to queue
responses and assert requests without rolling bespoke stubs:

```php
use RequestSuite\RequestPhpClient\Testing\FakeHttpAdapter;

$fake = new FakeHttpAdapter([
    FakeHttpAdapter::jsonResponse(['status' => 'ok']),
]);

$client = RequestClient::create([
    'apiKey' => 'rk_test',
    'httpAdapter' => $fake,
]);

$client->requests()->create([...]);
$fake->assertSent(static fn ($pending) => $pending->method() === 'POST');
```

See `docs/TESTING.md` for the full harness (coverage guard, parity scripts, integration plans).

## Composer scripts

- `composer test` - runs PHPUnit using `phpunit.xml.dist`.
- `composer stan` - runs PHPStan static analysis (level 7) using `phpstan.neon.dist`.
- `composer coverage` - prints PHPUnit coverage summary to the terminal.
- `composer cs` - runs PHPCS with PSR-12 + Slevomat rules.
- `composer cs:fix` - auto-fixes code style issues where possible.
- `composer md` - runs PHPMD (codesize/design/unusedcode) with configured thresholds.
- `composer cpd` - runs PHPCPD (copy/paste detection).
- `composer deps:rules` - runs PHPStan dependency rules (architecture contracts).

These are also invoked from the workspace validator (see root `scripts/validate.sh`).
