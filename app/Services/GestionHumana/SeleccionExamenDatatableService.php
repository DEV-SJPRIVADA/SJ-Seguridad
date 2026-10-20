<?php

namespace App\Services\GestionHumana;

use App\Models\SeleccionExamenOcupacional;
use App\Support\DisplayDate;
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
        // Estado se guarda como código (no_aprobado): buscar por texto «no aprobado» también.
        $estadoLike = '%'.str_replace(' ', '_', mb_strtolower($search)).'%';

        $query->where(function (Builder $inner) use ($like, $estadoLike): void {
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
                ->orWhere('estado', 'like', $estadoLike)
                ->orWhere('address', 'like', $like)
                ->orWhere('marital_status_name', 'like', $like)
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

        // Índices alineados con el <thead> de examenes.blade.php.
        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            2 => $query->orderBy('position_name', $direction),
            3 => $query->orderBy('servicio_sector', $direction),
            5 => $query->orderBy('city_name', $direction),
            6 => $query->orderBy('eps_name', $direction),
            7 => $query->orderBy('afp_name', $direction),
            8 => $query->orderBy('birth_date', $direction),
            11 => $query->orderBy('marital_status_name', $direction),
            12 => $query->orderBy('fecha_arl', $direction)->orderBy('id', $direction),
            13 => $query->orderBy('estado', $direction),
            14 => $query->orderBy('email', $direction),
            15 => $query->orderBy('solicitud_status_name', $direction),
            default => $query->orderByDesc('fecha_arl')->orderByDesc('id'),
        };
    }

    /**
     * Fila DataTables: todos los campos del formulario; Estado y Correo tras Fecha ARL.
     *
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
            e(DisplayDate::date($examen->birth_date)),
            e((string) ($examen->address ?: '—')),
            e((string) ($examen->phone ?: '—')),
            e((string) ($examen->marital_status_name ?: '—')),
            e(DisplayDate::date($examen->fecha_arl)),
            $this->formatEstadoBadge($examen),
            e((string) ($examen->email ?: '—')),
            e((string) $examen->solicitud_status_name),
            e((string) ($examen->responsable?->name ?: '—')),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($examen);
        }

        return $cells;
    }

    /**
     * Pill de estado con color por estado (misma familia visual que Requisiciones).
     */
    private function formatEstadoBadge(SeleccionExamenOcupacional $examen): string
    {
        $estado = (string) $examen->estado;

        return sprintf(
            '<span class="status-pill status-pill--examen-%s">%s</span>',
            e(str_replace('_', '-', $estado)),
            e($examen->estadoLabel()),
        );
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
            'estado' => $examen->estado,
            'solicitud_status_code' => $examen->solicitud_status_code,
            'responsable_user_id' => (string) $examen->responsable_user_id,
            'update_url' => route('gestion-humana.seleccion.examenes.update', $examen),
        ], JSON_UNESCAPED_UNICODE));

        $historialUrl = e(route('gestion-humana.seleccion.examenes.historial', ['id' => $examen->id]));

        return sprintf(
            '<div class="cursos-catalogo-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn js-seleccion-examen-historial" '.
            'data-historial-url="%s" title="Historial de cambios" aria-label="Historial de cambios">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>'.
            '</button>'.
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
            $historialUrl,
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
