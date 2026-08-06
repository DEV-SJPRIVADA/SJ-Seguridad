<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Services\Compras\ComprasDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComprasDashboardController extends Controller
{
    public function __invoke(Request $request, ComprasDashboardService $dashboard): View
    {
        $this->authorizeView();

        $filters = [
            'year' => (int) $request->input('year', now()->year),
            'month' => $request->input('month') !== null && $request->input('month') !== ''
                ? (int) $request->input('month')
                : null,
            'area_key' => trim($request->string('area_key')->toString()),
            'tipo' => trim($request->string('tipo')->toString()),
        ];

        if (! in_array($filters['tipo'], ['', 'purchase', 'supply'], true)) {
            $filters['tipo'] = '';
        }

        if ($filters['area_key'] !== '' && ! array_key_exists($filters['area_key'], config('access.areas', []))) {
            $filters['area_key'] = '';
        }

        $payload = $dashboard->build($filters);

        return view('areas.compras.dashboard', $payload);
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->can('view.board.compras.dashboard')
            || auth()->user()?->can('purchase.tab.processing')
            || auth()->user()?->can('view.area.compras')
            || auth()->user()?->can('manage.area.compras')
            || auth()->user()?->can('manage.users'),
            403
        );
    }
}
