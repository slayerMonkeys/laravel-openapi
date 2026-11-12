<?php

namespace BIWEB\LaravelOpenapi\Commands;

class MakeResponseCommand extends MakeDefinitionCommand
{
    protected $name = 'make:openapi-response';

    protected $description = 'Create an OpenAPI Response definition class';

    protected $type = 'OpenAPI Response';

    protected function definition(): string
    {
        return 'Response';
    }

    protected function directory(): string
    {
        return 'Responses';
    }
}
