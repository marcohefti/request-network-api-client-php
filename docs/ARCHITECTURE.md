# Request PHP Client Architecture

## Goals
- Mirror the ergonomics and reliability of the TypeScript `@request/request-network-api-client` in a PHP-first package that WooCommerce (and other PHP runtimes) can consume without a Node bridge.
- Lean on shared assets (OpenAPI spec, webhook fixtures) so TypeScript and PHP SDKs stay aligned as the Request REST API evolves.
- Follow modern PHP standards: PSR-4 autoloading, PSR-18-friendly transport adapters, and pluggable retry/logging hooks.

## Layered Design
```
RequestClient (public entrypoint)
│
├── Config\RequestClientConfig (immutable options, auth headers, telemetry)
├── Http\HttpClient (request lifecycle and retry loop)
│   ├── Http\RequestOptions (caller-supplied HTTP shape)
│   ├── Http\PendingRequest (merges config + options, builds URL/headers)
│   └── Http\HttpAdapter (transport abstraction)
│       └── Http\Adapter\CurlHttpAdapter (placeholder; PSR-18 bridge will follow)
└── Retry\RetryPolicy (retry semantics, defaults to StandardRetryPolicy)

Exceptions bubble up through `RequestApiException` so consumers always receive status, Request error
code, and correlation identifiers when available.
```

Domain facades (requests, payouts, payments, payer compliance, currencies, client IDs, pay) sit on top
of this pipeline and call `HttpClient` with schema‑keyed requests. Webhook helpers reuse the same
fixtures already committed to the TypeScript suite.

## Implementation Notes
- **Configuration** – `RequestClientConfig::fromArray()` normalises base URL,
  credentials, telemetry headers, and optional SDK metadata. Credential headers
  mirror the TypeScript helper (`x-api-key`, `x-client-id`, `x-request-origin`).
- **Transport** – `HttpClient` orchestrates retries through a `RetryPolicy`. The default policy
  (`StandardRetryPolicy`) provides exponential backoff with jitter and retries on network failures,
  HTTP 408/429, and 5xx responses. Actual HTTP exchange is delegated to `HttpAdapter`
  implementations. The bundled `CurlHttpAdapter` is the default adapter today. A PSR‑18 bridge can
  be plugged in alongside it so applications can supply any PSR‑18 client.
- **Error mapping** – transport failures are wrapped in `TransportException`
  (a `RequestApiException`), and JSON error envelopes are normalised via
  `RequestApiErrorBuilder::buildRequestApiError()`. The builder mirrors the
  TypeScript logic by extracting status/code/message, retry headers, and nested
  error details so `RequestApiException::toArray()` exposes parity metadata.
- **Runtime validation** – `Validation\SchemaRegistry` + `SchemaValidator`
  coordinate JSON Schema (Opis) validation keyed by OpenAPI `operationId`. The
  validator exposes `parseWithSchema`/`parseWithRegistry` helpers (parity with
  the TS `zod` helpers) and honours `RuntimeValidationConfig` toggles for
  requests/responses/errors. Override fragments (request status, payment routes,
  payer compliance) live under `src/Validation/Overrides/` and register during
  bootstrap so parity quirks are handled automatically.
- **Spec sync/codegen** – `composer update:spec` copies OpenAPI + webhook assets
  from the shared contracts package, while `composer codegen` emits
  `generated/OpenApi/Operations.php` and a schema manifest consumed by the
  registry. The manifest stores JSON pointers into the synced OpenAPI document
  so PHP can keep validation data in lock-step with the TypeScript client.
- **Requests domain (Phase 10)** – `RequestClient::requests()` exposes a thin
  facade built on `JsonRequestHelper`. It reuses the generated manifest for
  runtime validation, a query builder helper to strip null parameters, and the
  request-status normaliser that mirrors the TypeScript helper semantics.
- **Requests v1 (Phase 11)** – `RequestClient::requestsV1()` keeps the legacy
  flows alive using the same helper set plus a legacy status normaliser that
  only returns the paid/pending states required by older merchants. The facade
  mirrors the TS v1 wrapper (create, payment routes, calldata, status, payment
  intent, stop recurrence).
- **Payments domain (Phase 13)** – `RequestClient::payments()` exposes the v2
  search facade. It reuses the shared query builder for filtering and the
  OpenAPI manifest for runtime validation, matching TS pagination and response
  shapes.
- **Pay v1 domain (Phase 14)** – `RequestClient::pay()` mirrors the TS
  `createPayApi` helper by returning a wrapper around `PayV1Api`. The wrapper
  forwards `/v1/pay` initiate/execute calls, injects schema metadata for runtime
  validation, and exposes a `legacy` alias so WooCommerce code paths that expect
  the original facade can keep calling the same methods.
- **Payer domain (Phase 15)** – `RequestClient::payer()` exposes the v2
  compliance facade covering onboarding, status polling, and payment detail
  creation. Requests attach schema metadata for runtime validation, and the
  `ComplianceStatusFormatter` helper converts compliance responses or API error
  payloads into user-facing summaries so Woo admin notices can explain blocking
  KYC states.
