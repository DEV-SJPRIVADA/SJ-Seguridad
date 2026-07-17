<?php

namespace App\Http\Middleware;

use App\Services\Access\RequisitionAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequisitionTabAccess
{
    public function __construct(
        private readonly RequisitionAccessService $requisitionAccess,
    ) {}

    public function handle(Request $request, Closure $next, string $tab): Response
    {
        $user = $request->user();
        $module = (string) $request->route('module');

        abort_unless($user && $this->requisitionAccess->canAccessTab($user, $module, $tab), 403);

        return $next($request);
    }
}
