<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized. You do not have access to this area.');
        }

        return $next($request);
    }
}
