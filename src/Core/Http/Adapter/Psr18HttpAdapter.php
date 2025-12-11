<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Adapter;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Exception\TransportException;
use RequestSuite\RequestPhpClient\Core\Http\HeaderBag;
use RequestSuite\RequestPhpClient\Core\Http\HttpAdapter;
use RequestSuite\RequestPhpClient\Core\Http\PendingRequest;
use RequestSuite\RequestPhpClient\Core\Http\Response;

final class Psr18HttpAdapter implements HttpAdapter
{
    private ClientInterface $client;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function send(PendingRequest $request): Response
    {
        [$body, $headers] = $this->prepareBody($request->body(), $request->headers());

        $psrRequest = $this->requestFactory->createRequest($request->method(), $request->url());
        foreach ($headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        if ($body !== null) {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($body));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $exception) {
            throw new TransportException(
                $exception->getMessage(),
                null,
                null,
                null,
                null,
                null,
                null,
                $exception
            );
        }

        $headersOut = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headersOut[$name] = implode(', ', $values);
        }

        return new Response(
            $psrResponse->getStatusCode(),
            $headersOut,
            (string) $psrResponse->getBody()
        );
    }

    public function description(): string
    {
        return 'psr-18';
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, string> $headers
     * @return array{0:?string,1:array<string,string>}
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
