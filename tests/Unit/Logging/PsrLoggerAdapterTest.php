<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RequestSuite\RequestPhpClient\Logging\PsrLoggerAdapter;

final class PsrLoggerAdapterTest extends TestCase
{
    public function testAdapterBridgesToLoggerWithRedaction(): void
    {
        $logger = new class implements LoggerInterface {
            public string $level = '';
            public string $message = '';
            public array $context = [];

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

            public function log($level, $message, array $context = []): void
            {
                $this->level = $level;
                $this->message = (string) $message;
                $this->context = $context;
            }
        };

        $adapter = new PsrLoggerAdapter($logger);
        $adapter('request:error', ['authorization' => 'secret', 'meta' => ['x-api-key' => 'rk']]);

        self::assertSame('error', $logger->level);
        self::assertSame('request:error', $logger->message);
        self::assertSame('***REDACTED***', $logger->context['authorization']);
        self::assertSame('***REDACTED***', $logger->context['meta']['x-api-key']);
    }
}

