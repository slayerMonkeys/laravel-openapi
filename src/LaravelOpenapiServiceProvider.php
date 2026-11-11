<?php

namespace BIWEB\LaravelOpenapi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BIWEB\LaravelOpenapi\Commands\LaravelOpenapiCommand;

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
            ->hasViews()
            ->hasMigration('create_openapi_table')
            ->hasCommand(LaravelOpenapiCommand::class);
    }
}
