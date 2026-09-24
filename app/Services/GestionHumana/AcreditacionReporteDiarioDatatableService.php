<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionReporteDiarioFila;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AcreditacionReporteDiarioDatatableService
{
    public function __construct(
        private readonly AcreditacionReporteDiarioListService $listService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = (int) config('acreditaciones.limits.datatable_max_length', 100);

        $baseQuery = $this->listService->filteredQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min($maxLength, $length)));
        } else {
            $query->take($maxLength);
        }

        /** @var Collection<int, AcreditacionReporteDiarioFila> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (AcreditacionReporteDiarioFila $row): array => $this->formatRow($row))
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
     * @param  Builder<AcreditacionReporteDiarioFila>  $query
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
                ->orWhere('cargo', 'like', $like)
                ->orWhere('apellido1', 'like', $like)
                ->orWhere('apellido2', 'like', $like)
                ->orWhere('nombre1', 'like', $like)
                ->orWhere('nombre2', 'like', $like)
                ->orWhere('estado_apo', 'like', $like);
        });
    }

    /**
     * @param  Builder<AcreditacionReporteDiarioFila>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderBy('origen')->orderBy('full_name')->orderBy('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Origen, Apellido1, Apellido2, Nombre1, Nombre2, Nombre completo, Cédula, Cargo, Estado, Vigen.Acr
        match ($columnIndex) {
            0 => $query->orderBy('origen', $direction)->orderBy('id', $direction),
            1 => $query->orderBy('apellido1', $direction)->orderBy('id', $direction),
            2 => $query->orderBy('apellido2', $direction)->orderBy('id', $direction),
            3 => $query->orderBy('nombre1', $direction)->orderBy('id', $direction),
            4 => $query->orderBy('nombre2', $direction)->orderBy('id', $direction),
            5 => $query->orderBy('full_name', $direction)->orderBy('id', $direction),
            6 => $query->orderBy('document_number', $direction),
            7 => $query->orderBy('cargo', $direction)->orderBy('id', $direction),
            8 => $query->orderBy('estado_apo', $direction)->orderBy('id', $direction),
            9 => $query->orderBy('vigencia_acr', $direction)->orderBy('id', $direction),
            default => $query->orderBy('origen')->orderBy('full_name')->orderBy('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(AcreditacionReporteDiarioFila $row): array
    {
        return [
            e($row->origenLabel()),
            e((string) ($row->apellido1 ?? '—')),
            e((string) ($row->apellido2 ?? '—')),
            e((string) ($row->nombre1 ?? '—')),
            e((string) ($row->nombre2 ?? '—')),
            e((string) $row->full_name),
            e((string) $row->document_number),
            e((string) ($row->cargo ?? '—')),
            e((string) ($row->resolvedEstadoApo() ?? '—')),
            e(optional($row->vigencia_acr)?->format('Y-m-d') ?: '—'),
        ];
    }
}
