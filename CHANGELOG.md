# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
