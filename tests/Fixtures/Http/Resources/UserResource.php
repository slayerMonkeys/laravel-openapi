<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

#[Schema]
#[Property('id', type: 'integer')]
#[Property('email', type: 'string', format: 'email', description: 'Login email')]
#[Property('nickname', type: 'string', nullable: true)]
#[Property('avatar', type: 'string', required: false)]
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'nickname' => $this->nickname, 'avatar' => $this->avatar];
    }
}
