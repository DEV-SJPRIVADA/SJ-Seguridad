<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ReportesNovedades\LookupFichaRequest;
use App\Services\Access\ReportesNovedadesAccessService;
use App\Services\GestionHumana\ReportesNovedadesFichaLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportesNovedadesController extends Controller
{
    public function __construct(
        private readonly ReportesNovedadesAccessService $access,
        private readonly ReportesNovedadesFichaLookupService $fichaLookup,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->access->canViewReportesNovedadesBoard(auth()->user()), 403);

        $firstTab = $this->access->visibleTabsFor(auth()->user())[0] ?? null;

        abort_unless($firstTab !== null, 403);

        return redirect()->route(
            'gestion-humana.reportes-novedades.'.$firstTab,
            $request->query()
        );
    }

    public function lookup(LookupFichaRequest $request): JsonResponse
    {
        $result = $this->fichaLookup->lookupByDocument(
            (string) $request->validated('document_number')
        );

        return response()->json($result);
    }
}
