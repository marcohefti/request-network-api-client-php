# Publishing `marcohefti/request-network-api-client`

This package will ship on Packagist once the MVP surface (requests, payouts,
payments, payer compliance, currencies, webhooks) and validation story are in
place. Use this checklist when preparing a release.

## Preflight
- [x] Ensure `README.md` documents installation (composer require),
      configuration (env vars/options), and usage examples for HTTP facades + webhook helpers.
- [x] Confirm `docs/ARCHITECTURE.md` reflects the latest transport/domain
      design and regeneration commands.
- [x] Regenerate DTOs/webhook schemas from `@marcohefti/request-network-api-contracts/specs/openapi/request-network-openapi.json`
      (and `@marcohefti/request-network-api-contracts/fixtures/webhooks/**`) using the local codegen scripts, then commit
      the outputs.
- [x] Ensure `@marcohefti/request-network-api-contracts` is available (via npm as a sibling directory or as an npm dependency pinned to the release tag) so `composer update:spec` and parity guards can sync assets without manual intervention.
- [x] Run `composer test` and `composer stan` inside the package to ensure all tests pass and static analysis is clean.

## Release Metadata
- Update `composer.json`:
  - [x] `description`, `keywords`, and `homepage` align with the release scope.
  - [x] `type` remains `library` and `license` is correct.
  - [x] Autoload paths for `src/` and `generated/` are accurate.
  - [x] Include PSR dependencies (http-client, http-factory, http-message,
        log) once adapters/logging are implemented.
- [x] Add `.gitattributes` entries to exclude tests/docs from distro archives
      if required (`export-ignore`).
- [x] Update `docs/TESTING.md` / `docs/ARCHITECTURE.md` with the final publishing workflow so the public repo stays in sync.
- [x] Tag strategy documented (e.g., `v0.x.y` for pre-release, semantic version
      once GA).

Tagging strategy for this package:

- Use `v0.x.y` tags for the 0.x line while the surface is still evolving.
- Treat `0.x` as feature-bearing: minor (`0.minor`) can contain breaking changes, patch (`0.x.patch`) is reserved for backward-compatible fixes.
- Reserve `1.0.0` for the first stable GA release; from that point onward, follow strict SemVer (breaking changes require a major bump).

## Repository Setup
- [x] Repository is standalone at `git@github.com:marcohefti/request-network-api-client-php.git`.
- [x] CI configured to run composer install, PHPUnit, PHPStan, and codegen/validation scripts.
- [x] `@marcohefti/request-network-api-contracts` available as an npm package so `composer update:spec` works.
- [x] Packagist hook configured to publish on tagged releases.
- [x] Docs (README, architecture) are in the repository.

## Publishing Steps
1. Bump the version in `composer.json` (following semver).
2. Commit the release prep (docs, changelog, version bump).
3. Tag the commit (`git tag v0.x.y`) and push tag.
4. Packagist will detect the tag if the repo is hooked. Otherwise trigger a
   manual update (or run the GitHub Action that calls the Packagist API).
5. Announce changes and update internal docs/consumers.

## Post-Release
- [ ] Update WooCommerce plugin dependency constraints to require the new
      version once validated.
- [ ] Archive the release notes and validation logs in the internal knowledge
      base.
