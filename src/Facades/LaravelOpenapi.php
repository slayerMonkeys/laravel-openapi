<?php

namespace BIWEB\LaravelOpenapi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> generate(?string $document = null)
 *
 * @see \BIWEB\LaravelOpenapi\LaravelOpenapi
 */
class LaravelOpenapi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BIWEB\LaravelOpenapi\LaravelOpenapi::class;
    }
}
