<?php

namespace App\Services\PurchaseRequests\Providers;

use App\Models\PurchaseProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listado server-side del catálogo de proveedores.
 *
 * Default operativo: solo registros `es_proveedor = true` y `estado = activo`.
 * El filtro de estado permite ver inactivos o todos.
 */
final class PurchaseProviderDatatableService
{
    public const ESTADO_TODOS = 'todos';

    /**
     * @var array<int, string>
     */
    private const ORDERABLE_COLUMNS = [
        0 => 'identificacion',
        1 => 'nombre',
        2 => 'telefono',
        3 => 'correo',
        4 => 'contacto',
        5 => 'ciudad',
        6 => 'dias_pago',
        7 => 'estado',
    ];

    /**
     * @return array{q: string, estado: string, solo_proveedores: bool}
     */
    public function filtersFromRequest(Request $request): array
    {
        return $this->filtersFromInput($request->all());
    }

    /**
     * Lee solo el query string (para conservar filtros tras guardar, sin
     * confundirlos con campos del formulario como `estado`).
     *
     * @return array{q: string, estado: string, solo_proveedores: bool}
     */
    public function filtersFromQuery(Request $request): array
    {
        return $this->filtersFromInput($request->query());
    }

    /**
     * @param  array{q: string, estado: string, solo_proveedores: bool}  $filters
     * @return array<string, string>
     */
    public function filtersToQuery(array $filters): array
    {
        $query = [];

        if ($filters['q'] !== '') {
            $query['q'] = $filters['q'];
        }

        if ($filters['estado'] !== PurchaseProvider::ESTADO_ACTIVO) {
            $query['estado'] = $filters['estado'];
        }

        if (! $filters['solo_proveedores']) {
            $query['solo_proveedores'] = '0';
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{q: string, estado: string, solo_proveedores: bool}
     */
    private function filtersFromInput(array $input): array
    {
        $estado = (string) ($input['estado'] ?? PurchaseProvider::ESTADO_ACTIVO);

        if (! in_array($estado, [
            PurchaseProvider::ESTADO_ACTIVO,
            PurchaseProvider::ESTADO_INACTIVO,
            self::ESTADO_TODOS,
        ], true)) {
            $estado = PurchaseProvider::ESTADO_ACTIVO;
        }

        return [
            'q' => trim((string) ($input['q'] ?? '')),
            'estado' => $estado,
            'solo_proveedores' => filter_var(
                $input['solo_proveedores'] ?? true,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ) ?? true,
        ];
    }

    /**
     * @param  array{q: string, estado: string, solo_proveedores: bool}  $filters
     * @return Builder<PurchaseProvider>
     */
    public function filteredQuery(array $filters): Builder
    {
        return PurchaseProvider::query()
            ->when($filters['solo_proveedores'], fn (Builder $query): Builder => $query->where('es_proveedor', true))
            ->when(
                $filters['estado'] !== self::ESTADO_TODOS,
                fn (Builder $query): Builder => $query->where('estado', $filters['estado']),
            )
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('identificacion', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('correo', 'like', $term)
                        ->orWhere('contacto', 'like', $term)
                        ->orWhere('ciudad', 'like', $term);
                });
            });
    }

    /**
     * @param  array{q: string, estado: string, solo_proveedores: bool}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->filteredQuery($filters);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyGlobalSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        $rows = $query->get()
            ->map(fn (PurchaseProvider $provider): array => $this->formatRow($provider, $canEdit))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * @param  Builder<PurchaseProvider>  $query
     */
    private function applyGlobalSearch(Builder $query, Request $request): void
    {
        $search = trim((string) data_get($request->all(), 'search.value', ''));

        if ($search === '') {
            return;
        }

        $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('identificacion', 'like', $term)
                ->orWhere('nombre', 'like', $term)
                ->orWhere('correo', 'like', $term)
                ->orWhere('contacto', 'like', $term)
                ->orWhere('ciudad', 'like', $term);
        });
    }

    /**
     * @param  Builder<PurchaseProvider>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        $columnIndex = (int) data_get($request->all(), 'order.0.column', 1);
        $direction = strtolower((string) data_get($request->all(), 'order.0.dir', 'asc'));
        $column = self::ORDERABLE_COLUMNS[$columnIndex] ?? 'nombre';

        $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
    }

    /**
     * @return array<int, string>
     */
    private function formatRow(PurchaseProvider $provider, bool $canEdit): array
    {
        return [
            e((string) $provider->identificacion),
            e((string) $provider->nombre),
            e((string) ($provider->telefono ?? '—')),
            e((string) ($provider->correo ?? '—')),
            e((string) ($provider->contacto ?? '—')),
            e((string) ($provider->ciudad ?? '—')),
            $provider->dias_pago !== null ? (string) (int) $provider->dias_pago : '—',
            $this->estadoPill($provider),
            $canEdit
                ? $this->editAction($provider)
                : '<span class="text-muted">—</span>',
        ];
    }

    private function estadoPill(PurchaseProvider $provider): string
    {
        $activo = $provider->isActivo();

        return sprintf(
            '<span class="status-pill %s">%s</span>',
            $activo ? 'status-pill--success' : 'status-pill--muted',
            e((string) (PurchaseProvider::estadoLabels()[$provider->estado] ?? $provider->estado)),
        );
    }

    private function editAction(PurchaseProvider $provider): string
    {
        $returnQuery = $this->filtersToQuery($this->filtersFromQuery(request()));

        return sprintf(
            '<a href="%s" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" '.
            'title="Editar proveedor" aria-label="Editar proveedor">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'.
            '</a>',
            e(route('purchase-requests.providers.edit', array_merge([
                'module' => request()->route('module'),
                'purchase_provider' => $provider,
            ], $returnQuery))),
        );
    }
}