- **Payer v1 domain (Phase 16)** – Added `PayerV1Api` plus the `PayerApi`
  wrapper so `$client->payer()->legacy` routes to `/v1/payer/**`. Legacy helpers
  reuse the same schema metadata + formatter utilities, ensuring Woo can fall
  back to historic compliance flows without losing validation or messaging.
- **Client IDs domain (Phase 17)** – `RequestClient::clientIds()` exposes the
  `/v2/client-ids` lifecycle (list/create/find/update/delete). Each request
  carries request/response schema keys so runtime validation remains consistent
  with the TypeScript client, enabling Woo to provision/revoke credentials
  without custom HTTP glue.
- **Currencies domain (Phase 18)** – `RequestClient::currencies()` surfaces the
  `/v2/currencies` list + conversion routes alongside a `.legacy` v1 facade.
  Requests include schema metadata for runtime validation while returning plain
  associative arrays so Woo can render token metadata without manual HTTP
  plumbing.
- **Shared utilities & aggregator (Phase 19)** – `RequestClient` now lazily instantiates
  every domain facade (requests, requests v1, payouts, payments, payer, payer v1,
  pay, pay v1, client IDs, currencies, currencies v1) on demand so they share the
  same `HttpClient` + retry/logging/validation pipeline. The `src/index.php` helper
  functions (`createRequestClient()`, `createRequestClientFromEnv()`) mirror the
  TypeScript `src/index.ts` export names while PHP continues to rely on PSR-4
  namespaces instead of wildcard barrels. `$client->http()` remains exposed for
  advanced integrations that need the low-level transport.
- **Webhook signature & header utilities (Phase 20)** – `WebhookSignatureVerifier`
  mirrors the TS helper: it accepts raw strings or PSR-7 requests, normalises
  headers via `WebhookHeaders`, supports secret rotation arrays, optional timestamp
  tolerances, and surfaces `RequestWebhookSignatureException` (code, header name,
  signature, timestamp, reason). This keeps Woo’s PHP stack aligned with the
  TypeScript SDK’s webhook security story ahead of the dispatcher phases.
- **Webhook events & parser (Phase 21)** – Added strongly typed webhook event value
  objects (`PaymentConfirmedEvent`, `PaymentFailedEvent`, `ComplianceUpdatedEvent`,
  etc.) plus `WebhookParser` that verifies (optional) signatures, loads the shared
  webhook spec into the schema registry, validates payloads, and returns a
  `ParsedWebhookEvent` with typed metadata + payload accessors. Payment detail and
  compliance events promote to status-specific subclasses (approved/pending/
  rejected/failed) so Woo code can rely on `instanceof` checks without re-reading
  raw fields, and missing schemas now fail fast to keep parity with the TS
  validator. This mirrors the TypeScript event helpers and prepares the ground
  for the dispatcher/middleware layers that follow.
- **Webhook dispatcher, middleware & testing harness (Phase 22)** – Added
  `WebhookDispatcher` for strongly typed handler registration, PSR-15
  `WebhookMiddleware` that verifies + parses PSR-7 requests (attaching the parsed
  event + dispatching to handlers), and a `WebhookTestHelper` that mimics the TS
  testing utilities (signature generation, PSR-7 mock builders, verification
  bypass toggles).
- **Logging & telemetry (Phase 23)** – Added `Logging\Redactor` and
  `Logging\PsrLoggerAdapter` so HttpClient + webhook middleware can stream events
  through PSR-3 loggers without leaking credentials. RequestClient now accepts a
  PSR-3 logger directly and the middleware emits structured webhook events.
- **Testing harness & parity guards (Phase 24)** – Introduced `Testing\FakeHttpAdapter`
  for PSR-18-friendly fakes/assertions, locked PHPUnit coverage to ≥80 %, and wired
  the OpenAPI/webhook parity scripts into the workflow so spec drift fails fast.
- **Tests** – PHPUnit scaffolding lives under `tests/`. As behaviour lands we’ll
  add unit tests for retry policy, transport adapters, and domain facades using
  the shared fixtures.

## Parity With TypeScript Client
- Same default base URL (`https://api.request.network`).
- Same credential + telemetry header logic.
- Retry defaults align with `DEFAULT_RETRY_CONFIG` (max attempts: 4, base delay:
  200ms, max delay: 2000ms, jitter 20%).
- Architecture keeps a low-level HTTP pipeline and exposes domain-specific
  facades on top-matching the TS `createRequestClient` structure.

## Planned Extensions
- PSR-18 adapter bridge + curl implementation (curl stub exists. PSR-18 bridge planned).
- OpenAPI-driven DTO generation (`generated/` directory) and domain facades for
  requests, payouts, payments, payer compliance, and currencies.
- Publishing pipeline (composer archive, packagist automation, docs) per `docs/PUBLISHING.md`.

## Parity Guards
- Operation coverage: each facade method will be associated with a canonical
  OpenAPI `operationId`. We will consolidate these IDs in a small registry under
  `src/Validation/Operations.php` so a parity script can compare implemented
  operations against `@request/request-network-api-contracts/specs/openapi/request-network-openapi.json`.
- Webhook coverage: event classes under `src/Webhooks/Events` follow a
  deterministic naming scheme based on fixture filenames (e.g.,
  `payment-confirmed.json` -> `PaymentConfirmedEvent`). A parity script will
  compare classes to the fixtures in the contracts package.
