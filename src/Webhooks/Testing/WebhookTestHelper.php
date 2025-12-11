<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Testing;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RequestSuite\RequestPhpClient\Webhooks\WebhookSignatureVerifier;
use Stringable;

use function class_exists;
use function getenv;
use function hash_hmac;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class WebhookTestHelper
{
    public const DEFAULT_TEST_SECRET = 'whsec_test_secret';

    /**
     * @param array<mixed>|Stringable|string $payload
     */
    public static function generateSignature(
        Stringable|string|array $payload,
        string $secret = self::DEFAULT_TEST_SECRET
    ): string {
        $body = self::normalisePayload($payload);

        return hash_hmac(WebhookSignatureVerifier::DEFAULT_SIGNATURE_ALGORITHM, $body, $secret);
    }

    public static function isVerificationBypassed(): bool
    {
        if (getenv('REQUEST_WEBHOOK_DISABLE_VERIFICATION') === 'true') {
            return true;
        }

        return self::$bypassVerification;
    }

    public static function setVerificationBypass(bool $enabled): void
    {
        self::$bypassVerification = $enabled;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function withVerificationDisabled(callable $callback)
    {
        $previous = self::$bypassVerification;
        self::$bypassVerification = true;

        try {
            return $callback();
        } finally {
            self::$bypassVerification = $previous;
        }
    }

    /**
     * @param array{
     *     payload: array<mixed>|string|Stringable,
     *     secret?: string,
     *     headerName?: string,
     *     headers?: array<string, string>,
     *     method?: string,
     *     uri?: string,
     *     requestFactory?: ?ServerRequestFactoryInterface,
     *     streamFactory?: ?StreamFactoryInterface
     * } $options
     */
    public static function createMockRequest(array $options): ServerRequestInterface
    {
        $payload = $options['payload'];
        $secret = $options['secret'] ?? self::DEFAULT_TEST_SECRET;
        $headerName = $options['headerName'] ?? WebhookSignatureVerifier::DEFAULT_SIGNATURE_HEADER;
        $headers = $options['headers'] ?? [];
        $method = $options['method'] ?? 'POST';
        $uri = $options['uri'] ?? 'https://example.test/hooks';
        $requestFactory = $options['requestFactory'] ?? self::detectRequestFactory();
        $streamFactory = $options['streamFactory'] ?? self::detectStreamFactory();

        $body = self::normalisePayload($payload);
        $signature = self::generateSignature($payload, $secret);

        $stream = $streamFactory->createStream($body);

        $request = $requestFactory
            ->createServerRequest($method, $uri)
            ->withBody($stream);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request
            ->withHeader($headerName, WebhookSignatureVerifier::DEFAULT_SIGNATURE_ALGORITHM . '=' . $signature)
            ->withHeader('content-type', 'application/json');
    }

    public static function createMockResponse(?ResponseFactoryInterface $responseFactory = null): ResponseInterface
    {
        $factory = $responseFactory ?? self::detectResponseFactory();

        return $factory->createResponse();
    }

    /**
     * @param array<mixed>|Stringable|string $payload
     */
    private static function normalisePayload(Stringable|string|array $payload): string
    {
        if ($payload instanceof Stringable) {
            return (string) $payload;
        }

        if (is_string($payload)) {
            return $payload;
        }

        return (string) json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private static function detectRequestFactory(): ServerRequestFactoryInterface
    {
        if (self::$requestFactory !== null) {
            return self::$requestFactory;
        }

        return self::$requestFactory = self::instantiateFactory(ServerRequestFactoryInterface::class);
    }

    private static function detectStreamFactory(): StreamFactoryInterface
    {
        if (self::$streamFactory !== null) {
            return self::$streamFactory;
        }

        return self::$streamFactory = self::instantiateFactory(StreamFactoryInterface::class);
    }

    private static function detectResponseFactory(): ResponseFactoryInterface
    {
        if (self::$responseFactory !== null) {
            return self::$responseFactory;
        }

        return self::$responseFactory = self::instantiateFactory(ResponseFactoryInterface::class);
    }

    /**
     * @template T of ServerRequestFactoryInterface|StreamFactoryInterface|ResponseFactoryInterface
     * @param class-string<T> $type
     * @return T
     */
    private static function instantiateFactory(string $type)
    {
        if (class_exists('Nyholm\\Psr7\\Factory\\Psr17Factory')) {
            $factory = new Psr17Factory();
            if ($factory instanceof $type) {
                return $factory;
            }
        }

        throw new InvalidArgumentException(sprintf('Unable to auto-detect PSR-17 factory for %s', $type));
    }

    private static bool $bypassVerification = false;

    private static ?ServerRequestFactoryInterface $requestFactory = null;

    private static ?StreamFactoryInterface $streamFactory = null;

    private static ?ResponseFactoryInterface $responseFactory = null;
}
