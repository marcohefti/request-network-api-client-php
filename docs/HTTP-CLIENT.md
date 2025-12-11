# HTTP Client

The PHP client exposes a shared HTTP pipeline that mirrors the TypeScript `createHttpClient` helper.
Most integrations should use the domain facades (`requests()`, `payer()`, `currencies()`, etc.), but
the low-level client is available for custom flows or advanced instrumentation.

## Accessing the HttpClient

```php
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'userAgent' => 'my-app/1.0.0',
]);

$http = $client->http(); // RequestSuite\RequestPhpClient\Core\Http\HttpClient
```

`RequestClient` wires the `HttpClient` with:

- A `RequestClientConfig` (base URL, credentials, telemetry headers).
- An `HttpAdapter` (default: `Core\Http\Adapter\CurlHttpAdapter`).
- A `RetryPolicy` (default: exponential backoff with jitter, retrying on network failures, 408/429, and 5xx).
- Logging and runtime validation configuration.

## Making a Request

Use `Core\Http\RequestOptions` to describe a request and call `HttpClient::request()`:

```php
use RequestSuite\RequestPhpClient\Core\Http\RequestOptions;
use RequestSuite\RequestPhpClient\RequestClient;

$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'runtimeValidation' => true,
]);

$http = $client->http();

$options = new RequestOptions(
    'GET',
    '/v2/currencies',
    ['network' => 'sepolia'],
    [],
    null,
    5_000,
    'comma',
    [
        'operationId' => 'CurrenciesV2Controller_getNetworkTokens_v2',
    ]
);

$response = $http->request($options);

if ($response->status() !== 200) {
    throw new \RuntimeException('Unexpected status: ' . $response->status());
}

$body = $response->json(); // array<string, mixed>
```

`RequestOptions` fields:

- `method` – HTTP verb (`GET`, `POST`, etc.).
- `path` – Path relative to the configured base URL (e.g., `/v2/currencies`).
- `query` – Associative array of query params; `null` values are omitted.
- `headers` – Additional headers. Credential/telemetry headers are applied automatically.
- `body` – String or associative array (arrays are JSON‑encoded).
- `timeoutMs` – Per‑request timeout in milliseconds.
- `querySerializer` – `'comma'` (default), `'repeat'`, or a callable for custom query encoding.
- `meta` – Arbitrary metadata. Used by interceptors and runtime validation.

## Runtime Validation & Meta Options

The HTTP client merges global runtime validation with per‑request overrides:

- Global: `RequestClient::create(['runtimeValidation' => true|false|RuntimeValidationConfig])`.
- Per request: set `meta['validation']` on `RequestOptions`:

```php
$options = new RequestOptions(
    'GET',
    '/v2/currencies',
    ['network' => 'sepolia'],
    [],
    null,
    5_000,
    null,
    [
        'operationId' => 'CurrenciesV2Controller_getNetworkTokens_v2',
        'validation' => [
            'requests' => true,
            'responses' => true,
            'errors' => false,
        ],
    ]
);
```

Use `meta` to:

- Attach `operationId`/schema keys for validation.
- Pass per‑request interceptors (`meta['interceptors']`) implementing `Core\Http\Interceptor\Interceptor`.
- Override runtime validation for hot paths where upstream responses are fully trusted.

## Logging & Retry Behaviour

Configure logging when creating the client:

```php
$client = RequestClient::create([
    'apiKey' => $_ENV['REQUEST_API_KEY'],
    'logger' => static function (string $event, array $context): void {
        // $event: request:start, request:response, request:error
        // $context: method, url, status, attempt, delayMs, etc.
        error_log(sprintf('[request-api] %s %s', $event, json_encode($context)));
    },
    'logLevel' => 'info',
]);
```

Retry behaviour:

- The default `RetryPolicy` retries idempotent requests (`GET`, `HEAD`, `OPTIONS`, `PUT`, `DELETE`) on network
  errors, 408, 429, and 5xx responses.
- Per‑request retry overrides are expressed via the domain facades. When using `HttpClient` directly, construct
  a custom `RetryPolicy` and pass it into `RequestClientFactory` if you need fine‑grained control.

For most integrations, use domain facades instead of the low‑level HTTP client. Reach for `HttpClient` only when
you need custom endpoints, advanced interception, or tight control over retry/validation behaviour.

