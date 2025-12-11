<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Validation;

use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistryBootConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaValidationOptions;
use RequestSuite\RequestPhpClient\Validation\SchemaValidationException;
use RequestSuite\RequestPhpClient\Validation\SchemaValidator;

final class SchemaRegistryTest extends TestCase
{
    public function testRegisterAndResolveSchema(): void
    {
        $bootConfig = (new SchemaRegistryBootConfig())
            ->withoutGeneratedSchemas()
            ->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);
        $key = (new SchemaKeyFactory())->response('TestOperation', 200);

        $schema = [
            'type' => 'object',
            'required' => ['value'],
            'properties' => [
                'value' => ['type' => 'string'],
            ],
        ];

        $registry->register($key, $schema);

        $entry = $registry->resolve($key);

        self::assertNotNull($entry);

        $validator = new SchemaValidator($registry, new Validator());
        $result = $validator->parseWithRegistry(
            $key,
            ['value' => 'ok'],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testParseWithRegistryFailsWhenMissing(): void
    {
        $bootConfig = (new SchemaRegistryBootConfig())->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);
        $validator = new SchemaValidator($registry);
        $key = (new SchemaKeyFactory())->response('MissingOperation', 200);

        $result = $validator->parseWithRegistry(
            $key,
            ['value' => 'ok'],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertFalse($result->isSuccess());
        self::assertInstanceOf(SchemaValidationException::class, $result->error());
    }

    public function testSkipOnMissingSchemaReturnsData(): void
    {
        $bootConfig = (new SchemaRegistryBootConfig())->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);
        $validator = new SchemaValidator($registry);
        $key = (new SchemaKeyFactory())->response('SkipOperation', 200);

        $options = (new SchemaValidationOptions())->enableSkipOnMissingSchema();

        $result = $validator->parseWithRegistry(
            $key,
            ['value' => 'ok'],
            new RuntimeValidationConfig(true, true, true),
            $options
        );

        self::assertTrue($result->isSuccess());
        self::assertSame(['value' => 'ok'], $result->data());
    }
}
