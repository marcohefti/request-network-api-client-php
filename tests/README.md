# Tests

This package ships PHPUnit suites that mirror the TypeScript client’s guard rails: unit coverage,
static analysis, and OpenAPI/webhook parity.

- See `docs/TESTING.md` for the full testing guide (commands, coverage expectations, and parity scripts).
- Run `composer test` from this directory for the unit suites.
- Use `pnpm validate --full` from the repo root to exercise the workspace validator before publishing.
