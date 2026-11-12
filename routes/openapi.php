<?php

use BIWEB\LaravelOpenapi\Http\OpenapiController;
use Illuminate\Support\Facades\Route;

// read at boot, then cached by route:cache: the document is a route default, not a closure variable
foreach (config('openapi.documents', []) as $document => $config) {
    if ($config['route']['enabled'] ?? false) {
        Route::get($config['route']['uri'], OpenapiController::class)
            ->middleware($config['route']['middleware'] ?? [])
            ->defaults('document', $document);
    }
}
