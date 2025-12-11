<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Core\Http\RuntimeValidationConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistryBootConfig;
use RequestSuite\RequestPhpClient\Validation\SchemaValidator;

final class SchemaValidatorTest extends TestCase
{
    public function testParseWithSchemaReportsValidationErrors(): void
    {
        $bootConfig = (new SchemaRegistryBootConfig())
            ->withoutGeneratedSchemas()
            ->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);
        $validator = new SchemaValidator($registry);

        $schema = [
            'type' => 'object',
            'required' => ['value'],
            'properties' => [
                'value' => ['type' => 'string'],
            ],
        ];

        $result = $validator->parseWithSchema($schema, ['value' => 123], 'Test payload');

        self::assertFalse($result->isSuccess());
        self::assertSame('Test payload', $result->error()?->getMessage());
        self::assertNotEmpty($result->error()?->context());
    }

    public function testPreprocessorIsAppliedBeforeValidation(): void
    {
        $bootConfig = (new SchemaRegistryBootConfig())
            ->withoutGeneratedSchemas()
            ->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);
        $key = (new SchemaKeyFactory())->response('PreprocessOperation', 200);

        $schema = [
            'type' => 'object',
            'properties' => [
                'value' => ['type' => 'string'],
            ],
            'additionalProperties' => true,
        ];

        $registry->register(
            $key,
            $schema,
            static function (mixed $value) {
                if (is_array($value) && array_key_exists('value', $value) && $value['value'] === null) {
                    unset($value['value']);
                }

                return $value;
            },
            SchemaEntry::SOURCE_OVERRIDE
        );

        $validator = new SchemaValidator($registry);
        $result = $validator->parseWithRegistry(
            $key,
            ['value' => null],
            new RuntimeValidationConfig(true, true, true)
        );

        self::assertTrue($result->isSuccess());
    }
}
