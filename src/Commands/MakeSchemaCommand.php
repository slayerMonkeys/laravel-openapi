<?php

namespace BIWEB\LaravelOpenapi\Commands;

class MakeSchemaCommand extends MakeDefinitionCommand
{
    protected $name = 'make:openapi-schema';

    protected $description = 'Create an OpenAPI Schema definition class';

    protected $type = 'OpenAPI Schema';

    protected function definition(): string
    {
        return 'Schema';
    }

    protected function directory(): string
    {
        return 'Schemas';
    }
}
