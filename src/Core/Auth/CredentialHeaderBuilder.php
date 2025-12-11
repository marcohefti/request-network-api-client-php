<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Auth;

/**
 * Builds credential headers matching the TypeScript SDK behaviour.
 */
final class CredentialHeaderBuilder
{
    /**
     * @param array{
     *   apiKey?: string|null,
     *   clientId?: string|null,
     *   origin?: string|null
     * } $options
     *
     * @return array<string, string>
     */
    public function build(array $options): array
    {
        $headers = [];

        $apiKey = $this->trim($options['apiKey'] ?? null);
        if ($apiKey !== null) {
            $headers['x-api-key'] = $apiKey;
        }

        $clientId = $this->trim($options['clientId'] ?? null);
        if ($clientId !== null) {
            $headers['x-client-id'] = $clientId;
        }

        $origin = $this->trim($options['origin'] ?? null);
        if ($origin !== null) {
            $headers['Origin'] = $origin;
        }

        return $headers;
    }

    private function trim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
