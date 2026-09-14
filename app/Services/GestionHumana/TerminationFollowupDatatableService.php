<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeTerminationFollowup;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class TerminationFollowupDatatableService
{
    public function respond(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());
        $status = $this->resolveStatusFilter($request);

        $baseQuery = EmployeeTerminationFollowup::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = (clone $baseQuery)
            ->search($q)
            ->statusFilter($status)
            ->orderByDesc('id');

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

    private function resolveStatusFilter(Request $request): string
    {
        $status = strtolower(trim($request->string('status')->toString()));

        return in_array($status, ['incompletos', 'ok_todo', 'sin_carta'], true)
            ? $status
            : 'todos';
    }
}
