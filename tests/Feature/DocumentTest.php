<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use PHPUnit\Framework\ExpectationFailedException;

it('generates a valid empty document', function (string $version) {
    config()->set('openapi.documents.default.openapi', $version);

    $document = LaravelOpenapi::generate();

    expect($document['openapi'])->toBe($version)
        ->and($document['info'])->toBe(['title' => 'API', 'version' => '1.0.0'])
        ->and(json_encode($document['paths']))->toBe('{}')
        ->and($document)->toBeValidOpenapi();
})->with(['3.0.3', '3.0.0', '3.1.0', '3.1.1']);

it('generates a named document', function () {
    config()->set('openapi.documents.internal', [
        'openapi' => '3.1.0',
        'info' => ['title' => 'Internal', 'version' => '2.0.0'],
    ]);

    expect(LaravelOpenapi::generate('internal')['info']['title'])->toBe('Internal');
});

it('rejects an invalid document with both validators', function (string $version) {
    expect(fn () => expect(['openapi' => $version, 'paths' => new stdClass])->toBeValidOpenapi())
        ->toThrow(ExpectationFailedException::class);
})->with(['3.0.3', '3.1.0']);

it('rejects an unsupported openapi version', function (string $version) {
    config()->set('openapi.documents.default.openapi', $version);

    LaravelOpenapi::generate();
})->with(['2.0', '3.2.0', '3.1', 'foo'])->throws(InvalidOpenapiDefinition::class, 'Unsupported OpenAPI version');

it('rejects an unknown document', function () {
    LaravelOpenapi::generate('missing');
})->throws(InvalidOpenapiDefinition::class, 'Unknown OpenAPI document [missing]');
