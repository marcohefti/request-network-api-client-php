<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Retry;

final class RetryConfigFactory
{
    public function default(): RetryConfig
    {
        return new RetryConfig(
            3,
            250,
            5_000,
            2.0,
            RetryConfig::JITTER_FULL,
            [408, 425, 429, 500, 502, 503, 504],
            ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE']
        );
    }
}
