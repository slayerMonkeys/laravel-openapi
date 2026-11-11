<?php

namespace BIWEB\LaravelOpenapi\Tests;

use BIWEB\LaravelOpenapi\LaravelOpenapiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelOpenapiServiceProvider::class,
        ];
    }
}
