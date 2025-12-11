<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Config;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Config\EnvironmentClientFactory;
use RequestSuite\RequestPhpClient\Core\Config\RequestEnvironment;

final class EnvironmentClientFactoryTest extends TestCase
{
    public function testCreateRequestClientUsesModernKeys(): void
    {
        $client = EnvironmentClientFactory::createRequestClient([
            'REQUEST_API_URL' => 'https://env.example',
            'REQUEST_API_KEY' => 'api_key',
            'REQUEST_CLIENT_ID' => 'client_id',
        ]);

        $config = $client->config();
        self::assertSame('https://env.example', $config->baseUrl());
        self::assertSame('api_key', $config->apiKey());
        self::assertSame('client_id', $config->clientId());
    }

    public function testCreateRequestClientFallsBackToLegacyKeys(): void
    {
        $client = EnvironmentClientFactory::createRequestClient([
            'REQUEST_API_URL' => '',
            'REQUEST_SDK_BASE_URL' => 'https://legacy.example',
            'REQUEST_SDK_API_KEY' => 'legacy_key',
            'REQUEST_SDK_CLIENT_ID' => 'legacy_client',
        ]);

        $config = $client->config();
        self::assertSame('https://legacy.example', $config->baseUrl());
        self::assertSame('legacy_key', $config->apiKey());
        self::assertSame('legacy_client', $config->clientId());
    }

    public function testCreateRequestClientDefaultsToProductionEnvironment(): void
    {
        $client = EnvironmentClientFactory::createRequestClient([]);

        $environment = new RequestEnvironment();
        self::assertSame($environment->baseUrl(), $client->config()->baseUrl());
    }
}
