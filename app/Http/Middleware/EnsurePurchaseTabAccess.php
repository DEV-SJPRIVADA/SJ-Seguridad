<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePurchaseTabAccess
{
    public function handle(Request $request, Closure $next, string $tab): Response
    {
        $user = $request->user();

        if ($user?->hasRole('super-admin') || $user?->can('manage.users')) {
            return $next($request);
        }

        $module = (string) $request->route('module');

        abort_unless($user && $user->canAccessPurchaseTab($module, $tab), 403);

        return $next($request);
    }
}
