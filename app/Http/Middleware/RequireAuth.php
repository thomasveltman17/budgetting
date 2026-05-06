<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('login', 'logout')) {
            return $next($request);
        }

        if (! session('authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
