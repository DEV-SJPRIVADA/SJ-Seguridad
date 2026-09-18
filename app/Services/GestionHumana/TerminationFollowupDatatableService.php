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

        $recordsFiltered = (clone $query)->count();

        if ($length !== -1) {
            $query->skip($start)->take(max(1, $length));
        }

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
     * Query filtrada (busqueda, estado, rango fecha entregado nomina) ordenada por id desc.
     *
     * @return Builder<EmployeeTerminationFollowup>
     */
    public function filteredQuery(Request $request): Builder
    {
        $q = trim($request->string('q')->toString());
        $status = $this->resolveStatusFilter($request);
        $fechaDesde = $this->resolveDateFilter($request, 'fecha_desde');
        $fechaHasta = $this->resolveDateFilter($request, 'fecha_hasta');

        return EmployeeTerminationFollowup::query()
            ->search($q)
            ->statusFilter($status)
            ->payrollDeliveredBetween($fechaDesde, $fechaHasta)
            ->orderByDesc('id');
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
        $status = strtolower(trim($request->string('status')->toString()));

        return in_array($status, ['incompletos', 'ok_todo', 'sin_carta'], true)
            ? $status
            : 'todos';
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
