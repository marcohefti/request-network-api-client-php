<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Adapter;

use CurlHandle;
use JsonException;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Exception\TransportException;
use RequestSuite\RequestPhpClient\Core\Http\HeaderBag;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;
use RuntimeException;

class CurlHttpAdapter implements HttpAdapter
{
    public function send(PendingRequest $request): Response
    {
        [$body, $headers] = $this->prepareBody($request->body(), $request->headers());

        [$status, $responseHeaders, $responseBody] = $this->performRequest(
            $request->url(),
            $request->method(),
            $headers,
            $body,
            $request->timeoutMs()
        );

        return new Response($status, $responseHeaders, $responseBody);
    }

    public function description(): string
    {
        return 'curl';
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, string> $headers
     * @return array{0: ?string, 1: array<string, string>}
     */
    private function prepareBody(array|string|null $input, array $headers): array
    {
        if (is_array($input)) {
            try {
                $encoded = json_encode($input, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new ConfigurationException(
                    'Unable to encode JSON request body: ' . $exception->getMessage(),
                    0,
                    $exception
                );
            }

            if (! $this->hasHeader($headers, 'Content-Type')) {
                $headers = (new HeaderBag())->merge($headers, ['Content-Type' => 'application/json']);
            }

            return [$encoded, $headers];
        }

        return [$input, $headers];
    }

    /**
     * @param array<string, string> $headers
     * @return array{0:int,1:array<string,string>,2:string}
     */
    protected function performRequest(
        string $url,
        string $method,
        array $headers,
        ?string $body,
        ?int $timeoutMs
    ): array {
        $handle = $this->initialiseHandle($url, $method);

        try {
            $this->applyTimeout($handle, $timeoutMs);
            $this->applyHeaders($handle, $headers);
            $this->applyBody($handle, $body);

            $raw = $this->executeHandle($handle);
            [$status, $headerSize] = $this->extractStatus($handle);
        } finally {
            curl_close($handle);
        }

        $headerBlob = substr($raw, 0, $headerSize);
        $bodyContent = substr($raw, $headerSize);

        $responseHeaders = $this->parseHeaders($headerBlob);

        return [$status, $responseHeaders, $bodyContent];
    }

    private function initialiseHandle(string $url, string $method): CurlHandle
    {
        if (! function_exists('curl_init')) {
            throw new TransportException('cURL extension is not available.');
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new TransportException('Failed to initialize cURL handle.');
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
        ]);

        return $handle;
    }

    private function applyTimeout(CurlHandle $handle, ?int $timeoutMs): void
    {
        if ($timeoutMs === null) {
            return;
        }

        curl_setopt($handle, CURLOPT_TIMEOUT_MS, max(0, $timeoutMs));
    }

    /**
     * @param array<string, string> $headers
     */
    private function applyHeaders(CurlHandle $handle, array $headers): void
    {
        if ($headers === []) {
            return;
        }

        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->formatHeaders($headers));
    }

    private function applyBody(CurlHandle $handle, ?string $body): void
    {
        if ($body === null) {
            return;
        }

        curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
    }

    private function executeHandle(CurlHandle $handle): string
    {
        $raw = curl_exec($handle);
        if ($raw === false) {
            $error = curl_error($handle);
            $code = curl_errno($handle);

            $message = sprintf('cURL request failed (%s)', $error !== '' ? $error : 'unknown error');
            $previous = $code !== 0 ? new RuntimeException($error !== '' ? $error : 'cURL error', $code) : null;

            throw new TransportException(
                $message,
                null,
                null,
                null,
                null,
                null,
                null,
                $previous
            );
        }

        if (! is_string($raw)) {
            throw new TransportException('Unexpected cURL response payload.');
        }

        return $raw;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function extractStatus(CurlHandle $handle): array
    {
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);

        return [$status, $headerSize];
    }

    /**
     * @param array<string, string> $headers
     * @return array<int, string>
     */
    private function formatHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            $out[] = sprintf('%s: %s', $name, $value);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $headerBlob): array
    {
        $headers = [];
        $blocks = preg_split("/\r\n\r\n/", trim($headerBlob));
        $lastBlock = $blocks !== false && $blocks !== [] ? end($blocks) : '';

        if ($lastBlock === false || $lastBlock === '') {
            return $headers;
        }

        $lines = preg_split("/\r\n/", $lastBlock);
        if ($lines === false) {
            $lines = [];
        }
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)] = trim($value);
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     */
    private function hasHeader(array $headers, string $name): bool
    {
        foreach (array_keys($headers) as $key) {
            if (strcasecmp($key, $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
