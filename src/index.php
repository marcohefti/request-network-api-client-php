<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient;

/**
 * Namespaced helper mirroring the TypeScript `createRequestClient` factory.
 *
 * @param array<string, mixed> $options
 */
function createRequestClient(array $options = []): RequestClient
{
    /** @var callable(array<string, mixed>): RequestClient $factory */
    $factory = [RequestClient::class, 'create'];

    return $factory($options);
}

/**
 * Helper mirroring TypeScript `createRequestClientFromEnv` that reads from PHP env arrays.
 *
 * @param array<string, string>|null $env
 */
function createRequestClientFromEnv(?array $env = null): RequestClient
{
    /** @var callable(array<string, string>|null): RequestClient $factory */
    $factory = [RequestClient::class, 'createFromEnv'];

    return $factory($env);
}
