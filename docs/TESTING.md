# Testing the Request PHP Client

The PHP package mirrors the TypeScript SDK’s guard rails so every release ships with the same signal quality (unit coverage, static analysis, spec parity, and synced fixtures). This guide explains how to run the suites locally, how CI enforces them, and where future contributors should extend coverage as new behaviour lands.

## Goals and Non-Goals
- Document the end-to-end testing workflow for this PHP client repository, including tooling, directory layout, and validation hooks.
- Record expectations for coverage, contracts sync, and parity scripts so regressions are obvious before release.
- Surface Mollie-inspired PHP testing ergonomics (PSR-18 mocks, reusable fixtures) that keep suites fast and deterministic.
- Call out backlog updates required now that the testing contract exists.
- **Non-goal:** Document feature behaviour-`docs/ARCHITECTURE.md` remains the source of truth for runtime design decisions.

## Test Types and Tooling

| Suite / Check | Tooling | Status | Purpose |
| --- | --- | --- | --- |
| Unit | PHPUnit 11 (`composer test`) | Available | Exercises core config, retry policies, HTTP adapters, and (eventually) domain facades using faked transports. |
| Integration | PHPUnit 11 (`composer test -- --group integration`) | Planned | Hits the live Request API with PSR-18 clients (Guzzle recommended) once credential scaffolding lands. |
| Static analysis | PHPStan + Strict Rules (`composer stan`) | Available | Enforces type safety across `src/**`. Mirrors TS `pnpm typecheck`. |
| Style / lint | PHPCS + Slevomat (`composer cs`, `composer cs:fix`) | Available | Applies PSR-12 + strict import/type rules equivalent to the TS ESLint config. |
| Code health | PHPMD (`composer md`) | Available | Flags complexity and dead code where PHPCS lacks coverage. |
| Coverage | PHPUnit TextUI (`composer coverage`) | Available | Produces a line coverage summary. Thresholds will gate releases (≥80 % target). |
| OpenAPI parity | `scripts/parity-openapi.php` (`composer parity:openapi`) | Available | Diffs implemented `operationId`s against the synced OpenAPI spec. |
| Webhook parity | `scripts/parity-webhooks.php` (`composer parity:webhooks`) | Available | Diffs webhook event classes against the shared fixtures. |
| Contracts sync | `scripts/sync-contracts.mjs` (`composer update:spec`) | Available | Copies OpenAPI + webhook assets from `@marcohefti/request-network-api-contracts`. |

## Project Layout

| Path | Contents | Notes |
| --- | --- | --- |
| `src/**` | Production code organised by domain (`Config/`, `Http/`, `Retry/`, etc.). | PSR-4 root: `RequestSuite\RequestPhpClient`. |
| `tests/Unit/**` | PHPUnit unit suites. | Mirror TS folder names (e.g. `Http`, `Retry`) as new suites land. |
| `tests/Integration/**` | Placeholder for live suites. | Gate with PHPUnit groups to avoid accidental CI runs without credentials. |
| `tests/fixtures/**` | Planned PHP fixtures that wrap the shared JSON assets. | Prefer re-exporting `@marcohefti/request-network-api-contracts` fixtures. |
| `specs/**` | Synced OpenAPI & webhook specs + fixtures. | Populated by `composer update:spec`. Committed to VCS. |
| `scripts/**` | Node + PHP utilities (contracts sync, parity guards). | Keep scripts idempotent so CI re-runs stay green. |

## Setup & Prerequisites

1. PHP ≥8.2 with `ext-json`, `ext-hash`, and `ext-mbstring` enabled (matching `composer.json`).
2. Composer ≥2.7 with the `dealerdirect/phpcodesniffer-composer-installer` plugin allowed:
   ```sh
   composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
   ```
3. Coverage driver (Xdebug 3 or pcov) if you plan to run `composer coverage`.
4. Node ≥20 (only required when you run the contracts sync script or other Node-based tooling). Install the dev dependency for contracts with:
   ```sh
   npm install
   ```
5. Install PHP dependencies:
   ```sh
   composer install
   ```

## Running Tests and Checks

| Command | Location | Description |
| --- | --- | --- |
| `composer test` | repo root | Runs PHPUnit suites defined in `phpunit.xml.dist`. Use `-- --filter RetryPolicyTest` or `-- --group integration` for targeted runs. |
| `composer coverage` | repo root | Re-runs PHPUnit with `--coverage-text`. Requires Xdebug/pcov. Treat ≥80 % line coverage as the working floor. |
| `composer stan` | repo root | Executes PHPStan level 7 with strict rules. Mirrors TS `pnpm typecheck`. |
| `composer cs` | repo root | Runs PHPCS against `src/**`. |
| `composer cs:fix` | repo root | Applies PHPCS fixes (best-effort. Rerun `composer cs` afterwards). |
| `composer md` | repo root | Runs PHPMD (codesize, cleancode, unusedcode, naming) and reports any violations. |
| `composer update:spec` | repo root | Syncs OpenAPI + webhook specs/fixtures into `specs/**`. |
| `composer parity:openapi` | repo root | Fails if `src/Validation/Operations.php` drifts from the synced OpenAPI spec. |
| `composer parity:webhooks` | repo root | Fails if webhook event classes drift from synced fixtures. |

