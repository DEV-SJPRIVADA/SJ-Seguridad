<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\PersonalRequisitionFichaEntryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\PromoteFichaEntryRequest;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FichaEmpleadosController extends Controller
{
    use HasFichaEmpleadosTabs;

    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);

        $entries = $this->entryListQuery($q, $estado)->get();

        return view('areas.gestion_humana.ficha-empleados.employees.index', [
            'entries' => $entries,
            'filters' => ['q' => $q, 'estado' => $estado],
            'estadoLabels' => self::estadoFilterLabels(),
            'canManage' => $this->canManage(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);

        $entries = $this->entryListQuery($q, $estado)
            ->with(PersonalRequisitionFichaEntryExport::relationNames())
            ->get();

        $title = self::estadoFilterLabels()[$estado] ?? 'Empleados';

        return PersonalRequisitionFichaEntryExport::download(
            $entries,
            'ficha_empleados_'.now()->format('Y-m-d').'.xlsx',
            'Ficha empleados — '.$title
        );
    }

    public function promote(PromoteFichaEntryRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        if ($fichaEntry->moved_to_ficha_at !== null) {
            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->with('status', 'Ese registro ya estaba en Ficha empleados.');
        }

        $fichaEntry->update([
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.index')
            ->with('status', 'Registro agregado a Ficha empleados.');
    }

    /**
     * @return array<string, string>
     */
    private static function estadoFilterLabels(): array
    {
        return [
            'pendientes' => 'Pendientes',
            'en_ficha' => 'En ficha',
        ];
    }

    private function resolveEstadoFilter(Request $request): string
    {
        $estado = trim($request->string('estado')->toString());

        return array_key_exists($estado, self::estadoFilterLabels()) ? $estado : 'pendientes';
    }

    /**
     * @return Builder<PersonalRequisitionFichaEntry>
     */
    private function entryListQuery(string $q, string $estado): Builder
    {
        return PersonalRequisitionFichaEntry::query()
            ->with(['requisition.position', 'requisition.client', 'requisition.city', 'movedBy'])
            ->when(
                $estado === 'en_ficha',
                fn (Builder $query) => $query->inFicha(),
                fn (Builder $query) => $query->pending()
            )
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('hired_document', 'like', "%{$q}%")
                        ->orWhere('hired_full_name', 'like', "%{$q}%")
                        ->orWhereHas('requisition', fn (Builder $r) => $r->where('code', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('created_at');
    }

    private function authorizeView(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canView(auth()->user()), 403);
    }

    private function canManage(): bool
    {
        return $this->fichaEmpleadosAccess->canManage(auth()->user());
    }
}
