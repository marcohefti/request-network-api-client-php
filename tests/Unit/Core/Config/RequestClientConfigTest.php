<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Config;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\RequestClientConfig;
use RequestSuite\RequestPhpClient\Core\Config\RequestEnvironment;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;

final class RequestClientConfigTest extends TestCase
{
    public function testDefaultsProvideProductionBaseUrl(): void
    {
        $config = RequestClientConfig::fromArray([]);

        $environment = new RequestEnvironment();

        self::assertSame($environment->baseUrl(), $config->baseUrl());
        self::assertNull($config->environment());
    }

    public function testExplicitBaseUrlTakesPrecedenceOverEnvironment(): void
    {
        $config = RequestClientConfig::fromArray([
            'baseUrl' => 'https://custom-host.example/',
        ]);

        self::assertSame('https://custom-host.example', $config->baseUrl());
        self::assertNull($config->environment());
    }

    public function testConfigurationMergesHeadersAndTelemetry(): void
    {
        $config = RequestClientConfig::fromArray([
            'apiKey' => 'sk_test',
            'clientId' => 'client_123',
            'origin' => 'WooCommerce',
            'headers' => ['X-Custom' => 'value'],
            'userAgent' => 'RequestSuite/PHP',
            'sdk' => ['name' => 'woo-extension', 'version' => '1.2.3'],
        ]);

        self::assertSame('sk_test', $config->apiKey());
        self::assertSame('client_123', $config->clientId());
        self::assertSame('WooCommerce', $config->origin());
        self::assertSame(['X-Custom' => 'value'], $config->headers());
        self::assertSame('RequestSuite/PHP', $config->userAgent());
        self::assertSame(['name' => 'woo-extension', 'version' => '1.2.3'], $config->sdk());
    }

    public function testInvalidBaseUrlThrows(): void
    {
        $this->expectException(ConfigurationException::class);
        RequestClientConfig::fromArray(['baseUrl' => '']);
    }

    public function testUnknownEnvironmentThrows(): void
    {
        $this->expectException(ConfigurationException::class);
        RequestClientConfig::fromArray(['environment' => 'staging']);
    }
}
