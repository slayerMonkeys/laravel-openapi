<?php

namespace BIWEB\LaravelOpenapi\Commands;

class MakeParametersCommand extends MakeDefinitionCommand
{
    protected $name = 'make:openapi-parameters';

    protected $description = 'Create an OpenAPI Parameters definition class';

    protected $type = 'OpenAPI Parameters';

    protected function definition(): string
    {
        return 'Parameters';
    }

    protected function directory(): string
    {
        return 'Parameters';
    }
}
