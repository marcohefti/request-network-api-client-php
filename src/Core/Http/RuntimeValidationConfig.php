<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

final class RuntimeValidationConfig
{
    public bool $requests;
    public bool $responses;
    public bool $errors;

    public function __construct(bool $requests, bool $responses, bool $errors)
    {
        $this->requests = $requests;
        $this->responses = $responses;
        $this->errors = $errors;
    }

    public function copy(): self
    {
        return new self($this->requests, $this->responses, $this->errors);
    }
}
