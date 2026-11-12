<?php

namespace BIWEB\LaravelOpenapi\Commands;

class MakeRequestBodyCommand extends MakeDefinitionCommand
{
    protected $name = 'make:openapi-request-body';

    protected $description = 'Create an OpenAPI RequestBody definition class';

    protected $type = 'OpenAPI RequestBody';

    protected function definition(): string
    {
        return 'RequestBody';
    }

    protected function directory(): string
    {
        return 'RequestBodies';
    }
}
