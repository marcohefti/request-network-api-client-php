<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use Opis\JsonSchema\Errors\ValidationError;
use RuntimeException;

final class SchemaValidationException extends RuntimeException
{
    /**
     * @var array<string, mixed>
     */
    private array $context;

    public function __construct(string $message, ?ValidationError $error = null)
    {
        parent::__construct($message);
        if ($error !== null) {
            $this->context = $this->formatError($error);
            return;
        }

        $this->context = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatError(ValidationError $error): array
    {
        $path = $error->data()->fullPath();
        $formattedPath = $path === [] ? '$' : '$.' . implode('.', array_map(
            static fn ($segment): string => is_int($segment) ? (string) $segment : $segment,
            $path
        ));

        $children = array_map(
            fn (ValidationError $child) => $this->formatError($child),
            $error->subErrors()
        );

        $args = $error->args();

        return array_filter([
            'keyword' => $error->keyword(),
            'message' => $error->message(),
            'path' => $formattedPath,
            'args' => $args !== [] ? $args : null,
            'children' => $children !== [] ? $children : null,
        ], static fn ($value) => $value !== null);
    }
}
