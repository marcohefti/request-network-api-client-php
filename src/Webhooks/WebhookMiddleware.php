<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RequestSuite\RequestPhpClient\Logging\Redactor;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\RequestWebhookSignatureException;
use RequestSuite\RequestPhpClient\Webhooks\Exceptions\WebhookMiddlewareException;
use RequestSuite\RequestPhpClient\Webhooks\ParsedWebhookEvent;
use RequestSuite\RequestPhpClient\Webhooks\Testing\EnvSkipVerificationResolver;
use Stringable;
use Throwable;

use function is_array;
use function is_callable;
use function is_string;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @phpstan-type WebhookDispatchContext array<string, mixed>
 * @phpstan-type DispatchContextBuilder callable(ServerRequestInterface, ParsedWebhookEvent): WebhookDispatchContext
 * @phpstan-type EventListener callable(ParsedWebhookEvent, ServerRequestInterface): (ResponseInterface|null|void)
 * @phpstan-type ErrorListener callable(Throwable, ServerRequestInterface): (ResponseInterface|null|void)
 * @phpstan-type RawBodyResolver callable(ServerRequestInterface): Stringable|string|StreamInterface
 * @phpstan-type SkipVerificationResolver callable(ServerRequestInterface): bool
 */
final class WebhookMiddleware implements MiddlewareInterface
{
    /**
     * @param array{
     *     secret: string|array<int, string>,
     *     headerName?: string,
     *     timestampHeader?: string,
     *     toleranceMs?: int,
     *     dispatcher?: ?WebhookDispatcher,
     *     logger?: ?LoggerInterface,
     *     onEvent?: ?callable,
     *     onError?: ?callable,
     *     buildDispatchContext?: ?callable,
     *     getRawBody?: ?callable,
     *     skipVerification?: ?callable,
     *     attribute?: string
     * } $options
     */
    public function __construct(
        array $options,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        ?WebhookParser $parser = null
    ) {
        $secret = $options['secret'] ?? null;
        if ($secret === null || (is_array($secret) && $secret === [])) {
            throw new WebhookMiddlewareException('WebhookMiddleware requires at least one webhook secret.');
        }

        $this->secret = $this->normaliseSecrets($secret);
        $this->headerName = $options['headerName'] ?? null;
        $this->timestampHeader = $options['timestampHeader'] ?? null;
        $this->toleranceMs = $options['toleranceMs'] ?? null;
        $this->dispatcher = $options['dispatcher'] ?? null;
        $this->logger = $options['logger'] ?? null;
        $this->onEvent = $options['onEvent'] ?? null;
        $this->onError = $options['onError'] ?? null;
        $this->buildDispatchContext = $options['buildDispatchContext'] ?? null;
        $this->getRawBody = $options['getRawBody'] ?? null;
        $this->skipResolver = $this->resolveSkipVerificationResolver($options['skipVerification'] ?? null);
        $this->attribute = $options['attribute'] ?? 'requestWebhook';
        $this->parser = $parser ?? new WebhookParser();
        $this->redactor = new Redactor();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rawBody = $this->resolveRawBody($request);

        $skipVerification = $this->shouldSkipVerification($request);

        try {
            $parserOptions = [
                'rawBody' => $rawBody,
                'headers' => $request,
                'secret' => $this->secret,
                'skipSignatureVerification' => $skipVerification,
            ];

            if ($this->headerName !== null) {
                $parserOptions['headerName'] = $this->headerName;
            }

            if ($this->timestampHeader !== null) {
                $parserOptions['timestampHeader'] = $this->timestampHeader;
            }

            if ($this->toleranceMs !== null) {
                $parserOptions['toleranceMs'] = $this->toleranceMs;
            }

            $parsed = $this->parser->parse($parserOptions);

            $requestWithAttr = $request->withAttribute($this->attribute, $parsed);
            $this->logEvent('webhook:verified', 'debug', [
                'event' => $parsed->eventName(),
                'signature' => $parsed->signature(),
                'matchedSecret' => $parsed->matchedSecret(),
            ]);

            if ($this->dispatcher !== null) {
                $contextBuilder = $this->buildDispatchContext ?? self::defaultDispatchContext();
                $context = $contextBuilder($requestWithAttr, $parsed);
                $this->dispatcher->dispatch($parsed, $context);
                $this->logEvent('webhook:dispatched', 'info', [
                    'event' => $parsed->eventName(),
                    'context' => $context,
                ]);
            }

            if (is_callable($this->onEvent)) {
                $response = ($this->onEvent)($parsed, $requestWithAttr);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }
            }

            return $handler->handle($requestWithAttr);
        } catch (Throwable $error) {
            return $this->handleError($error, $request);
        }
    }

    private function shouldSkipVerification(ServerRequestInterface $request): bool
    {
        return (bool) ($this->skipResolver)($request);
    }

    private function handleError(Throwable $error, ServerRequestInterface $request): ResponseInterface
    {
        $this->logError($error);

        if (is_callable($this->onError)) {
            $response = ($this->onError)($error, $request);
            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        return $this->createErrorResponse($error);
    }

    private function logError(Throwable $error): void
    {
        if ($error instanceof RequestWebhookSignatureException) {
            $this->logEvent('webhook:error', 'warning', [
                'header' => $error->headerName,
                'reason' => $error->reason,
                'signature' => $error->signature,
            ]);

            return;
        }

        $this->logEvent('webhook:error', 'error', ['exception' => $error]);
    }

    private function createErrorResponse(Throwable $error): ResponseInterface
    {
        $status = 500;
        $payload = [
            'error' => 'webhook_handler_failed',
        ];

        if ($error instanceof RequestWebhookSignatureException) {
            $status = $error->statusCode;
            $payload = [
                'error' => 'invalid_webhook_signature',
                'reason' => $error->reason,
            ];
        }

        $response = $this->responseFactory->createResponse($status);
        $stream = $this->streamFactory->createStream((string) json_encode($payload, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('content-type', 'application/json')
            ->withBody($stream);
    }

    /**
     *
     * @throws WebhookMiddlewareException
     */
    private function resolveRawBody(ServerRequestInterface $request): Stringable|string|StreamInterface
    {
        if (is_callable($this->getRawBody)) {
            $raw = ($this->getRawBody)($request);
            if ($this->isSupportedRawBody($raw)) {
                return $raw;
            }

            throw new WebhookMiddlewareException('Webhook raw body resolver returned an unsupported value.');
        }

        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $body;
    }

    private static function defaultDispatchContext(): callable
    {
        return static function (ServerRequestInterface $request, ParsedWebhookEvent $event): array {
            return [
                'request' => $request,
                'event' => $event,
            ];
        };
    }

    /**
     * @param array<int, string>|string $secret
     * @return array<int, string>|string
     */
    private function normaliseSecrets(string|array $secret): string|array
    {
        if (is_string($secret)) {
            return $secret;
        }

        $filtered = [];
        foreach ($secret as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $filtered[] = $candidate;
            }
        }

        return $filtered;
    }

    private function isSupportedRawBody(mixed $value): bool
    {
        return $value instanceof StreamInterface || $value instanceof Stringable || is_string($value);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logEvent(string $event, string $level, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->log($level, $event, $this->redactor->redactContext($context));
    }

    /**
     * @var string|array<int, string>
     */
    private string|array $secret;

    private ?string $headerName;

    private ?string $timestampHeader;

    private ?int $toleranceMs;

    private ?WebhookDispatcher $dispatcher;

    private ?LoggerInterface $logger;

    /** @var callable|null */
    private $onEvent;

    /** @var callable|null */
    private $onError;

    /** @var callable|null */
    private $buildDispatchContext;

    /** @var callable|null */
    private $getRawBody;

    /** @var SkipVerificationResolver */
    private $skipResolver;

    private string $attribute;

    private WebhookParser $parser;

    private Redactor $redactor;

    /**
     * @param SkipVerificationResolver|null $resolver
     * @return SkipVerificationResolver
     */
    private function resolveSkipVerificationResolver(?callable $resolver): callable
    {
        if ($resolver !== null) {
            return $resolver;
        }

        return new EnvSkipVerificationResolver();
    }
}
