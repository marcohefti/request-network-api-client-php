# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 0.5.x   | :white_check_mark: |
| < 0.5.0 | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of the following methods:

1. **GitHub Security Advisories** (preferred): Use the [Security Advisory](https://github.com/marcohefti/request-network-api-client-php/security/advisories/new) feature
2. **Email**: Send details to [your-email] (if you prefer this route)

### What to Include

Please include the following information in your report:

- Type of vulnerability
- Full paths of affected source files
- Location of the affected code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the vulnerability, including how an attacker might exploit it

### Response Timeline

- We will acknowledge receipt of your vulnerability report within **48 hours**
- We will send you a more detailed response within **5 business days** indicating next steps
- We will keep you informed about the progress of fixing the vulnerability
- We will notify you when the vulnerability is fixed

## Security Features

This library implements several security best practices:

### Webhook Signature Verification

- **Timing-safe comparison**: Uses `hash_equals()` to prevent timing attacks when verifying webhook signatures
- **Secret rotation support**: Accepts multiple secrets to enable zero-downtime secret rotation
- **Timestamp validation**: Prevents replay attacks with configurable tolerance windows
- **HMAC-SHA256**: Industry-standard signature algorithm

### Credential Protection

- **Automatic redaction**: All sensitive data (API keys, signatures, secrets) are automatically redacted from logs
- **No credential logging**: The `Logging\Redactor` class strips sensitive headers and fields
- **Environment-based configuration**: Examples use `$_ENV` to prevent hardcoded credentials

### HTTP Security

- **PSR-18/PSR-7 compatibility**: Allows use of security-audited HTTP clients (Guzzle, Symfony HttpClient, etc.)
- **TLS enforcement**: Default base URL uses HTTPS
- **Request validation**: Optional runtime schema validation for all API requests and responses

### Input Validation

- **JSON Schema validation**: Runtime validation against OpenAPI specification
- **Type safety**: Strict type declarations (`declare(strict_types=1)`) throughout the codebase
- **Exception handling**: Proper error handling prevents information leakage

## Known Security Considerations

### API Key Storage

This library does not handle API key storage. Users must:

- Never commit API keys to version control
- Use environment variables or secure key management systems
- Rotate keys regularly
- Use different keys for development and production

### Webhook Secrets

When using webhook verification:

- Store webhook secrets securely (environment variables, secret managers)
- Use strong, randomly-generated secrets
- Rotate secrets periodically using the secret rotation feature
- Configure appropriate timestamp tolerance (default: 5 minutes)

## Security Updates

Security updates will be released as patch versions (e.g., 0.5.2) and documented in the [CHANGELOG.md](CHANGELOG.md). Subscribe to repository releases to receive notifications.

## Responsible Disclosure

We practice responsible disclosure and will work with security researchers to:

- Confirm the vulnerability
- Determine its impact and severity
- Develop and test a fix
- Release a security advisory and patch
- Credit researchers (if desired) in release notes

Thank you for helping keep Request Network API Client and our users safe!
