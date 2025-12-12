---
name: Bug Report
about: Report a bug to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

## Bug Description

A clear and concise description of the bug.

## Steps to Reproduce

1. Create a client with '...'
2. Call method '...'
3. See error

## Expected Behavior

What you expected to happen.

## Actual Behavior

What actually happened.

## Code Sample

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => 'rk_test_...',
]);

// Code that reproduces the bug
```

## Error Output

```
Paste any error messages, stack traces, or log output here
```

## Environment

- PHP version: [e.g., 8.2.10]
- Package version: [e.g., 0.5.1]
- Operating System: [e.g., macOS 14.0, Ubuntu 22.04]
- HTTP client (if using PSR-18): [e.g., Guzzle 7.8.0, Symfony HttpClient]

## Additional Context

Add any other context about the problem, such as:
- Does this happen consistently or intermittently?
- Did this work in a previous version?
- Any relevant configuration or environment variables
