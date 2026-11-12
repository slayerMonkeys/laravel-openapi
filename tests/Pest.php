<?php

use BIWEB\LaravelOpenapi\Tests\TestCase;
use JsonSchema\Validator as DraftFourValidator;
use Opis\JsonSchema\CompliantValidator as OpisValidator;
use Opis\JsonSchema\Errors\ErrorFormatter;

uses(TestCase::class)->in(__DIR__);

// OpenAPI 3.0 meta-schema is draft-04 (justinrainbow), 3.1 is draft 2020-12 (opis): no single validator covers both.
expect()->extend('toBeValidOpenapi', function () {
    $data = json_decode(json_encode($this->value, JSON_THROW_ON_ERROR), flags: JSON_THROW_ON_ERROR);
    $schemaPath = __DIR__.'/Fixtures/schemas/oas-'.substr((string) ($data->openapi ?? '3.1'), 0, 3).'.json';
    // opis resolves $dynamicRef against the resource root, not $defs/schema: a plain $ref is equivalent without an extending dialect
    $schema = json_decode(str_replace('"$dynamicRef": "#meta"', '"$ref": "#/$defs/schema"', file_get_contents($schemaPath)), flags: JSON_THROW_ON_ERROR);

    if (str_starts_with((string) ($data->openapi ?? ''), '3.0')) {
        $validator = new DraftFourValidator;
        $validator->validate($data, $schema);
        $errors = array_map(fn (array $error) => "[{$error['property']}] {$error['message']}", $validator->getErrors());
    } else {
        $result = (new OpisValidator)->validate($data, $schema);
        $errors = $result->isValid() ? [] : (new ErrorFormatter)->formatFlat($result->error());
    }

    expect($errors)->toBe([], 'Document OpenAPI invalide');

    return $this;
});
