<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\PostTooLargeException;

class CustomPostSize
{
    public function handle($request, Closure $next)
    {
        $contentLength = $request->server('CONTENT_LENGTH');

        // Set a very high limit (1GB in this example)
        $max = 1024 * 1024 * 1024;

        if ($contentLength > $max) {
            throw new PostTooLargeException;
        }

        return $next($request);
    }
}
