<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIndicadorAccess
{
    public function handle(Request $request, Closure $next, string $tab): Response
    {
        $user = $request->user();

        abort_unless($user && $user->canAccessIndicadorTab($tab), 403);

        return $next($request);
    }
}
