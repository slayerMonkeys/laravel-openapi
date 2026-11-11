<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Commands\LaravelOpenapiCommand;
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
            ->hasCommand(LaravelOpenapiCommand::class);
    }
}
