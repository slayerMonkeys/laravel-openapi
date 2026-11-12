<?php

use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\AccountController;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\ResponseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/openapi-'.uniqid();
    config()->set('openapi.documents.default.output', "{$this->directory}/default.json");
    Route::get('api/users', [AccountController::class, 'index']);
    Route::delete('api/users/{user}', [AccountController::class, 'destroy']);
});

afterEach(fn () => File::deleteDirectory($this->directory));

it('writes the document output as JSON, creating its directory', function () {
    $this->artisan('openapi:generate')->assertSuccessful();

    expect(File::get("{$this->directory}/default.json"))->toBe(json_encode(LaravelOpenapi::generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
});

it('writes YAML equal to the JSON, from the extension or --format', function (string $file, array $options) {
    $this->artisan('openapi:generate', ['--output' => "{$this->directory}/{$file}", ...$options])->assertSuccessful();

    $json = json_decode(json_encode(LaravelOpenapi::generate()));

    expect(Yaml::parseFile("{$this->directory}/{$file}", Yaml::PARSE_OBJECT_FOR_MAP))->toEqual($json);
})->with([
    'extension' => ['openapi.yaml', []],
    '--format' => ['openapi.txt', ['--format' => 'yaml']],
]);

it('writes one file per document with --all', function () {
    config()->set('openapi.documents.internal', [...config('openapi.documents.default'), 'output' => "{$this->directory}/internal.yml"]);

    $this->artisan('openapi:generate', ['--all' => true])->assertSuccessful();

    expect(File::exists("{$this->directory}/default.json"))->toBeTrue()
        ->and(Yaml::parseFile("{$this->directory}/internal.yml"))->toHaveKey('openapi');
});

it('fails with a message naming the cause', function (Closure $setup, array $arguments, string $message) {
    $setup();

    $this->artisan('openapi:generate', $arguments)->expectsOutputToContain($message)->assertFailed();
})->with([
    'unknown document' => [fn () => null, ['document' => 'missing'], 'Unknown OpenAPI document [missing].'],
    'invalid annotation' => [fn () => Route::get('api/invalid', [ResponseController::class, 'definitionWithArguments']), [], 'forbids the other arguments of #[Response]'],
    'unknown format' => [fn () => null, ['--format' => 'xml'], 'Unknown format [xml], expected json or yaml.'],
    'no output' => [fn () => config()->set('openapi.documents.default.output', null), [], 'Document [default] has no output file'],
    '--all with --output' => [fn () => null, ['--all' => true, '--output' => 'x.json'], 'The --all option cannot be combined'],
]);
