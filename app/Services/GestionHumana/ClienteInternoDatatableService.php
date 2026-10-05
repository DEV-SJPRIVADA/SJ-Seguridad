<?php

namespace App\Services\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ClienteInternoDatatableService
{
    /**
     * @var array<int, string>
     */
    public const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * @param  array{
     *     anio?: int|string|null,
     *     mes?: int|string|null,
     *     estado_id?: int|string|null,
     *     cedula?: string|null,
     *     nombre?: string|null,
     * }  $filters
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = ClienteInternoSolicitud::query()
            ->with(['tipoSolicitud:id,name,code', 'estado:id,name,code']);

        if ($ordered) {
            $query->orderByDesc('fecha_solicitud')->orderByDesc('id');
        }

        $anio = $filters['anio'] ?? null;
        if ($anio !== null && $anio !== '') {
            $query->where('anio', (int) $anio);
        }

        $mes = $filters['mes'] ?? null;
        if ($mes !== null && $mes !== '') {
            $mesCandidate = (int) $mes;
            if ($mesCandidate >= 1 && $mesCandidate <= 12) {
                $query->where('mes', $mesCandidate);
            }
        }

        $estadoId = $filters['estado_id'] ?? null;
        if ($estadoId !== null && $estadoId !== '') {
            $query->where('estado_id', (int) $estadoId);
        }

        $cedula = trim((string) ($filters['cedula'] ?? ''));
        if ($cedula !== '') {
            $query->where('cedula', 'like', '%'.$cedula.'%');
        }

        $nombre = trim((string) ($filters['nombre'] ?? ''));
        if ($nombre !== '') {
            $query->where('nombre_apellidos', 'like', '%'.$nombre.'%');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = $this->filteredQuery($filters, ordered: false);
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

        /** @var Collection<int, ClienteInternoSolicitud> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (ClienteInternoSolicitud $solicitud): array => $this->formatRow($solicitud, $canEdit))
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
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     estados: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(): array
    {
        $anios = ClienteInternoSolicitud::query()
            ->select('anio')
            ->whereNotNull('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anioValue): array => [
                'value' => (string) $anioValue,
                'label' => (string) $anioValue,
            ])
            ->values()
            ->all();

        $meses = [];
        foreach (self::MESES as $value => $label) {
            $meses[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        $estados = ClienteInternoEstado::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ClienteInternoEstado $estado): array => [
                'value' => (string) $estado->id,
                'label' => (string) $estado->name,
            ])
            ->values()
            ->all();

        return [
            'anios' => $anios,
            'meses' => $meses,
            'estados' => $estados,
        ];
    }

    /**
     * @param  Builder<ClienteInternoSolicitud>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('cedula', 'like', $like)
                ->orWhere('nombre_apellidos', 'like', $like)
                ->orWhere('correo_electronico', 'like', $like)
                ->orWhere('novedad', 'like', $like)
                ->orWhereHas('tipoSolicitud', fn (Builder $tipo) => $tipo->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->orWhereHas('estado', fn (Builder $estado) => $estado->where('name', 'like', $like)->orWhere('code', 'like', $like));
        });
    }

    /**
     * @param  Builder<ClienteInternoSolicitud>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_solicitud')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('fecha_solicitud', $direction)->orderBy('id', $direction),
            1 => $query->orderBy('nombre_apellidos', $direction),
            2 => $query->orderBy('cedula', $direction),
            3 => $query->orderBy('correo_electronico', $direction),
            5 => $query->orderBy('fecha_respuesta', $direction),
            8 => $query->orderBy('dias_respuesta', $direction),
            default => $query->orderByDesc('fecha_solicitud')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(ClienteInternoSolicitud $solicitud, bool $canEdit): array
    {
        $novedad = (string) ($solicitud->novedad ?? '');
        $novedadShort = mb_strlen($novedad) > 80 ? mb_substr($novedad, 0, 77).'…' : $novedad;

        $cells = [
            e(optional($solicitud->fecha_solicitud)?->format('Y-m-d') ?: '—'),
            e((string) $solicitud->nombre_apellidos),
            e((string) $solicitud->cedula),
            e((string) ($solicitud->correo_electronico ?: '—')),
            e((string) ($solicitud->tipoSolicitud?->name ?: '—')),
            e(optional($solicitud->fecha_respuesta)?->format('Y-m-d') ?: '—'),
            e((string) ($solicitud->estado?->name ?: '—')),
            e($novedadShort !== '' ? $novedadShort : '—'),
            e($solicitud->dias_respuesta !== null ? (string) $solicitud->dias_respuesta : '—'),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($solicitud);
        }

        return $cells;
    }

    private function formatActionsCell(ClienteInternoSolicitud $solicitud): string
    {
        $editPayload = e(json_encode([
            'id' => $solicitud->id,
            'fecha_solicitud' => optional($solicitud->fecha_solicitud)?->format('Y-m-d'),
            'nombre_apellidos' => $solicitud->nombre_apellidos,
            'cedula' => $solicitud->cedula,
            'correo_electronico' => $solicitud->correo_electronico ?? '',
            'tipo_solicitud_id' => (string) $solicitud->tipo_solicitud_id,
            'fecha_respuesta' => optional($solicitud->fecha_respuesta)?->format('Y-m-d'),
            'estado_id' => $solicitud->estado_id !== null ? (string) $solicitud->estado_id : '',
            'novedad' => $solicitud->novedad ?? '',
            'dias_respuesta' => $solicitud->dias_respuesta !== null ? (string) $solicitud->dias_respuesta : '',
            'dias_respuesta_manual' => (bool) $solicitud->dias_respuesta_manual,
            'update_url' => route('gestion-humana.cliente-interno.solicitudes.update', $solicitud),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-cliente-interno-solicitud-edit" '.
            'data-solicitud-edit="%s" title="Editar" aria-label="Editar">'.
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
            e(route('gestion-humana.cliente-interno.solicitudes.destroy', $solicitud)),
            e(json_encode('¿Eliminar esta solicitud de forma definitiva?', JSON_UNESCAPED_UNICODE)),
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
