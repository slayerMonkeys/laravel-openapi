<?php

namespace BIWEB\LaravelOpenapi\Commands;

use Illuminate\Console\Command;

class LaravelOpenapiCommand extends Command
{
    public $signature = 'laravel-openapi';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
