<?php

namespace BIWEB\LaravelOpenapi\Http;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Serves the output file written by openapi:generate, as is. */
class OpenapiController
{
    public function __invoke(string $document): BinaryFileResponse
    {
        $path = config("openapi.documents.{$document}.output");

        abort_unless(is_string($path) && is_file($path), 404);

        $type = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['yaml', 'yml'], true) ? 'application/yaml' : 'application/json';

        return response()->file($path, ['Content-Type' => $type]);
    }
}
