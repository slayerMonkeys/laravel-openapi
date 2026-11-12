<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;

#[Operation(summary: 'Ambiguous')]
class AmbiguousAction
{
    #[Parameter(name: 'user', type: 'integer')]
    public function __invoke(): void {}
}
