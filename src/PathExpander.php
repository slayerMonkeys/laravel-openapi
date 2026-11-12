<?php

namespace BIWEB\LaravelOpenapi;

class PathExpander
{
    /**
     * OpenAPI has no optional path segments, so a URI with n {x?} gives n + 1 paths, from none of them to all.
     *
     * @return array<string, list<string>> each path, with the optional parameters it contains
     */
    public static function expand(string $uri): array
    {
        $path = '';
        $optional = [];
        $paths = [];

        foreach (explode('/', trim($uri, '/')) as $segment) {
            if (preg_match('/^\{(\w+)\?\}$/', $segment, $match)) {
                $paths[$path === '' ? '/' : $path] = $optional;
                $optional[] = $match[1];
                $segment = "{{$match[1]}}";
            }

            $path .= '/'.$segment;
        }

        $paths[$path] = $optional;

        return $paths;
    }
}
