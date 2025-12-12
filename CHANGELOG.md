# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.2] - 2024-12-12

### Added
- GitHub Actions CI workflow testing on PHP 8.2, 8.3, 8.4
- SECURITY.md with vulnerability reporting guidelines and security features documentation
- CONTRIBUTING.md with comprehensive contribution guidelines and development workflow
- CHANGELOG.md for tracking version history
- GitHub issue templates (bug report, feature request) and PR template
- Comprehensive TROUBLESHOOTING.md covering common issues and solutions
- README badges (version, tests, PHP version, license, downloads)
- "Why Use This Client?" section in README with value proposition
- Architecture diagrams (Mermaid) in ARCHITECTURE.md showing request flow and webhook processing
- Three new complete examples:
  - create-invoice-end-to-end.php (full invoice workflow)
  - webhook-handler-middleware.php (PSR-15 integration)
  - error-handling.php (comprehensive error handling patterns)

### Changed
- Expanded .gitignore with comprehensive PHP/IDE/OS coverage
- Added keywords to composer.json for better Packagist discoverability
- Added support section to composer.json with issue/source/docs URLs
- Corrected PHP version requirement in README (8.2+)
- Fixed quick start example with correct API field names
- Condensed webhook section in README for better readability
- Enhanced compatibility section with framework and HTTP client details

### Fixed
- Documentation inconsistencies between README and examples
- Missing standard repository files for professional open-source project

## [0.5.1] - 2024-12-11

### Added
- MIT License file
- Comprehensive documentation in `docs/` directory
- Repository hygiene improvements (SECURITY.md, GitHub templates, .gitignore)
- README badges for Packagist, PHP version, and license
- Keywords in composer.json for better discoverability

### Changed
- Removed `createFromEnv` and `EnvironmentClientFactory` in favor of direct configuration
- Updated README.md with accurate PHP version requirement (8.2+)
- Fixed quick start example to use correct API field names

### Fixed
- Documentation inconsistencies between README and examples
- .gitignore missing common PHP/IDE/OS patterns

## [0.5.0] - 2024-12-11

### Added
- Complete PHP client implementation mirroring TypeScript SDK
- Domain facades: Requests, Payments, Payouts, Payer, Currencies, ClientIds, Pay
- V1 and V2 API support for requests, payer, currencies
- Webhook module with signature verification, typed events, parser, dispatcher, and PSR-15 middleware
- Runtime validation using OpenAPI specifications
- Comprehensive retry logic with exponential backoff and jitter
- PSR-18 and cURL HTTP adapter support
- Automatic credential redaction in logging
- Testing harness with FakeHttpAdapter
- Parity scripts for OpenAPI and webhook validation
- PHPStan level 7 static analysis with strict rules
- 127 PHPUnit tests with strong coverage
- Comprehensive documentation (ARCHITECTURE.md, TESTING.md, WEBHOOKS.md, HTTP-CLIENT.md, BEFORE-AFTER.md)

### Security
- Timing-safe webhook signature verification using `hash_equals()`
- Automatic redaction of sensitive data in logs
- Support for webhook secret rotation
- Timestamp validation to prevent replay attacks

## [Unreleased]

### Planned
- CI/CD integration with GitHub Actions
- Additional integration examples (Laravel, Symfony)
- Enhanced error recovery patterns
- Performance optimizations for high-throughput scenarios

---

## Version History

- **0.5.x** - Initial public release with full feature parity to TypeScript client
- **0.x** - Pre-release versions, may contain breaking changes between minor versions
- **1.0.0** - Planned stable release following strict SemVer

## Upgrading

See [UPGRADE.md](UPGRADE.md) for detailed upgrade instructions between versions.

## Support

- [Documentation](https://github.com/marcohefti/request-network-api-client-php#readme)
- [Issues](https://github.com/marcohefti/request-network-api-client-php/issues)
- [Security](SECURITY.md)
