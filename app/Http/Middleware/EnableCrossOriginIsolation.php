<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableCrossOriginIsolation
{
    /**
     * Add the HTTP headers required to enable SharedArrayBuffer and threads
     * in supporting browsers (Cross-Origin Isolation).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        return $response;
    }
}
