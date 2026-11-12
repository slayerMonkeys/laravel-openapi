<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MarkServed
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $response->headers->set('X-Openapi', 'served');

        return $response;
    }
}
