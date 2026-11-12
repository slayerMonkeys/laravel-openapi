<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Commands\GenerateCommand;
use BIWEB\LaravelOpenapi\Commands\MakeParametersCommand;
use BIWEB\LaravelOpenapi\Commands\MakeRequestBodyCommand;
use BIWEB\LaravelOpenapi\Commands\MakeResponseCommand;
use BIWEB\LaravelOpenapi\Commands\MakeSchemaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelOpenapiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-openapi')
            ->hasConfigFile()
            ->hasRoute('openapi')
            ->hasCommands(GenerateCommand::class, MakeSchemaCommand::class, MakeParametersCommand::class, MakeRequestBodyCommand::class, MakeResponseCommand::class);
    }

    // package-tools has no helper for stubs: the make:openapi-* commands read a published one first
    public function packageBooted(): void
    {
        $this->publishes([__DIR__.'/../stubs' => $this->app->basePath('stubs')], 'openapi-stubs');
    }
}
