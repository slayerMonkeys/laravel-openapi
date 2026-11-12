<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use Illuminate\Http\Resources\Json\JsonResource;

#[Schema]
#[Property('id', type: 'integer')]
final class PlainResource extends JsonResource
{
    public static $wrap = null;
}
