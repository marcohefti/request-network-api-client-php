<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

/**
 * Case-insensitive header collection helper.
 */
final class HeaderBag
{
    /**
     * @param array<string, string> ...$sources
     *
     * @return array<string, string>
     */
    public function merge(array ...$sources): array
    {
        $values = [];
        $originalKeys = [];

        foreach ($sources as $headers) {
            foreach ($headers as $name => $value) {
                $normalized = $this->normalizeName($name);
                $originalKeys[$normalized] = $name;
                $values[$normalized] = (string) $value;
            }
        }

        $result = [];
        foreach ($values as $normalized => $value) {
            $key = $originalKeys[$normalized] ?? $normalized;
            $result[$key] = $value;
        }

        return $result;
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
