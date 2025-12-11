<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http\Interceptor;

final class LogLevel
{
    public const SILENT = 'silent';
    public const ERROR = 'error';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    private function __construct()
    {
    }
}
