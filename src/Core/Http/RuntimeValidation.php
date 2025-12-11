<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

final class RuntimeValidation
{
    /**
     * @param bool|array<string, bool>|RuntimeValidationConfig|null $option
     */
    public function normalise(mixed $option = null): RuntimeValidationConfig
    {
        if ($option instanceof RuntimeValidationConfig) {
            return $option->copy();
        }

        if (is_bool($option)) {
            return new RuntimeValidationConfig($option, $option, $option);
        }

        $defaults = new RuntimeValidationConfig(true, true, true);
        if (! is_array($option)) {
            return $defaults;
        }

        return new RuntimeValidationConfig(
            $this->normaliseBoolean($option['requests'] ?? null, $defaults->requests),
            $this->normaliseBoolean($option['responses'] ?? null, $defaults->responses),
            $this->normaliseBoolean($option['errors'] ?? null, $defaults->errors)
        );
    }

    /**
     * @param bool|array<string, bool>|RuntimeValidationConfig|null $override
     */
    public function merge(RuntimeValidationConfig $base, mixed $override = null): RuntimeValidationConfig
    {
        if ($override === null) {
            return $base->copy();
        }

        if ($override instanceof RuntimeValidationConfig) {
            return $override->copy();
        }

        if (is_bool($override)) {
            return new RuntimeValidationConfig($override, $override, $override);
        }

        if (! is_array($override)) {
            return $base->copy();
        }

        return new RuntimeValidationConfig(
            $this->normaliseBoolean($override['requests'] ?? null, $base->requests),
            $this->normaliseBoolean($override['responses'] ?? null, $base->responses),
            $this->normaliseBoolean($override['errors'] ?? null, $base->errors)
        );
    }

    public function duplicate(RuntimeValidationConfig $config): RuntimeValidationConfig
    {
        return $config->copy();
    }

    private function normaliseBoolean(mixed $value, bool $fallback): bool
    {
        return is_bool($value) ? $value : $fallback;
    }
}
