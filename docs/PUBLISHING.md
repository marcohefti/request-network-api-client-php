# Publishing `marcohefti/request-network-api-client`

This package will ship on Packagist once the MVP surface (requests, payouts,
payments, payer compliance, currencies, webhooks) and validation story are in
place. Use this checklist when preparing a release.

## Preflight
- [ ] Ensure `packages/request-php-client/README.md` documents installation (composer require),
      configuration (env vars/options), and usage examples for HTTP facades + webhook helpers.
- [ ] Confirm `docs/ARCHITECTURE.md` reflects the latest transport/domain
      design and regeneration commands.
- [ ] Regenerate DTOs/webhook schemas from `@request/request-network-api-contracts/specs/openapi/request-network-openapi.json`
      (and `@request/request-network-api-contracts/fixtures/webhooks/**`) using the local codegen scripts, then commit
      the outputs.
- [ ] Ensure `@request/request-network-api-contracts` is available (workspace path `/request-network-api-contracts` or an npm dependency pinned to the release tag) so `composer update:spec` and parity guards can sync assets without manual intervention.
- [ ] Run `composer test` and `composer stan` inside the package, then
      `pnpm validate --full` from the repo root (capture logs per workspace
      protocol).

## Release Metadata
- Update `composer.json`:
  - [ ] `description`, `keywords`, and `homepage` align with the release scope.
  - [ ] `type` remains `library` and `license` is correct.
  - [ ] Autoload paths for `src/` and `generated/` are accurate.
  - [ ] Include PSR dependencies (http-client, http-factory, http-message,
        log) once adapters/logging are implemented.
- [ ] Add `.gitattributes` entries to exclude tests/docs from distro archives
      if required (`export-ignore`).
- [ ] Update `docs/TESTING.md` / `docs/ARCHITECTURE.md` with the final publishing workflow so the public repo stays in sync.
- [ ] Tag strategy documented (e.g., `v0.x.y` for pre-release, semantic version
      once GA).

## Repository Split (post-monorepo)
- [ ] Use `git subtree split` or `git filter-repo` to extract
      `packages/request-php-client` into a standalone repository.
- [ ] Configure CI in the public repo to run composer install, PHPUnit,
      PHPStan, and any codegen/validation scripts.
- [ ] Add `@request/request-network-api-contracts` as a Node dependency (git tag or registry package) so `composer update:spec` continues working outside the monorepo.
- [ ] Add Packagist hook (or GitHub Actions workflow) to publish on tagged
      releases.
- [ ] Mirror docs (README, architecture) in the public repo.

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
