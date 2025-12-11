# Testing the Request PHP Client

The PHP package mirrors the TypeScript SDK’s guard rails so every release ships with the same signal quality (unit coverage, static analysis, spec parity, and synced fixtures). This guide explains how to run the suites locally, how CI enforces them, and where future contributors should extend coverage as new behaviour lands.

## Goals and Non-Goals
- Document the end-to-end testing workflow for `packages/request-php-client`, including tooling, directory layout, and validation hooks.
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
| Contracts sync | `scripts/sync-contracts.mjs` (`composer update:spec`) | Available | Copies OpenAPI + webhook assets from `@request/request-network-api-contracts`. |

## Project Layout

| Path | Contents | Notes |
| --- | --- | --- |
| `src/**` | Production code organised by domain (`Config/`, `Http/`, `Retry/`, etc.). | PSR-4 root: `RequestSuite\RequestPhpClient`. |
| `tests/Unit/**` | PHPUnit unit suites. | Mirror TS folder names (e.g. `Http`, `Retry`) as new suites land. |
| `tests/Integration/**` | Placeholder for live suites. | Gate with PHPUnit groups to avoid accidental CI runs without credentials. |
| `tests/fixtures/**` | Planned PHP fixtures that wrap the shared JSON assets. | Prefer re-exporting `@request/request-network-api-contracts` fixtures. |
| `specs/**` | Synced OpenAPI & webhook specs + fixtures. | Populated by `composer update:spec`. Committed to VCS. |
| `scripts/**` | Node + PHP utilities (contracts sync, parity guards). | Keep scripts idempotent so CI re-runs stay green. |

## Setup & Prerequisites

1. PHP ≥8.2 with `ext-json`, `ext-hash`, and `ext-mbstring` enabled (matching `composer.json`).
2. Composer ≥2.7 with the `dealerdirect/phpcodesniffer-composer-installer` plugin allowed:
   ```sh
   composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
   ```
3. Coverage driver (Xdebug 3 or pcov) if you plan to run `composer coverage`.
4. Node ≥20 (repo default pins Node 24.x) with `pnpm@10.17.1` via Corepack:
   ```sh
   corepack enable pnpm@10.17.1
   ```
5. Install dependencies:
   ```sh
   pnpm install          # workspace (installs @request/request-network-api-contracts)
   composer install      # from packages/request-php-client
   ```

Set `VALIDATE_SINCE=origin/main` when running scoped validation loops so the orchestrator only checks packages you touched.

## Running Tests and Checks

| Command | Location | Description |
| --- | --- | --- |
| `composer test` | `packages/request-php-client` | Runs PHPUnit suites defined in `phpunit.xml.dist`. Use `-- --filter RetryPolicyTest` or `-- --group integration` for targeted runs. |
| `composer coverage` | `packages/request-php-client` | Re-runs PHPUnit with `--coverage-text`. Requires Xdebug/pcov. Treat ≥80 % line coverage as the working floor. |
| `composer stan` | `packages/request-php-client` | Executes PHPStan level 7 with strict rules. Mirrors TS `pnpm typecheck`. |
| `composer cs` | `packages/request-php-client` | Runs PHPCS against `src/**`. |
| `composer cs:fix` | `packages/request-php-client` | Applies PHPCS fixes (best-effort. Rerun `composer cs` afterwards). |
| `composer md` | `packages/request-php-client` | Runs PHPMD (codesize, cleancode, unusedcode, naming) and reports any violations. |
| `composer update:spec` | `packages/request-php-client` | Syncs OpenAPI + webhook specs/fixtures into `specs/**`. |
| `composer parity:openapi` | `packages/request-php-client` | Fails if `src/Validation/Operations.php` drifts from the synced OpenAPI spec. |
| `composer parity:webhooks` | `packages/request-php-client` | Fails if webhook event classes drift from synced fixtures. |
| `pnpm validate --full 2>&1 \| tee /tmp/request-network-api-client-validate.log` | repo root | Runs the workspace validator (`scripts/validate.sh`). Tail the log afterwards to confirm `✅ VALIDATION PASSED`. |
| `pnpm validate:scoped -- --filter "./packages/request-php-client"` | repo root | Runs only the phases touching this package. Useful for quick loops between commits. |

## Contracts & Parity Guardrails

- `composer update:spec` copies OpenAPI schemas (`specs/openapi/**`) and webhook assets (`specs/webhooks/**`, `specs/fixtures/webhooks/**`) from `@request/request-network-api-contracts`. The script writes `specs/meta.json` with SHA-256 fingerprints so we can detect drift in CI.
- `composer parity:openapi` reads every `operationId` in the synced OpenAPI spec and compares it to constants defined in `src/Validation/Operations.php`. Missing IDs fail the build. Extra IDs mean PHP is ahead of the spec.
- `composer parity:webhooks` converts fixture filenames (kebab-case) into PascalCase event names and compares them to classes under `src/Webhooks/Events`. Missing/extra events fail the run. `UnknownEvent` is ignored to preserve a sane fallback.
- Keep parity guards green before merging. If you intentionally add an allowlist, document it in this file and the backlog task so future work can remove it.

## Coverage Expectations

- Target ≥80 % line coverage across `src/**`. Treat regressions below this floor as blockers.
- `composer coverage` prints the per-file summary. Pair it with PHPUnit’s HTML report (`--coverage-html build/coverage`) during investigations.
- `phpunit.coverage.xml.dist` enforces the floor via `<coverage><limit><line min="80"/></limit></coverage>`. Document any temporary relaxation (e.g., during major refactors) in the task Execution Log.

## CI & Local Validation

`scripts/validate.sh` orchestrates the same phases CI runs:

| Phase | What runs for PHP | Trigger |
| --- | --- | --- |
| `LINT` | `composer cs:fix` (best-effort), `composer cs`, `composer stan` | Always, when `composer.json` exists. |
| `TESTS` | `composer test`, optionally `composer coverage` (non-fatal) | Always. |
| `DUPLICATES` | `composer md` (PHPMD) | Always. Add `composer cpd` once the script exists. |
| `CONTRACTS` | `composer update:spec` (non-fatal), `composer deps:rules` (planned), `composer parity:openapi`, `composer parity:webhooks` | Always. |

Run the full validator before handoff:

```sh
pnpm validate --full 2>&1 | tee /tmp/request-network-api-client-validate.log
tail -n 60 /tmp/request-network-api-client-validate.log
```

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

Keep `tasks/TODO/20251106-request-php-client-delivery.md` in sync with this guide-specifically, reference the Fake HTTP adapter, parity guard scripts, validated coverage guard, and note that `composer cpd` / `composer deps:rules` remain future work.
