<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;

class Not extends BaseObject implements SchemaContract
{
    protected ?SchemaContract $schema = null;

    public function schema(?SchemaContract $schema): static
    {
        return $this->with('schema', $schema);
    }

    protected function fields(string $version): array
    {
        return ['not' => $this->schema];
    }
}
