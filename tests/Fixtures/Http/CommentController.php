<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;

class CommentController
{
    #[Operation(operationId: 'listComments')]
    #[Parameter(name: 'comment', type: 'integer')]
    public function index(): void {}

    #[Operation(operationId: 'listArchives')]
    public function archives(): void {}
}
