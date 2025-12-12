# Troubleshooting

Common issues and solutions when using the Request Network API Client for PHP.

## Table of Contents

- [Authentication Issues](#authentication-issues)
- [Network and Timeout Errors](#network-and-timeout-errors)
- [Webhook Verification Failures](#webhook-verification-failures)
- [Validation Errors](#validation-errors)
- [HTTP Client Issues](#http-client-issues)
- [Development Environment](#development-environment)

---

## Authentication Issues

### Invalid API Key Error

**Symptom:**
```
RequestApiException: Unauthorized (401)
errorCode: "UNAUTHORIZED"
```

**Solutions:**

1. **Verify API key format:**
   - Test keys start with `rk_test_`
   - Production keys start with `rk_live_`
   - Ensure no extra whitespace or newlines

2. **Check environment variable:**
   ```php
   // Debug: print your API key (remove in production!)
   var_dump($_ENV['REQUEST_API_KEY']);

   // Ensure it's loaded
   if (empty($_ENV['REQUEST_API_KEY'])) {
       throw new \RuntimeException('REQUEST_API_KEY not set');
   }
   ```

3. **Verify key is active:**
   - Log into Request Network dashboard
   - Check that the API key hasn't been revoked
   - Ensure you're using the correct environment (test vs production)

### Client ID Mismatch

**Symptom:**
```
RequestApiException: Invalid client ID
```

**Solution:**
```php
$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'clientId' => $_ENV['REQUEST_CLIENT_ID'], // Optional, only if required
]);
```

Client ID is optional for most operations. Only include if explicitly required by your use case.

---

## Network and Timeout Errors

### Connection Timeout

**Symptom:**
```
TransportException: cURL error 28: Operation timed out after 5000 milliseconds
```

**Solutions:**

1. **Increase timeout:**
   ```php
   use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;

   // Per-request timeout (in milliseconds)
   $options = new RequestOptions(
       'POST',
       '/v2/request',
       [],
       [],
       $body,
       30000  // 30 seconds
   );
   ```

2. **Check network connectivity:**
   ```bash
   curl -v https://api.request.network/health
   ```

3. **Verify firewall rules:**
   - Ensure outbound HTTPS (port 443) is allowed
   - Check if corporate proxy is interfering

### SSL/TLS Certificate Errors

**Symptom:**
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

**Solutions:**

1. **Update CA certificates:**
   ```bash
   # Ubuntu/Debian
   sudo apt-get update && sudo apt-get install ca-certificates

   # macOS (via Homebrew)
   brew install curl-ca-bundle
   ```

2. **Configure PHP to use system certificates:**
   ```ini
   ; php.ini
   curl.cainfo = "/etc/ssl/certs/ca-certificates.crt"
   openssl.cafile = "/etc/ssl/certs/ca-certificates.crt"
   ```

### Retry Exhausted

**Symptom:**
```
RequestApiException: Maximum retry attempts exceeded (4)
```

**Solutions:**

1. **Check Request Network status:**
   - Visit status page or check for maintenance windows
   - Verify API is operational

2. **Review retry configuration:**
   ```php
   use RequestSuite\RequestPhpClient\Core\Retry\RetryConfig;
   use RequestSuite\RequestPhpClient\Core\Retry\StandardRetryPolicy;

   $retryConfig = new RetryConfig(
       maxAttempts: 6,        // Increase from default 4
       baseDelayMs: 200,
       maxDelayMs: 5000,
       jitterFactor: 0.2
   );

   $client = RequestClient::create([
       'apiKey' => $_ENV['REQUEST_API_KEY'],
       'retryPolicy' => new StandardRetryPolicy($retryConfig),
   ]);
   ```

---

## Webhook Verification Failures

### Invalid Signature

**Symptom:**
```
RequestWebhookSignatureException: Signature verification failed
reason: "signature_mismatch"
```

**Solutions:**

1. **Verify webhook secret:**
   ```php
   // Check your secret matches the Request Network dashboard
   $secret = $_ENV['REQUEST_WEBHOOK_SECRET'];

   // Ensure no whitespace
   $secret = trim($secret);
   ```

2. **Check header names:**
   ```php
   use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;

   $result = $verifier->verifyFromRequest($request, [$secret], [
       'timestampHeader' => 'x-request-network-timestamp',  // Must match actual header
       'headerName' => 'x-request-network-signature',
   ]);
   ```

3. **Debug signature computation:**
   ```php
   // Manually verify
   $rawBody = (string) $request->getBody();
   $timestamp = $request->getHeaderLine('x-request-network-timestamp');
   $receivedSig = $request->getHeaderLine('x-request-network-signature');

   echo "Raw body: " . $rawBody . "\n";
   echo "Timestamp: " . $timestamp . "\n";
   echo "Received signature: " . $receivedSig . "\n";
   ```

### Timestamp Too Old

**Symptom:**
```
RequestWebhookSignatureException: Timestamp outside tolerance window
reason: "timestamp_too_old"
```

**Solutions:**

1. **Increase tolerance:**
   ```php
   $result = $verifier->verifyFromRequest($request, [$secret], [
       'toleranceMs' => 10 * 60 * 1000,  // 10 minutes (default is 5)
   ]);
   ```

2. **Check server time:**
   ```bash
   # Ensure server time is accurate
   date
   ntpq -p  # Check NTP sync
   ```

3. **Webhook queue delays:**
   - If using a queue system, ensure webhooks are processed promptly
   - Consider increasing tolerance for queued webhooks

### Missing Headers

**Symptom:**
```
RequestWebhookSignatureException: Missing required header
```

**Solution:**

Ensure your web server passes all headers to PHP:

```apache
# Apache .htaccess
SetEnvIf X-Request-Network-Signature .+ CUSTOM_HEADER=1
```

```nginx
# Nginx
location / {
    fastcgi_pass_header X-Request-Network-Signature;
    fastcgi_pass_header X-Request-Network-Timestamp;
}
```

---

## Validation Errors

### Schema Validation Failed

**Symptom:**
```
SchemaValidationException: Request validation failed
errors: [{"path": "/amount", "message": "Value is required"}]
```

**Solutions:**

1. **Check required fields:**
   ```php
   // Ensure all required fields are present
   $request = $client->requests()->create([
       'amount' => '100',              // Required
       'invoiceCurrency' => 'USD',     // Required
       'paymentCurrency' => 'ETH-sepolia-sepolia',  // Required
       'payee' => '0x...',             // Required
       'reference' => 'order-123',     // Recommended
   ]);
   ```

2. **Disable validation temporarily (debugging only):**
   ```php
   $client = RequestClient::create([
       'apiKey' => $_ENV['REQUEST_API_KEY'],
       'runtimeValidation' => false,  // Not recommended for production
   ]);
   ```

3. **Review error details:**
   ```php
   try {
       $client->requests()->create($data);
   } catch (\RequestSuite\RequestPhpClient\Validation\SchemaValidationException $e) {
       var_dump($e->errors());  // Array of validation errors
   }
   ```

### Unexpected Response Format

**Symptom:**
Response doesn't match expected structure.

**Solutions:**

1. **Check API version:**
   - Ensure you're using the correct domain facade (v1 vs v2)
   - Example: `$client->requests()` vs `$client->requestsV1()`

2. **Inspect raw response:**
   ```php
   use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;

   $response = $client->http()->request($options);
   var_dump($response->json());  // See actual response
   ```

---

## HTTP Client Issues

### PSR-18 Adapter Not Working

**Symptom:**
```
ConfigurationException: The provided httpAdapter must implement HttpAdapter
```

**Solution:**

Use the PSR-18 adapter wrapper:

```php
use RequestSuite\RequestPhpClient\Core\Http\Adapter\Psr18HttpAdapter;
use GuzzleHttp\Client;

$guzzle = new Client();
$adapter = new Psr18HttpAdapter($guzzle);

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'httpAdapter' => $adapter,
]);
```

### Memory Limit Exceeded

**Symptom:**
```
Fatal error: Allowed memory size exhausted
```

**Solutions:**

1. **Increase PHP memory limit:**
   ```ini
   ; php.ini
   memory_limit = 256M
   ```

2. **Process large result sets in chunks:**
   ```php
   // Instead of loading all at once
   $limit = 100;
   $offset = 0;

   do {
       $payments = $client->payments()->search([
           'limit' => $limit,
           'offset' => $offset,
       ]);

       // Process batch
       processBatch($payments);

       $offset += $limit;
   } while (count($payments) === $limit);
   ```

---

## Development Environment

### Composer Autoload Issues

**Symptom:**
```
Fatal error: Class 'RequestSuite\RequestPhpClient\RequestClient' not found
```

**Solutions:**

1. **Regenerate autoloader:**
   ```bash
   composer dump-autoload
   ```

2. **Verify installation:**
   ```bash
   composer show marcohefti/request-network-api-client
   ```

3. **Check require statement:**
   ```php
   require __DIR__ . '/vendor/autoload.php';
   ```

### PHPUnit Test Failures

**Symptom:**
Tests fail with "Class not found" or namespace errors.

**Solutions:**

1. **Install dev dependencies:**
   ```bash
   composer install --dev
   ```

2. **Clear PHPUnit cache:**
   ```bash
   rm -rf .phpunit.result.cache
   composer test
   ```

### PHPStan Errors After Update

**Symptom:**
```
PHPStan analysis failed with new errors
```

**Solutions:**

1. **Clear PHPStan cache:**
   ```bash
   vendor/bin/phpstan clear-result-cache
   composer stan
   ```

2. **Check PHP version:**
   ```bash
   php -v  # Must be >= 8.2
   ```

---

## Getting Help

If you're still experiencing issues:

1. **Check the documentation:**
   - [README.md](../README.md)
   - [ARCHITECTURE.md](ARCHITECTURE.md)
   - [TESTING.md](TESTING.md)
   - [WEBHOOKS.md](WEBHOOKS.md)

2. **Search existing issues:**
   - [GitHub Issues](https://github.com/marcohefti/request-network-api-client-php/issues)

3. **Create a new issue:**
   - Include PHP version, package version, and error messages
   - Provide a minimal reproduction example
   - Redact any sensitive information (API keys, secrets)

4. **Security vulnerabilities:**
   - **Do not** open public issues
   - See [SECURITY.md](../SECURITY.md) for reporting instructions
