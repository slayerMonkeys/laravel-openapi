<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use stdClass;

class LaravelOpenapi
{
    /** @return array<string, mixed> */
    public function generate(?string $document = null): array
    {
        $document ??= config('openapi.default');
        $config = config("openapi.documents.{$document}");

        if (! is_array($config)) {
            throw new InvalidOpenapiDefinition("Unknown OpenAPI document [{$document}].");
        }

        $version = (string) ($config['openapi'] ?? '');

        if (! preg_match('/^3\.[01]\.\d+$/', $version)) {
            throw new InvalidOpenapiDefinition("Unsupported OpenAPI version [{$version}] in document [{$document}], expected 3.0.x or 3.1.x.");
        }

        return array_filter([
            'openapi' => $version,
            'info' => array_filter($config['info'] ?? [], fn ($value) => $value !== null),
            'servers' => $config['servers'] ?? [],
            // stdClass so an empty map encodes as {} instead of []
            'paths' => new stdClass,
        ]);
    }
}
