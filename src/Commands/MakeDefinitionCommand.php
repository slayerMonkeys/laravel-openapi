<?php

namespace BIWEB\LaravelOpenapi\Commands;

use BIWEB\LaravelOpenapi\Definitions\Reusable;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

/** Generates a definition class in app/OpenApi/{directory}, from a stub the application can publish and edit. */
abstract class MakeDefinitionCommand extends GeneratorCommand
{
    /** The definition class extended, also the suffix of the generated class: Schema, Parameters… */
    abstract protected function definition(): string;

    abstract protected function directory(): string;

    protected function getStub(): string
    {
        $stub = 'stubs/openapi.'.Str::kebab($this->definition()).'.stub';

        return file_exists($custom = $this->laravel->basePath($stub)) ? $custom : __DIR__."/../../{$stub}";
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\OpenApi\\'.$this->directory();
    }

    // make:openapi-response UserCreated gives UserCreatedResponse
    protected function getNameInput(): string
    {
        return Str::finish(parent::getNameInput(), $this->definition());
    }

    protected function buildClass($name): string
    {
        $class = parent::buildClass($name);

        // the stubs describe a reusable class: without --reusable, the marker is removed
        return $this->option('reusable') ? $class : str_replace(['use '.Reusable::class.";\n", ' implements Reusable'], '', $class);
    }

    /** @return list<array<int, mixed>> */
    protected function getOptions(): array
    {
        return [
            ['reusable', 'r', InputOption::VALUE_NONE, 'Emit the class once in components and reference it by $ref'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'],
        ];
    }
}
