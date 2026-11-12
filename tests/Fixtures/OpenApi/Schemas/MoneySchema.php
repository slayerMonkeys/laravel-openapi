<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas;

use BIWEB\LaravelOpenapi\Definitions\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use Illuminate\Contracts\Config\Repository;

final class MoneySchema extends Schema
{
    public function __construct(private readonly Repository $config) {}

    public function build(): Objects\Schema
    {
        return Objects\Schema::object()
            ->description('Amount in '.$this->config->get('app.currency'))
            ->properties(Objects\Schema::integer('amount'));
    }
}
