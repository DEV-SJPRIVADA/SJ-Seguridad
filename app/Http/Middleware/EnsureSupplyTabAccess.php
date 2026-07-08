<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupplyTabAccess
{
    public function handle(Request $request, Closure $next, string $tab): Response
    {
        $user = $request->user();
        $module = (string) $request->route('module');

        abort_unless($user && $user->canAccessSupplyTab($module, $tab), 403);

        return $next($request);
    }
}
