<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Core\Http;

use RequestSuite\RequestPhpClient\Validation\SchemaKeyFactory;

abstract class JsonApi
{
    protected JsonRequestHelper $json;

    protected SchemaKeyFactory $schemaKeys;

    protected PathBuilder $pathBuilder;

    public function __construct(
        protected readonly HttpClient $http,
        ?JsonRequestHelper $jsonHelper = null,
        ?PathBuilder $pathBuilder = null,
        ?SchemaKeyFactory $schemaKeys = null
    ) {
        $this->json = $jsonHelper ?? new JsonRequestHelper();
        $this->pathBuilder = $pathBuilder ?? new PathBuilder();
        $this->schemaKeys = $schemaKeys ?? new SchemaKeyFactory();
    }

    protected function schema(): SchemaKeyFactory
    {
        return $this->schemaKeys;
    }

    /**
     * @param array<string, string|int|float> $parameters
     */
    protected function buildPath(string $template, array $parameters = []): string
    {
        return $this->pathBuilder->build($template, $parameters);
    }
}
