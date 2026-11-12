<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\TaskData;

class InternalController
{
    #[Operation(summary: 'List tasks')]
    #[Response(200, 'Tasks', schema: TaskData::class, isList: true)]
    public function tasks(): void {}

    #[Operation(summary: 'List audits', documents: ['internal'])]
    #[Response(200, 'Audits')]
    public function audits(): void {}
}
