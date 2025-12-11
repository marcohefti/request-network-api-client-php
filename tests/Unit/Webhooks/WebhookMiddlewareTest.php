<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RequestSuite\RequestPhpClient\Webhooks\ParsedWebhookEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookDispatcher;
use RequestSuite\RequestPhpClient\Webhooks\WebhookMiddleware;
use RequestSuite\RequestPhpClient\Webhooks\Testing\WebhookTestHelper;
use Psr\Log\LoggerInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

final class WebhookMiddlewareTest extends TestCase
{
    public function testMiddlewareAttachesEventAndDispatches(): void
    {
        $factory = new Psr17Factory();
        $dispatcher = new WebhookDispatcher();
        $handledEvents = 0;
        $dispatcher->registerListener('payment.confirmed', function (ParsedWebhookEvent $event, array $context) use (&$handledEvents): void {
            $handledEvents++;
            self::assertSame('payment.confirmed', $event->eventName());
            self::assertSame('context', $context['source']);
        });

        $middleware = new WebhookMiddleware([
            'secret' => WebhookTestHelper::DEFAULT_TEST_SECRET,
            'dispatcher' => $dispatcher,
            'buildDispatchContext' => static function (ServerRequestInterface $request, ParsedWebhookEvent $event): array {
                return ['source' => 'context', 'request' => $request, 'event' => $event];
            },
        ], $factory, $factory);

        $request = WebhookTestHelper::createMockRequest([
            'payload' => $this->fixturePayload('payment-confirmed'),
            'requestFactory' => $factory,
            'streamFactory' => $factory,
        ]);

        $handler = new class ($factory) implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function __construct(private ResponseFactoryInterface $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return $this->factory->createResponse(204);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(1, $handledEvents);
        self::assertInstanceOf(ParsedWebhookEvent::class, $handler->request?->getAttribute('requestWebhook'));
    }

    public function testMiddlewareReturnsErrorResponseForInvalidSignature(): void
    {
        $factory = new Psr17Factory();
        $middleware = new WebhookMiddleware([
            'secret' => 'rk_test_other',
        ], $factory, $factory);

        $request = WebhookTestHelper::createMockRequest([
            'payload' => $this->fixturePayload('payment-confirmed'),
            'secret' => WebhookTestHelper::DEFAULT_TEST_SECRET,
            'requestFactory' => $factory,
            'streamFactory' => $factory,
        ]);

        $handler = new class ($factory) implements RequestHandlerInterface {
            public function __construct(private ResponseFactoryInterface $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse();
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
    }

    public function testMiddlewareCanShortCircuitWithOnEventResponse(): void
    {
        $factory = new Psr17Factory();
        $capturedError = null;

        $middleware = new WebhookMiddleware([
            'secret' => WebhookTestHelper::DEFAULT_TEST_SECRET,
            'onEvent' => static function (ParsedWebhookEvent $event, ServerRequestInterface $request) use ($factory): ResponseInterface {
                return $factory->createResponse(299);
            },
            'onError' => static function (Throwable $error) use (&$capturedError): void {
                $capturedError = $error;
            },
        ], $factory, $factory);

        $request = WebhookTestHelper::createMockRequest([
            'payload' => $this->fixturePayload('payment-confirmed'),
            'requestFactory' => $factory,
            'streamFactory' => $factory,
        ]);

        $handler = new class ($factory) implements RequestHandlerInterface {
            public int $calls = 0;

            public function __construct(private ResponseFactoryInterface $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->calls++;

                return $this->factory->createResponse(204);
            }
        };

        $response = $middleware->process($request, $handler);

        $message = $capturedError ? $capturedError->getMessage() : (string) $response->getBody();

        self::assertSame(299, $response->getStatusCode(), $message);
        self::assertSame(0, $handler->calls);
    }

    public function testMiddlewareHonoursVerificationBypass(): void
    {
        WebhookTestHelper::withVerificationDisabled(function (): void {
            $factory = new Psr17Factory();
            $middleware = new WebhookMiddleware([
                'secret' => 'rk_should_not_matter',
            ], $factory, $factory);

            $request = $factory
                ->createServerRequest('POST', 'https://example.test')
                ->withBody($factory->createStream((string) json_encode($this->fixturePayload('payment-confirmed'), JSON_THROW_ON_ERROR)));

            $handler = new class ($factory) implements RequestHandlerInterface {
                public function __construct(private ResponseFactoryInterface $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            };

            $response = $middleware->process($request, $handler);

            self::assertSame(200, $response->getStatusCode());
        });
    }

    public function testLoggerRedactsSensitiveContext(): void
    {
        $factory = new Psr17Factory();
        $logger = new class implements LoggerInterface {
            public array $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }

            public function emergency($message, array $context = []): void
            {
                $this->log('emergency', $message, $context);
            }
            public function alert($message, array $context = []): void
            {
                $this->log('alert', $message, $context);
            }
            public function critical($message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }
            public function error($message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }
            public function warning($message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }
            public function notice($message, array $context = []): void
            {
                $this->log('notice', $message, $context);
            }
            public function info($message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }
            public function debug($message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }
        };

        $middleware = new WebhookMiddleware([
            'secret' => WebhookTestHelper::DEFAULT_TEST_SECRET,
            'logger' => $logger,
        ], $factory, $factory);

        $request = WebhookTestHelper::createMockRequest([
            'payload' => $this->fixturePayload('payment-confirmed'),
            'requestFactory' => $factory,
            'streamFactory' => $factory,
        ]);

        $handler = new class ($factory) implements RequestHandlerInterface {
            public function __construct(private ResponseFactoryInterface $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        };

        $middleware->process($request, $handler);

        $found = false;
        foreach ($logger->records as $record) {
            if ($record['message'] === 'webhook:verified') {
                self::assertSame('***REDACTED***', $record['context']['signature']);
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'Expected webhook:verified log entry');
    }

    /**
     * @return array<string, mixed>
     */
    private function fixturePayload(string $name): array
    {
        $path = dirname(__DIR__, 3) . '/specs/fixtures/webhooks/' . $name . '.json';
        $contents = file_get_contents($path);

        return json_decode((string) $contents, true, 512, JSON_THROW_ON_ERROR);
    }
}
