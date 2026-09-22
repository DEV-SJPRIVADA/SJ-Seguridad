<?php

namespace App\Services\GestionHumana;

use App\Models\SeleccionExamenOcupacional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SeleccionExamenDatatableService
{
    public function __construct(
        private readonly SeleccionExamenService $listService,
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

        /** @var Collection<int, SeleccionExamenOcupacional> $rows */
        $rows = $query->with(['commercialClient:id,name', 'responsable:id,name'])->get();

        $data = $rows
            ->map(fn (SeleccionExamenOcupacional $examen): array => $this->formatRow($examen, $canEdit))
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
     * @param  Builder<SeleccionExamenOcupacional>  $query
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
                ->orWhere('servicio_sector', 'like', $like)
                ->orWhere('eps_name', 'like', $like)
                ->orWhere('afp_name', 'like', $like)
                ->orWhere('solicitud_status_name', 'like', $like)
                ->orWhereHas('commercialClient', fn (Builder $client) => $client->where('name', 'like', $like))
                ->orWhereHas('responsable', fn (Builder $user) => $user->where('name', 'like', $like));
        });
    }

    /**
     * @param  Builder<SeleccionExamenOcupacional>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_arl')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            2 => $query->orderBy('position_name', $direction),
            5 => $query->orderBy('city_name', $direction),
            8 => $query->orderBy('fecha_arl', $direction)->orderBy('id', $direction),
            9 => $query->orderBy('solicitud_status_name', $direction),
            default => $query->orderByDesc('fecha_arl')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(SeleccionExamenOcupacional $examen, bool $canEdit): array
    {
        $cells = [
            e((string) $examen->document_number),
            e((string) $examen->full_name),
            e((string) $examen->position_name),
            e((string) $examen->servicio_sector),
            e((string) ($examen->commercialClient?->name ?: '—')),
            e((string) $examen->city_name),
            e((string) $examen->eps_name),
            e((string) $examen->afp_name),
            e(optional($examen->fecha_arl)?->format('Y-m-d') ?: '—'),
            e((string) $examen->solicitud_status_name),
            e((string) ($examen->responsable?->name ?: '—')),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($examen);
        }

        return $cells;
    }

    private function formatActionsCell(SeleccionExamenOcupacional $examen): string
    {
        $editPayload = e(json_encode([
            'id' => $examen->id,
            'document_number' => $examen->document_number,
            'full_name' => $examen->full_name,
            'position_code' => $examen->position_code,
            'servicio_sector' => $examen->servicio_sector,
            'commercial_client_id' => (string) $examen->commercial_client_id,
            'eps_code' => $examen->eps_code,
            'afp_code' => $examen->afp_code,
            'birth_date' => optional($examen->birth_date)?->format('Y-m-d'),
            'city_code' => $examen->city_code,
            'address' => $examen->address,
            'email' => $examen->email,
            'phone' => $examen->phone,
            'marital_status_code' => $examen->marital_status_code,
            'fecha_arl' => optional($examen->fecha_arl)?->format('Y-m-d'),
            'solicitud_status_code' => $examen->solicitud_status_code,
            'responsable_user_id' => (string) $examen->responsable_user_id,
            'update_url' => route('gestion-humana.seleccion.examenes.update', $examen),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="seleccion-examenes-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-seleccion-examen-edit" '.
            'data-examen-edit="%s" title="Editar" aria-label="Editar">'.
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
            e(route('gestion-humana.seleccion.examenes.destroy', $examen)),
            e(json_encode('¿Eliminar este registro de examen ocupacional?', JSON_UNESCAPED_UNICODE)),
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
