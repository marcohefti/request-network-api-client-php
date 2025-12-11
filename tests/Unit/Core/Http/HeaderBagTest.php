<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\HeaderBag;

final class HeaderBagTest extends TestCase
{
    public function testMergeTreatsHeaderNamesCaseInsensitively(): void
    {
        $bag = new HeaderBag();
        $merged = $bag->merge(
            ['X-Foo' => 'one', 'X-Bar' => 'two'],
            ['x-foo' => 'two'],
            ['custom-header' => 'value']
        );

        self::assertSame(
            [
                'x-foo' => 'two',
                'X-Bar' => 'two',
                'custom-header' => 'value',
            ],
            $merged
        );
    }

    public function testMergePreservesLastProvidedCasing(): void
    {
        $bag = new HeaderBag();
        $merged = $bag->merge(
            ['X-Test' => 'a'],
            ['x-test' => 'b'],
            ['X-Test' => 'c']
        );

        self::assertSame(['X-Test' => 'c'], $merged);
    }
}
