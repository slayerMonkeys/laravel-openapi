<?php

use BIWEB\LaravelOpenapi\Components;
use BIWEB\LaravelOpenapi\Definitions;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('OpenApi'));
    File::deleteDirectory(base_path('stubs'));
});

/** @return array{class-string, array<string, mixed>} the generated class and what Components resolves it to */
function makeDefinition(string $command, string $name, string $directory, string $suffix, array $options = []): array
{
    test()->artisan($command, ['name' => $name, ...$options])->assertSuccessful();

    $class = "App\\OpenApi\\{$directory}\\{$name}{$suffix}";
    require app_path("OpenApi/{$directory}/{$name}{$suffix}.php");

    $resolved = match ($suffix) {
        'Schema' => (new Components)->schema($class)->toArray('3.1.0'),
        'Parameters' => array_map(fn ($parameter) => $parameter->toArray('3.1.0'), array_values((new Components)->parameters($class))),
        'RequestBody' => (new Components)->requestBody($class)->toArray('3.1.0'),
        'Response' => (new Components)->response($class)->toArray('3.1.0'),
    };

    return [$class, $resolved];
}

dataset('definitions', [
    'schema' => ['make:openapi-schema', 'Schemas', 'Schema', Definitions\Schema::class, '#/components/schemas/SharedSchema'],
    'parameters' => ['make:openapi-parameters', 'Parameters', 'Parameters', Definitions\Parameters::class, '#/components/parameters/page'],
    'request body' => ['make:openapi-request-body', 'RequestBodies', 'RequestBody', Definitions\RequestBody::class, '#/components/requestBodies/SharedRequestBody'],
    'response' => ['make:openapi-response', 'Responses', 'Response', Definitions\Response::class, '#/components/responses/SharedResponse'],
]);

it('generates a definition class that Components inlines', function (string $command, string $directory, string $suffix, string $base) {
    [$class, $resolved] = makeDefinition($command, 'Inline', $directory, $suffix);

    expect(is_subclass_of($class, $base))->toBeTrue()
        ->and(is_subclass_of($class, Definitions\Reusable::class))->toBeFalse()
        ->and(json_encode($resolved))->not->toContain('$ref');
})->with('definitions');

it('generates a reusable definition class with --reusable, emitted as a $ref', function (string $command, string $directory, string $suffix, string $base, string $pointer) {
    [$class, $resolved] = makeDefinition($command, 'Shared', $directory, $suffix, ['--reusable' => true]);

    expect(is_subclass_of($class, Definitions\Reusable::class))->toBeTrue()
        ->and($suffix === 'Parameters' ? $resolved[0] : $resolved)->toBe(['$ref' => $pointer]);
})->with('definitions');

it('does not repeat the suffix already in the name', function () {
    $this->artisan('make:openapi-response', ['name' => 'UserCreatedResponse'])->assertSuccessful();

    expect(File::exists(app_path('OpenApi/Responses/UserCreatedResponse.php')))->toBeTrue();
});

it('uses the stub published in the application', function () {
    $this->artisan('vendor:publish', ['--tag' => 'openapi-stubs'])->assertSuccessful();
    File::put(base_path('stubs/openapi.schema.stub'), File::get(base_path('stubs/openapi.schema.stub'))."// customized\n");

    $this->artisan('make:openapi-schema', ['name' => 'Custom'])->assertSuccessful();

    expect(File::get(app_path('OpenApi/Schemas/CustomSchema.php')))->toEndWith("// customized\n");
});
