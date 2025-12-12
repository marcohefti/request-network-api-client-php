# Contributing to Request Network API Client (PHP)

Thank you for your interest in contributing! This document provides guidelines and instructions for contributing to this project.

## Code of Conduct

This project follows a code of professional conduct. Please be respectful and constructive in all interactions.

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer >= 2.7
- Git
- Node.js >= 20 (for contract synchronization scripts)

### Development Setup

1. **Fork and clone the repository:**
   ```bash
   git clone https://github.com/YOUR-USERNAME/request-network-api-client-php.git
   cd request-network-api-client-php
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Verify your setup:**
   ```bash
   composer test
   composer stan
   composer cs
   ```

### Project Structure

```
.
├── src/                    # Source code
│   ├── Core/              # HTTP client, config, exceptions, retry logic
│   ├── Domains/           # API domain facades (requests, payments, etc.)
│   ├── Webhooks/          # Webhook verification and parsing
│   ├── Validation/        # Schema validation
│   └── Logging/           # Logging and redaction
├── tests/                 # PHPUnit tests
│   └── Unit/             # Unit tests
├── examples/              # Usage examples
├── docs/                  # Documentation
└── specs/                 # OpenAPI & webhook specifications (synced)
```

## Development Workflow

### Making Changes

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/my-new-feature
   # or
   git checkout -b fix/issue-description
   ```

2. **Write your code:**
   - Follow PSR-12 coding style
   - Add `declare(strict_types=1);` to all PHP files
   - Include PHPDoc for all public methods
   - Write tests for new functionality

3. **Run quality checks:**
   ```bash
   # Run tests
   composer test

   # Static analysis
   composer stan

   # Code style
   composer cs

   # Fix code style issues
   composer cs:fix

   # Check code complexity/quality
   composer md
   ```

4. **Commit your changes:**
   ```bash
   git add .
   git commit -m "feat: add new feature description"
   ```

   Follow conventional commit format:
   - `feat:` - New features
   - `fix:` - Bug fixes
   - `docs:` - Documentation changes
   - `test:` - Test additions/changes
   - `refactor:` - Code refactoring
   - `chore:` - Maintenance tasks

5. **Push and create a pull request:**
   ```bash
   git push origin feature/my-new-feature
   ```

## Coding Standards

### PHP Style

- **PSR-12**: Follow PSR-12 coding style
- **Strict types**: Always use `declare(strict_types=1);`
- **Type hints**: Use type hints for all parameters and return types
- **PHPDoc**: Document all public APIs with PHPDoc blocks
- **Readonly**: Use `readonly` for immutable properties where appropriate

### Example:

```php
<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Example;

/**
 * Example class demonstrating coding standards.
 */
final class ExampleClass
{
    /**
     * Process a payment request.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function processPayment(array $data): array
    {
        // Implementation
        return [];
    }
}
```

### Testing

- Write tests for all new features
- Maintain or improve code coverage (target: >= 80%)
- Use the `FakeHttpAdapter` for testing API interactions
- Name test methods descriptively: `test_it_creates_request_with_valid_data()`

### Example Test:

```php
<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\RequestClient;
use RequestSuite\RequestPhpClient\Testing\FakeHttpAdapter;

final class ExampleTest extends TestCase
{
    public function test_it_creates_client_successfully(): void
    {
        $fake = new FakeHttpAdapter([
            FakeHttpAdapter::jsonResponse(['requestId' => 'req_123']),
        ]);

        $client = RequestClient::create([
            'apiKey' => 'rk_test',
            'httpAdapter' => $fake,
        ]);

        $result = $client->requests()->create(['amount' => '100']);

        $this->assertEquals('req_123', $result['requestId']);
    }
}
```

## Documentation

### When to Update Documentation

- **README.md**: Update for new features or changed APIs
- **docs/**: Add detailed guides for significant features
- **CHANGELOG.md**: Document all notable changes
- **Examples**: Add examples for new functionality

### Documentation Style

- Be concise and clear
- Include code examples
- Link to related documentation
- Keep it up-to-date with code changes

## Submitting Pull Requests

### PR Checklist

Before submitting your PR, ensure:

- [ ] Tests pass (`composer test`)
- [ ] PHPStan analysis passes (`composer stan`)
- [ ] Code style is correct (`composer cs`)
- [ ] New functionality has tests
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated (for notable changes)
- [ ] Commit messages follow conventional commit format
- [ ] PR description explains the changes

### PR Guidelines

1. **Keep PRs focused**: One feature/fix per PR
2. **Write clear descriptions**: Explain what and why
3. **Link related issues**: Reference issues in PR description
4. **Respond to feedback**: Address review comments promptly
5. **Keep it current**: Rebase on main if needed

## Reporting Issues

### Before Creating an Issue

1. Search existing issues to avoid duplicates
2. Ensure you're using the latest version
3. Collect relevant information (PHP version, package version, error messages)

### Issue Template

Use the provided issue templates for:
- **Bug reports**: Include reproduction steps
- **Feature requests**: Explain use case and proposed solution

### Security Issues

**Do not** create public issues for security vulnerabilities. See [SECURITY.md](SECURITY.md) for reporting instructions.

## Syncing OpenAPI Specifications

The client uses shared OpenAPI and webhook specifications:

```bash
# Sync specifications from contracts package
composer update:spec

# Verify parity
composer parity:openapi
composer parity:webhooks
```

## Release Process

(For maintainers)

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Commit: `git commit -m "chore: bump to v0.x.y"`
4. Tag: `git tag v0.x.y`
5. Push: `git push && git push --tags`
6. Packagist will automatically detect the release

## Getting Help

- **Documentation**: Check `docs/` directory
- **Examples**: See `examples/` directory
- **Discussions**: Use GitHub Discussions for questions
- **Issues**: Report bugs or request features via GitHub Issues

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You!

Your contributions help make this library better for everyone. We appreciate your time and effort!
