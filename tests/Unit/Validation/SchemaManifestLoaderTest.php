<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use RequestSuite\RequestPhpClient\Validation\SchemaEntry;
use RequestSuite\RequestPhpClient\Validation\SchemaKey;
use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;
use RequestSuite\RequestPhpClient\Validation\SchemaManifestLoader;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistry;
use RequestSuite\RequestPhpClient\Validation\SchemaRegistryBootConfig;

final class SchemaManifestLoaderTest extends TestCase
{
    public function testRegistersSchemasFromManifest(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'schema-loader-' . uniqid();
        $manifestDir = $tempDir . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . 'Validation' . DIRECTORY_SEPARATOR . 'Schemas';
        $specPath = $tempDir . DIRECTORY_SEPARATOR . 'spec.json';

        self::assertTrue(mkdir($manifestDir, 0777, true));

        $spec = [
            'components' => [
                'schemas' => [
                    'Example' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/v1/example' => [
                    'get' => [
                        'operationId' => 'ExampleController_get',
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/Example',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($specPath, json_encode($spec, JSON_PRETTY_PRINT));

        $manifestPath = $manifestDir . DIRECTORY_SEPARATOR . 'index.json';
        $manifest = [
            'specPath' => $specPath,
            'entries' => [
                [
                    'key' => [
                        'operationId' => 'ExampleController_get',
                        'kind' => SchemaKey::KIND_RESPONSE,
                        'status' => 200,
                        'variant' => 'application/json',
                    ],
                    'pointer' => '/paths/~1v1~1example/get/responses/200/content/application~1json/schema',
                ],
            ],
        ];

        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));

        $bootConfig = (new SchemaRegistryBootConfig())
            ->withoutGeneratedSchemas()
            ->withoutOverrides();
        $registry = new SchemaRegistry(bootConfig: $bootConfig);

        $loader = new SchemaManifestLoader();
        $loader->registerGeneratedSchemas($registry, $manifestPath);

        $schemaKey = (new SchemaKeyFactory())->response('ExampleController_get', 200, 'application/json');
        $entry = $registry->resolve($schemaKey);
        self::assertInstanceOf(SchemaEntry::class, $entry);

        $schema = $entry->schema();
        self::assertIsObject($schema);
        self::assertObjectHasProperty('$schema', $schema);
        self::assertObjectHasProperty('components', $schema);

        $this->removeDirectory($tempDir);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $fileInfo->isDir()
                ? rmdir($fileInfo->getPathname())
                : unlink($fileInfo->getPathname());
        }

        rmdir($path);
    }
}
