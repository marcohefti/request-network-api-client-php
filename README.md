# Request Network API Client (PHP)

[![Latest Version](https://img.shields.io/packagist/v/marcohefti/request-network-api-client.svg?style=flat-square)](https://packagist.org/packages/marcohefti/request-network-api-client)
[![PHP Version](https://img.shields.io/packagist/php-v/marcohefti/request-network-api-client.svg?style=flat-square)](https://packagist.org/packages/marcohefti/request-network-api-client)
[![License](https://img.shields.io/packagist/l/marcohefti/request-network-api-client.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/marcohefti/request-network-api-client.svg?style=flat-square)](https://packagist.org/packages/marcohefti/request-network-api-client)

PHP client for the Request Network hosted REST API. It mirrors the TypeScript client's
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
]);

// Create a new payment request
$request = $client->requests()->create([
    'amount' => '100',
    'invoiceCurrency' => 'USD',
    'paymentCurrency' => 'USDC-sepolia',
    'payee' => '0x0000000000000000000000000000000000000000',
    'reference' => 'order-123',
]);

$requestId = $request['requestId'] ?? null;

// Search for payments
$payments = $client->payments()->search(['requestId' => $requestId]);
```


## Why Use This Client?

Instead of manually building HTTP requests and handling errors, this client provides:

- **Type Safety**: Strict type hints and PHPDoc throughout (PHPStan level 7 clean)
- **Automatic Retries**: Exponential backoff with jitter for transient failures
- **Schema Validation**: Runtime validation against OpenAPI specs (optional)
- **Webhook Security**: Timing-safe signature verification with secret rotation support
- **Error Handling**: Rich exception hierarchy with correlation IDs and retry headers
- **Logging**: Automatic credential redaction in PSR-3 compatible logs
- **Testing**: Built-in FakeHttpAdapter for easy unit testing

**Before vs After:** See `docs/BEFORE-AFTER.md` for side-by-side code comparisons showing how this client simplifies common Request Network API operations.

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
- Troubleshooting: `docs/TROUBLESHOOTING.md`
- Publishing checklist: `docs/PUBLISHING.md`
- HTTP client details: `docs/HTTP-CLIENT.md`
- Webhooks guide: `docs/WEBHOOKS.md`
- Examples: `examples/` (quick start + webhook parsing)

## Compatibility

- **PHP:** >= 8.2
- **Frameworks:** Designed for PSR‑15 stacks (e.g., WooCommerce, Laravel/Symfony via adapters)
- **HTTP Clients:** Any PSR-18 compatible client (Guzzle, Symfony HttpClient, etc.) or built-in cURL adapter

## Development

Common commands:

- `composer test` – PHPUnit suites.
- `composer stan` – PHPStan analysis.
- `composer cs` / `composer lint` – coding standards (if configured).
- `composer update:spec` – sync OpenAPI + webhook fixtures from the contracts package.

See `docs/TESTING.md` and `docs/ARCHITECTURE.md` for the full testing strategy, spec sync flow,
and domain layout.

## Webhooks

The webhook module provides secure signature verification, typed event objects, and PSR-15 middleware:

```php
use RequestSuite\RequestPhpClient\Webhooks\Events\PaymentConfirmedEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;

$parser = new WebhookParser();
$parsed = $parser->parse([
    'rawBody' => (string) $request->getBody(),
    'headers' => $request,
    'secret' => [$_ENV['REQUEST_WEBHOOK_SECRET']],
]);

if ($parsed->event() instanceof PaymentConfirmedEvent) {
    $data = $parsed->event()->data();
    $requestId = $data->string('requestId');
    // Handle payment confirmation
}
```

**Features:**
- Timing-safe signature verification with secret rotation support
- Typed event classes for all webhook types
- PSR-15 middleware for framework integration
- Event dispatcher for handler registration
- Testing helpers for unit tests

See `docs/WEBHOOKS.md` and `examples/webhook-handler-middleware.php` for complete webhook integration examples.

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
