<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Logging;

use Psr\Log\LoggerInterface;

final class PsrLoggerAdapter
{
    /**
     * @param callable(array<string, mixed>): array<string, mixed>|null $redactor
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $defaultLevel = 'info',
        ?callable $redactor = null
    ) {
        if ($redactor !== null) {
            $this->redactor = $redactor;

            return;
        }

        $instance = new Redactor();
        $this->redactor = static fn (array $context): array => $instance->redactContext($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(string $event, array $context = []): void
    {
        $level = self::EVENT_LEVELS[$event] ?? $this->defaultLevel;
        $redacted = ($this->redactor)($context);

        $this->logger->log($level, $event, $redacted);
    }

    /**
     * @var array<string, string>
     */
    private const EVENT_LEVELS = [
        'request:start' => 'debug',
        'request:response' => 'info',
        'request:error' => 'error',
        'webhook:verified' => 'debug',
        'webhook:dispatched' => 'info',
        'webhook:error' => 'error',
    ];

    /** @var callable(array<string, mixed>): array<string, mixed> */
    private $redactor;
}
