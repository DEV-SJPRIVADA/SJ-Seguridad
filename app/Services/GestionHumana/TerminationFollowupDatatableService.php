<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeTerminationFollowup;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class TerminationFollowupDatatableService
{
    public function respond(Request $request): JsonResponse
    {
        $baseQuery = EmployeeTerminationFollowup::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = $this->filteredQuery($request);

        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        // Tope duro: nunca devolver “todos” sin límite (DataTables length -1).
        if ($length === -1 || $length > 100) {
            $length = 100;
        }
        $length = max(1, $length);

        $recordsFiltered = (clone $query)->count();

        $query->skip($start)->take($length);

        /** @var Collection<int, EmployeeTerminationFollowup> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (EmployeeTerminationFollowup $followup): array => $this->formatRow($followup))
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
     * Query filtrada (busqueda, estado, rango por campo de fecha) ordenada por id desc.
     *
     * @return Builder<EmployeeTerminationFollowup>
     */
    public function filteredQuery(Request $request): Builder
    {
        $q = trim($request->string('search.value')->toString());
        if ($q === '') {
            $q = trim($request->string('q')->toString());
        }
        $status = $this->resolveStatusFilter($request);
        $rehireable = $this->resolveRehireableFilter($request);
        $fechaCampo = $this->resolveDateFieldFilter($request);
        $fechaDesde = $this->resolveDateFilter($request, 'fecha_desde');
        $fechaHasta = $this->resolveDateFilter($request, 'fecha_hasta');

        $query = EmployeeTerminationFollowup::query()
            ->search($q)
            ->statusFilter($status)
            ->rehireableFilter($rehireable)
            ->dateFieldBetween($fechaCampo, $fechaDesde, $fechaHasta);

        $this->applyOrdering($query, $request);

        return $query;
    }

    /**
     * Orden DataTables server-side (índices alineados con columnas de la vista).
     *
     * @param  Builder<EmployeeTerminationFollowup>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $checkFields = array_values(EmployeeTerminationFollowup::CHECK_FIELDS);

        // Columnas 7–14: checks booleanos.
        if ($columnIndex >= 7 && $columnIndex <= 14) {
            $field = $checkFields[$columnIndex - 7] ?? null;
            if ($field !== null) {
                $query->orderBy($field, $direction)->orderByDesc('id');

                return;
            }
        }

        // OK TODO (índice 15): AND de los 8 checks (misma lógica que isOkTodo).
        if ($columnIndex === 15) {
            $expr = implode(' AND ', array_map(
                static fn (string $field): string => $field.' = 1',
                $checkFields,
            ));
            $query->orderByRaw('('.$expr.') '.$direction)->orderByDesc('id');

            return;
        }

        match ($columnIndex) {
            0 => $query->orderBy('id', $direction),
            1 => $query->orderBy('document_number', $direction)->orderByDesc('id'),
            2 => $query->orderBy('full_name', $direction)->orderByDesc('id'),
            3 => $query->orderBy('position_name', $direction)->orderByDesc('id'),
            4, 18 => $query->orderBy('termination_cause_name', $direction)->orderByDesc('id'),
            5 => $query->orderBy('registered_at', $direction)->orderByDesc('id'),
            6 => $query->orderBy('termination_date', $direction)->orderByDesc('id'),
            16 => $query->orderBy('payroll_delivered_at', $direction)->orderByDesc('id'),
            17 => $query->orderBy('letter_generated', $direction)->orderByDesc('id'),
            19 => $query->orderBy('is_rehireable', $direction)->orderByDesc('id'),
            20 => $query->orderBy('termination_notes', $direction)->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRow(EmployeeTerminationFollowup $followup): array
    {
        $checks = [];
        foreach (EmployeeTerminationFollowup::CHECK_FIELDS as $field) {
            $checks[$field] = (bool) $followup->{$field};
        }

        return [
            'id' => $followup->id,
            'document_number' => $followup->document_number,
            'full_name' => $followup->full_name,
            'position_name' => $followup->position_name,
            'termination_cause_name' => $followup->termination_cause_name,
            'termination_cause_code' => $followup->termination_cause_code,
            'registered_at' => $followup->registered_at?->toIso8601String(),
            'registered_at_display' => DisplayDate::dateTime($followup->registered_at),
            'termination_date' => $followup->termination_date?->toDateString(),
            'termination_date_display' => DisplayDate::date($followup->termination_date),
            'checks' => $checks,
            'ok_todo' => $followup->isOkTodo(),
            'payroll_delivered_at' => $followup->payroll_delivered_at?->toDateString(),
            'payroll_delivered_at_display' => DisplayDate::date($followup->payroll_delivered_at),
            'letter_generated' => (bool) $followup->letter_generated,
            'letter_generated_label' => $followup->letter_generated ? 'Si' : 'No',
            'is_rehireable' => $followup->is_rehireable,
            'is_rehireable_label' => match ($followup->is_rehireable) {
                true => 'Si',
                false => 'No',
                default => '—',
            },
            'termination_notes' => $followup->termination_notes,
            'update_url' => route('gestion-humana.desvinculaciones.seguimientos.update', $followup),
            'revert_url' => route('gestion-humana.desvinculaciones.seguimientos.revert', $followup),
        ];
    }

    /**
     * Columnas del export Excel de Seguimientos (labels operativos).
     *
     * @return list<array{key: string|\Closure, label: string}>
     */
    public function exportColumns(): array
    {
        $columns = [
            ['key' => 'id', 'label' => 'No'],
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'NOMBRE Y APELLIDOS'],
            ['key' => 'position_name', 'label' => 'CARGO'],
            ['key' => 'termination_cause_name', 'label' => 'TIPO DESVINCULACION'],
            [
                'key' => static fn (EmployeeTerminationFollowup $row): string => DisplayDate::dateTime($row->registered_at, ''),
                'label' => 'FECHA DE REGISTRO',
            ],
            [
                'key' => static fn (EmployeeTerminationFollowup $row): string => DisplayDate::date($row->termination_date, ''),
                'label' => 'FECHA DESVINCULACION',
            ],
        ];

        foreach (EmployeeTerminationFollowup::CHECK_LABELS as $field => $label) {
            $columns[] = [
                'key' => fn (EmployeeTerminationFollowup $row): string => ((bool) $row->{$field}) ? 'Si' : 'No',
                'label' => $label,
            ];
        }

        $columns[] = [
            'key' => static fn (EmployeeTerminationFollowup $row): string => $row->isOkTodo() ? 'Si' : 'No',
            'label' => 'OK TODO',
        ];
        $columns[] = [
            'key' => static fn (EmployeeTerminationFollowup $row): string => (string) ($row->termination_notes ?? ''),
            'label' => 'OBSERVACIONES',
        ];

        return $columns;
    }

    private function resolveStatusFilter(Request $request): string
    {
        // status= (vacío) = sin filtro de estado (p. ej. solo rango de fechas).
        // Sin clave status = default incompletos.
        if (! array_key_exists('status', $request->query()) && ! $request->exists('status')) {
            return 'incompletos';
        }

        $status = strtolower(trim((string) $request->input('status', '')));

        if ($status === '') {
            return '';
        }

        return in_array($status, ['incompletos', 'ok_todo', 'sin_carta'], true)
            ? $status
            : 'incompletos';
    }

    private function resolveRehireableFilter(Request $request): string
    {
        $raw = strtolower(trim($request->string('rehireable')->toString()));

        return in_array($raw, ['1', '0', 'si', 'sí', 'no', 'true', 'false'], true)
            ? $raw
            : '';
    }

    private function resolveDateFieldFilter(Request $request): string
    {
        $field = trim($request->string('fecha_campo')->toString());

        return array_key_exists($field, EmployeeTerminationFollowup::DATE_FILTER_FIELDS)
            ? $field
            : EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD;
    }

    private function resolveDateFilter(Request $request, string $key): ?string
    {
        $raw = trim($request->string($key)->toString());

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
