<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

interface HttpAdapter
{
    public function send(PendingRequest $request): Response;

    public function description(): string;
}
