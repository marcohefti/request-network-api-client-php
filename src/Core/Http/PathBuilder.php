<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

final class PathBuilder
{
    /**
     * @param array<string, string|int|float> $parameters
     */
    public function build(string $template, array $parameters = []): string
    {
        return preg_replace_callback(
            '/\{([^}]+)\}/',
            static function (array $matches) use ($parameters): string {
                $key = $matches[1];
                if (! array_key_exists($key, $parameters)) {
                    return '';
                }

                return rawurlencode((string) $parameters[$key]);
            },
            $template
        ) ?? $template;
    }
}