## Contracts & Parity Guardrails

- `composer update:spec` copies OpenAPI schemas (`specs/openapi/**`) and webhook assets (`specs/webhooks/**`, `specs/fixtures/webhooks/**`) from `@marcohefti/request-network-api-contracts`. The script writes `specs/meta.json` with SHA-256 fingerprints so we can detect drift in CI.
- `composer parity:openapi` reads every `operationId` in the synced OpenAPI spec and compares it to constants defined in `src/Validation/Operations.php`. Missing IDs fail the build. Extra IDs mean PHP is ahead of the spec.
- `composer parity:webhooks` converts fixture filenames (kebab-case) into PascalCase event names and compares them to classes under `src/Webhooks/Events`. Missing/extra events fail the run. `UnknownEvent` is ignored to preserve a sane fallback.
- Keep parity guards green before merging. If you intentionally add an allowlist, document it in this file and the backlog task so future work can remove it.

## Coverage Expectations

- Target ≥80 % line coverage across `src/**`. Treat regressions below this floor as blockers.
- `composer coverage` prints the per-file summary. Pair it with PHPUnit’s HTML report (`--coverage-html build/coverage`) during investigations.
- `phpunit.coverage.xml.dist` enforces the floor via `<coverage><limit><line min="80"/></limit></coverage>`. Document any temporary relaxation (e.g., during major refactors) in the task Execution Log.

## CI & Local Validation

In CI (and before publishing new releases), run at least:

- `composer cs` and `composer stan` for style and static analysis.
- `composer test` (optionally `composer coverage` when a coverage driver is available).
- `composer update:spec` to refresh contracts when needed.
- `composer parity:openapi` and `composer parity:webhooks` to enforce spec parity.

## HTTP Testing Strategy

- Follow the Mollie PHP SDK pattern: model transport behaviour behind a PSR-18 adapter so tests can swap in fakes without bespoke stubs.
- Use the built-in `RequestSuite\RequestPhpClient\Testing\FakeHttpAdapter` to queue responses, inspect captured `PendingRequest`s, and call `assertSent`/`assertNothingSent` helpers instead of rolling custom mocks. Store reusable fixtures under `tests/fixtures/**` or reuse JSON from `specs/fixtures/webhooks`.
- Example harness:

  ```php
  use RequestSuite\RequestPhpClient\RequestClient;
  use RequestSuite\RequestPhpClient\Testing\FakeHttpAdapter;

  $fake = new FakeHttpAdapter([
      FakeHttpAdapter::jsonResponse(['requestId' => 'req_fake']),
  ]);

  $client = RequestClient::create([
      'apiKey' => 'rk_test',
      'httpAdapter' => $fake,
  ]);

  $client->requests()->create([...]);
  $fake->assertSent(static fn($pending) => $pending->method() === 'POST');
  ```

- When integration suites arrive, default to Guzzle as the PSR-18 implementation. Inject via `RequestClient::create(['httpAdapter' => new Psr18Adapter(...)])` and guard suites with PHPUnit groups/env checks.
- Prefer assertion helpers that mirror Mollie’s `assertSent` / `assertSentCount` semantics so contributors can verify headers, query params, and idempotency metadata without duplicating boilerplate.

## Local Development Tips

- PHPUnit filters: `composer test -- --filter RetryPolicyTest` runs a single class; `-- --testsuite Unit` forces the fast suite.
- Toggle integration suites using `@group integration` annotations and `composer test -- --group integration`.
- PHPStan auto-fixes aren’t available-address errors directly or annotate with `@phpstan-assert` when narrowing types.
- After syncing contracts, run `git status` to ensure new specs are committed (CI enforces a clean tree).
- For large fixture updates, commit `specs/meta.json` alongside the copied files so parity scripts can skip early.

## Troubleshooting

- **Composer plugin errors:** re-run `composer install` with `COMPOSER_ALLOW_SUPERUSER=1` and ensure the allow-list above is present.
- **Coverage driver missing:** install `pecl install xdebug` or enable `pcov`. PHPUnit 11 exits with `Code coverage driver not available`.
- **PHPStan memory issues:** run `composer stan -- --memory-limit=1G`.
- **Parity skips unexpectedly:** confirm `specs/` exists. `composer update:spec` must run before parity scripts can diff anything.
- **Node not found:** ensure you ran `pnpm install` from the repo root so `node` is available to the contracts sync script.

## Backlog Alignment

Track future testing-focused improvements (coverage thresholds, additional parity checks, dependency rules) in your own backlog or issue tracker so they are easy to prioritise alongside feature work.
