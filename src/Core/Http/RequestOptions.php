<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

/**
 * Value object describing the outgoing HTTP request.
 */
final class RequestOptions
{
    private string $method;

    private string $path;

    /**
     * @var array<string, scalar|array<int, scalar>|null>
     */
    private array $query;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var string|array<string, mixed>|null
     */
    private string|array|null $body;

    private ?int $timeoutMs;

    /**
     * @var 'comma'|'repeat'|callable|null
     */
    private $querySerializer;

    /**
     * @var array<string, mixed>
     */
    private array $meta;

    /**
     * @param array<string, scalar|array<int, scalar>|null> $query
     * @param array<string, string> $headers
     * @param string|array<string, mixed>|null $body
     * @param 'comma'|'repeat'|callable|null $querySerializer
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|array|null $body = null,
        ?int $timeoutMs = null,
        string|callable|null $querySerializer = null,
        array $meta = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->headers = $headers;
        $this->body = $body;
        $this->timeoutMs = $timeoutMs;
        $this->querySerializer = $querySerializer;
        $this->meta = $meta;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, scalar|array<int, scalar>|null>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return string|array<string, mixed>|null
     */
    public function body(): string|array|null
    {
        return $this->body;
    }

    public function timeoutMs(): ?int
    {
        return $this->timeoutMs;
    }

    /**
     * @return 'comma'|'repeat'|callable|null
     */
    public function querySerializer(): string|callable|null
    {
        return $this->querySerializer;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->headers,
            $this->body,
            $this->timeoutMs,
            $this->querySerializer,
            $meta
        );
    }
}
