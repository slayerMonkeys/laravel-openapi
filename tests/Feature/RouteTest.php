<?php

use BIWEB\LaravelOpenapi\LaravelOpenapiServiceProvider;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Middleware\MarkServed;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/openapi-'.uniqid();
    File::ensureDirectoryExists($this->directory);
});

afterEach(fn () => File::deleteDirectory($this->directory));

// the routes file is read when the provider boots, as in an application
function serveDocument(string $output, bool $enabled = true, array $middleware = []): void
{
    config()->set('openapi.documents.default.output', $output);
    config()->set('openapi.documents.default.route', ['enabled' => $enabled, 'uri' => 'openapi/default', 'middleware' => $middleware]);
    app()->register(LaravelOpenapiServiceProvider::class, force: true);
}

it('does not serve a document whose route is disabled', function () {
    File::put("{$this->directory}/openapi.json", '{}');
    serveDocument("{$this->directory}/openapi.json", enabled: false);

    $this->get('openapi/default')->assertNotFound();
});

it('answers 404 until the file is generated', function () {
    serveDocument("{$this->directory}/openapi.json");

    $this->get('openapi/default')->assertNotFound();
});

it('serves the file as is, typed by its extension', function (string $file, string $type) {
    File::put("{$this->directory}/{$file}", 'content');
    serveDocument("{$this->directory}/{$file}");

    $response = $this->get('openapi/default')->assertOk()->assertHeader('Content-Type', $type);

    expect($response->baseResponse->getFile()->getContent())->toBe('content');
})->with([
    'json' => ['openapi.json', 'application/json'],
    'yaml' => ['openapi.yaml', 'application/yaml'],
    'yml' => ['openapi.yml', 'application/yaml'],
]);

it('applies the configured middleware', function () {
    File::put("{$this->directory}/openapi.json", '{}');
    serveDocument("{$this->directory}/openapi.json", middleware: [MarkServed::class]);

    $this->get('openapi/default')->assertOk()->assertHeader('X-Openapi', 'served');
});

it('still serves the document from cached routes', function () {
    File::put("{$this->directory}/openapi.json", '{}');
    serveDocument("{$this->directory}/openapi.json");

    // what route:cache does: serialize each route, compile the collection, then load it back
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        $route->prepareForSerialization();
    }

    app('router')->setCompiledRoutes($routes->compile());

    $this->get('openapi/default')->assertOk()->assertHeader('Content-Type', 'application/json');
});
