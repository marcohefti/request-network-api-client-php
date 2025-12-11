<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Webhooks;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Webhooks\ParsedWebhookEvent;
use RequestSuite\RequestPhpClient\Webhooks\WebhookDispatcher;
use RequestSuite\RequestPhpClient\Webhooks\WebhookParser;

final class WebhookDispatcherTest extends TestCase
{
    public function testDispatchInvokesHandlersWithContext(): void
    {
        $dispatcher = new WebhookDispatcher();
        $event = $this->parsedEvent('payment-confirmed');

        $receivedContext = null;
        $dispatcher->registerListener($event->eventName(), function (ParsedWebhookEvent $payload, array $context) use (&$receivedContext, $event): void {
            self::assertSame($event->eventName(), $payload->eventName());
            $receivedContext = $context;
        });

        $dispatcher->dispatch($event, ['foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], $receivedContext);
    }

    public function testOnceHandlerRunsSingleTime(): void
    {
        $dispatcher = new WebhookDispatcher();
        $event = $this->parsedEvent('payment-confirmed');
        $invocations = 0;

        $dispatcher->registerOnce($event->eventName(), function () use (&$invocations): void {
            $invocations++;
        });

        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event);

        self::assertSame(1, $invocations);
        self::assertSame(0, $dispatcher->handlerCount($event->eventName()));
    }

    public function testOffRemovesMatchingHandler(): void
    {
        $dispatcher = new WebhookDispatcher();
        $event = $this->parsedEvent('payment-confirmed');

        $handler = static function (): void {
        };

        $dispatcher->registerListener($event->eventName(), $handler);
        self::assertSame(1, $dispatcher->handlerCount($event->eventName()));

        $dispatcher->unregisterListener($event->eventName(), $handler);

        self::assertSame(0, $dispatcher->handlerCount($event->eventName()));
    }

    private function parsedEvent(string $fixture): ParsedWebhookEvent
    {
        $path = dirname(__DIR__, 3) . '/specs/fixtures/webhooks/' . $fixture . '.json';
        $payload = (string) file_get_contents($path);

        $parser = new WebhookParser();

        return $parser->parse([
            'rawBody' => $payload,
            'headers' => ['content-type' => 'application/json'],
            'skipSignatureVerification' => true,
        ]);
    }
}
