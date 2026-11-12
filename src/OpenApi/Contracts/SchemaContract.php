<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Contracts;

/** A schema, or a composition of schemas (allOf, anyOf, oneOf, not). */
interface SchemaContract
{
    public function getObjectId(): ?string;
}
