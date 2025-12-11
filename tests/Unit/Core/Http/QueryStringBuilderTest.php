<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Exception\ConfigurationException;
use RequestSuite\RequestPhpClient\Core\Http\QueryStringBuilder;

final class QueryStringBuilderTest extends TestCase
{
    public function testDefaultCommaSerialisation(): void
    {
        $query = [
            'status' => ['open', 'closed'],
            'page' => 2,
        ];

        $builder = new QueryStringBuilder();
        $result = $builder->build($query);

        self::assertSame('status=open%2Cclosed&page=2', $result);
    }

    public function testRepeatSerialisation(): void
    {
        $query = [
            'status' => ['open', 'closed'],
            'page' => 2,
        ];

        $builder = new QueryStringBuilder();
        $result = $builder->build($query, 'repeat');

        self::assertSame('status=open&status=closed&page=2', $result);
    }

    public function testCustomSerializer(): void
    {
        $query = [
            'ids' => [1, 2, 3],
        ];

        $builder = new QueryStringBuilder();
        $result = $builder->build(
            $query,
            static function (string $key, mixed $value, callable $set, callable $_append): void {
                $set($key, implode('|', is_array($value) ? $value : [$value]));
            }
        );

        self::assertSame('ids=1%7C2%7C3', $result);
    }

    public function testInvalidKeyThrows(): void
    {
        $this->expectException(ConfigurationException::class);

        $builder = new QueryStringBuilder();
        $builder->build(['' => 'value']);
    }
}
