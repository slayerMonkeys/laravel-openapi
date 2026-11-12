<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Parameters;

use BIWEB\LaravelOpenapi\Definitions\Parameters;
use BIWEB\LaravelOpenapi\Definitions\Reusable;
use BIWEB\LaravelOpenapi\OpenApi\Objects;

final class PaginationParameters extends Parameters implements Reusable
{
    public function build(): array
    {
        return [
            Objects\Parameter::query()->name('page')->schema(Objects\Schema::integer()),
            Objects\Parameter::query('PerPage')->name('per_page')->schema(Objects\Schema::integer()),
        ];
    }
}
