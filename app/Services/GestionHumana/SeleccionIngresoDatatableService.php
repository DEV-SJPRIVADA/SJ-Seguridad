<?php

namespace App\Services\GestionHumana;

use App\Models\SeleccionIngreso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SeleccionIngresoDatatableService
{
    public function __construct(
        private readonly SeleccionIngresoService $listService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->listService->filteredQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, SeleccionIngreso> $rows */
        $rows = $query->with(['commercialClient:id,name', 'uniform:id,name', 'responsable:id,name'])->get();

        $data = $rows
            ->map(fn (SeleccionIngreso $ingreso): array => $this->formatRow($ingreso, $canEdit))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @param  Builder<SeleccionIngreso>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('document_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('city_name', 'like', $like)
                ->orWhere('position_name', 'like', $like)
                ->orWhere('blood_type_name', 'like', $like)
                ->orWhere('reemplaza_a', 'like', $like)
                ->orWhere('referido', 'like', $like)
                ->orWhere('jefe_ope', 'like', $like)
                ->orWhereHas('commercialClient', fn (Builder $client) => $client->where('name', 'like', $like))
                ->orWhereHas('responsable', fn (Builder $user) => $user->where('name', 'like', $like));
        });
    }

    /**
     * @param  Builder<SeleccionIngreso>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_ingreso')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            4 => $query->orderBy('city_name', $direction),
            5 => $query->orderBy('position_name', $direction),
            10 => $query->orderBy('fecha_ingreso', $direction)->orderBy('id', $direction),
            11 => $query->orderBy('blood_type_name', $direction),
            13 => $query->orderBy('referido', $direction),
            default => $query->orderByDesc('fecha_ingreso')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(SeleccionIngreso $ingreso, bool $canEdit): array
    {
        $cells = [
            e((string) $ingreso->document_number),
            e((string) $ingreso->full_name),
            e((string) $ingreso->email),
            e((string) $ingreso->phone),
            e((string) $ingreso->city_name),
            e((string) $ingreso->position_name),
            e((string) ($ingreso->commercialClient?->name ?: '—')),
            e((string) $ingreso->shirt_size),
            e((string) $ingreso->pants_size),
            e((string) $ingreso->shoes_size),
            e(optional($ingreso->fecha_ingreso)?->format('Y-m-d') ?: '—'),
            e((string) $ingreso->blood_type_name),
            e((string) ($ingreso->responsable?->name ?: '—')),
            e((string) ($ingreso->referido !== '' ? $ingreso->referido : '—')),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($ingreso);
        }

        return $cells;
    }

    private function formatActionsCell(SeleccionIngreso $ingreso): string
    {
        $editPayload = e(json_encode([
            'id' => $ingreso->id,
            'document_number' => $ingreso->document_number,
            'full_name' => $ingreso->full_name,
            'email' => $ingreso->email,
            'phone' => $ingreso->phone,
            'city_code' => $ingreso->city_code,
            'position_code' => $ingreso->position_code,
            'commercial_client_id' => (string) $ingreso->commercial_client_id,
            'shirt_size' => $ingreso->shirt_size,
            'pants_size' => $ingreso->pants_size,
            'shoes_size' => $ingreso->shoes_size,
            'requisition_uniform_id' => (string) $ingreso->requisition_uniform_id,
            'fecha_ingreso' => optional($ingreso->fecha_ingreso)?->format('Y-m-d'),
            'blood_type_code' => $ingreso->blood_type_code,
            'reemplaza_a' => $ingreso->reemplaza_a,
            'responsable_user_id' => (string) $ingreso->responsable_user_id,
            'referido' => $ingreso->referido,
            'jefe_ope' => $ingreso->jefe_ope,
            'update_url' => route('gestion-humana.seleccion.ingresos.update', $ingreso),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="seleccion-ingresos-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-seleccion-ingreso-edit" '.
            'data-ingreso-edit="%s" title="Editar" aria-label="Editar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'.
            '</button>'.
            '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
            '%s<input type="hidden" name="_method" value="DELETE">'.
            '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
            'title="Eliminar" aria-label="Eliminar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'.
            '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
            '</button></form></div>',
            $editPayload,
            e(route('gestion-humana.seleccion.ingresos.destroy', $ingreso)),
            e(json_encode('¿Eliminar este registro de ingreso?', JSON_UNESCAPED_UNICODE)),
            $this->csrfField(),
        );
    }

    private function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s" autocomplete="off">',
            e(csrf_token()),
        );
    }
}
