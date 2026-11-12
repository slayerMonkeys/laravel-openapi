<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema(name: 'Renamed', description: 'A renamed schema.')]
final class RenamedData {}
