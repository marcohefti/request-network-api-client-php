# Request Network API Client (PHP)

PHP client for the Request Network hosted REST API. It mirrors the TypeScript client’s
surface so WooCommerce and other PHP runtimes can talk to Request without a Node bridge.

## Installation

Install via Composer:

```bash
composer require marcohefti/request-network-api-client
```

## Quick start

Minimal example using the static factory:

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'clientId' => $_ENV['REQUEST_CLIENT_ID'] ?? null,
    'origin' => 'woo-plugin',
]);

$request = $client->requests()->create([
    'currency' => 'USD',
    'expectedAmount' => '1000',
    'payer' => ['email' => 'customer@example.com'],
]);

$payments = $client->payments()->search(['requestId' => $request['requestId']]);
```


## What this client covers

- PHP‑first `RequestClient` with facades for requests, payouts, payments, payer/compliance (v1+v2),
  client IDs, currencies (v1+v2), and legacy pay.
- Shared HTTP pipeline with retry, logging, and runtime validation driven by the synced OpenAPI spec.
- Webhook utilities: signature verifier, typed event objects, parser, dispatcher, PSR‑15 middleware, and
  testing helpers that mirror the TypeScript webhook module.
- Env‑based factory for backend and WooCommerce usage, with PSR‑4 autoloading under
  `RequestSuite\RequestPhpClient`.

For deeper details (HTTP client, domains, webhooks, error model), see:

- Architecture: `docs/ARCHITECTURE.md`
- Testing & validation: `docs/TESTING.md`
- Publishing checklist: `docs/PUBLISHING.md`
- HTTP client details: `docs/HTTP-CLIENT.md`
- Webhooks guide: `docs/WEBHOOKS.md`
- Examples: `examples/` (quick start + webhook parsing)

## Compatibility

- PHP: see the `php` constraint in `composer.json` (currently >= 8.1).
- Frameworks: designed for PSR‑15 stacks (e.g., WooCommerce, Laravel/Symfony via adapters).

## Development

Common commands:

- `composer test` – PHPUnit suites.
- `composer stan` – PHPStan analysis.
- `composer cs` / `composer lint` – coding standards (if configured).
- `composer update:spec` – sync OpenAPI + webhook fixtures from the contracts package.

See `docs/TESTING.md` and `docs/ARCHITECTURE.md` for the full testing strategy, spec sync flow,
and domain layout.
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
