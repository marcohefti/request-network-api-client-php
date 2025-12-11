<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks;

use BadMethodCallException;
use Closure;
use RequestSuite\RequestPhpClient\Webhooks\ParsedWebhookEvent;

use function is_array;
use function is_callable;
use function is_object;
use function is_string;

/**
 * @phpstan-type WebhookDispatchContext array<string, mixed>
 * @method callable on(string $event, callable $handler)
 * @method callable once(string $event, callable $handler)
 * @method void off(string $event, callable $handler)
 */
final class WebhookDispatcher
{
    /**
     * @var array<string, array<string, Closure(ParsedWebhookEvent, array<string, mixed>): void>>
     */
    private array $handlers = [];

    /**
     * @return callable(): void
     */
    public function registerListener(string $event, callable $handler): callable
    {
        [$handlerId, $callable] = $this->normaliseHandler($handler);
        $this->handlers[$event][$handlerId] = $callable;

        return function () use ($event, $handlerId): void {
            $this->removeHandler($event, $handlerId);
        };
    }

    /**
     * @return callable(): void
     */
    public function registerOnce(string $event, callable $handler): callable
    {
        /** @var callable|null $disposeRef */
        $disposeRef = null;
        $wrapped = function (ParsedWebhookEvent $eventPayload, array $context) use (&$disposeRef, $handler): void {
            if (is_callable($disposeRef)) {
                $disposeRef();
            }

            $handler($eventPayload, $context);
        };

        $dispose = $this->registerListener($event, $wrapped);
        $disposeRef = $dispose;

        return $dispose;
    }

    public function unregisterListener(string $event, callable $handler): void
    {
        [$handlerId] = $this->normaliseHandler($handler);
        $this->removeHandler($event, $handlerId);
    }

    public function clear(): void
    {
        $this->handlers = [];
    }

    public function handlerCount(?string $event = null): int
    {
        if ($event !== null) {
            return isset($this->handlers[$event]) ? count($this->handlers[$event]) : 0;
        }

        $total = 0;
        foreach ($this->handlers as $set) {
            $total += count($set);
        }

        return $total;
    }

    /**
     * @param WebhookDispatchContext $context
     */
    public function dispatch(ParsedWebhookEvent $event, array $context = []): void
    {
        $handlers = $this->handlers[$event->eventName()] ?? [];
        if ($handlers === []) {
            return;
        }

        foreach ($handlers as $callable) {
            $callable($event, $context);
        }
    }

    public function register(string $event, callable $handler): callable
    {
        $this->registerListener($event, $handler);

        return $handler;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return match ($name) {
            'on' => $this->registerListener(...$arguments),
            'once' => $this->registerOnce(...$arguments),
            'off' => $this->unregisterListener(...$arguments),
            default => throw new BadMethodCallException(sprintf('Undefined method %s::%s()', self::class, $name)),
        };
    }

    /**
     * @return array{string, Closure(ParsedWebhookEvent, array<string, mixed>): void}
     */
    private function normaliseHandler(callable $handler): array
    {
        if ($handler instanceof Closure) {
            return [$this->callableId($handler), $handler];
        }

        $callable = static function (ParsedWebhookEvent $event, array $context) use ($handler): void {
            $handler($event, $context);
        };

        return [$this->callableId($handler), $callable];
    }

    private function removeHandler(string $event, string $handlerId): void
    {
        if (! isset($this->handlers[$event])) {
            return;
        }

        unset($this->handlers[$event][$handlerId]);
        if ($this->handlers[$event] === []) {
            unset($this->handlers[$event]);
        }
    }

    private function callableId(callable $handler): string
    {
        if ($handler instanceof Closure) {
            return spl_object_hash($handler);
        }

        if (is_array($handler)) {
            $target = $handler[0] ?? null;
            $method = (string) ($handler[1] ?? '');

            if (is_object($target)) {
                return spl_object_hash($target) . '::' . $method;
            }

            return (string) $target . '::' . $method;
        }

        if (is_string($handler)) {
            return $handler;
        }

        if (is_object($handler)) {
            return spl_object_hash($handler) . '::__invoke';
        }

        return spl_object_hash((object) ['handler' => $handler]);
    }
}
