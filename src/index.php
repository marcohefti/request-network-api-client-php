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
