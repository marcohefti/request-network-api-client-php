<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

use RequestSuite\RequestPhpClient\Validation\Overrides\OverrideRegistrar;

final class SchemaRegistryBootstrapper
{
    public function __construct(
        private readonly SchemaManifestLoader $manifestLoader,
        private readonly OverrideRegistrar $overrideRegistrar
    ) {
    }

    public function bootstrap(SchemaRegistry $registry, ?SchemaRegistryBootConfig $config = null): void
    {
        $config ??= new SchemaRegistryBootConfig();

        if ($config->shouldLoadGeneratedSchemas()) {
            $this->manifestLoader->registerGeneratedSchemas($registry, $config->manifestPath());
        }

        if ($config->shouldRegisterOverrides()) {
            $this->overrideRegistrar->registerDefaults($registry);
        }
    }
}
