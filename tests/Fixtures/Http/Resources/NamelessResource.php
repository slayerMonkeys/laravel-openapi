<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use Illuminate\Http\Resources\Json\JsonResource;

#[Schema]
#[Property(type: 'integer')]
final class NamelessResource extends JsonResource {}
