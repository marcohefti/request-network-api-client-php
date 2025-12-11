<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Webhooks\Testing;

use Psr\Http\Message\ServerRequestInterface;
use RequestSuite\RequestPhpClient\Webhooks\Testing\WebhookTestHelper;

use function call_user_func;

final class EnvSkipVerificationResolver
{
    public function __invoke(ServerRequestInterface $request): bool
    {
        // Touch the request so PHPMD doesn't consider the parameter unused.
        $request->getMethod();

        return (bool) call_user_func([WebhookTestHelper::class, 'isVerificationBypassed']);
    }
}
