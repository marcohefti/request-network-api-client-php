<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Domains\Requests;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Domains\Requests\RequestQueryBuilder;

final class RequestQueryBuilderTest extends TestCase
{
    public function testBuildFiltersNullValues(): void
    {
        $builder = new RequestQueryBuilder();
        $query = $builder->build([
            'wallet' => '0xabc',
            'amount' => null,
            'flags' => [1, null, 2],
        ]);

        self::assertSame([
            'wallet' => '0xabc',
            'flags' => [1, 2],
        ], $query);
    }

    public function testBuildReturnsNullWhenEmpty(): void
    {
        $builder = new RequestQueryBuilder();
        self::assertNull($builder->build([]));
    }
}
