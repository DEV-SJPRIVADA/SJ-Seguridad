<?php

namespace App\Services\GestionHumana;

use App\Models\PersonalRequisitionFichaEntry;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class EmployeeFichaEntryDatatableService
{
    /**
     * @param  Builder<PersonalRequisitionFichaEntry>  $query
     */
    public function respond(
        Request $request,
        Builder $query,
        string $estado,
        bool $canManage,
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $recordsTotal = (clone $query)->count();

        $this->applyDatatableSearch($query, $request);

        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, $length));
        }

        /** @var Collection<int, PersonalRequisitionFichaEntry> $entries */
        $entries = $query->get();

        $rows = $entries
            ->map(fn (PersonalRequisitionFichaEntry $entry): array => $this->formatRow($entry, $estado, $canManage))
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
     * @param  Builder<PersonalRequisitionFichaEntry>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($search): void {
            $inner->where('hired_document', 'like', "%{$search}%")
                ->orWhere('hired_full_name', 'like', "%{$search}%")
                ->orWhereHas('requisition', fn (Builder $requisition) => $requisition->where('code', 'like', "%{$search}%"));
        });
    }

    /**
     * @param  Builder<PersonalRequisitionFichaEntry>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('created_at');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('hired_document', $direction),
            1 => $query->orderBy('hired_full_name', $direction),
            default => $query->orderByDesc('created_at'),
        };
    }

    /**
     * @return array<int, string>
     */
    private function formatRow(PersonalRequisitionFichaEntry $entry, string $estado, bool $canManage): array
    {
        $status = $entry->employmentStatus();
        $statusLabel = $entry->employmentStatusLabel();

        $statusCell = $statusLabel !== null
            ? sprintf(
                '<span class="status-pill status-pill--ficha-%s">%s</span>',
                e($status),
                e($statusLabel),
            )
            : '—';

        $fichaHref = $canManage && $estado === 'en_ficha'
            ? route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry)
            : '';

        $cells = [
            e($entry->hired_document),
            e($entry->hired_full_name),
            e($entry->positionName() ?: '—'),
            e($entry->clientName() ?: '—'),
            e($entry->cityName() ?: '—'),
            e(DisplayDate::date($entry->contractDate())),
            e(DisplayDate::date($entry->hireDate())),
            e(DisplayDate::date($entry->terminationDate())),
            $statusCell,
        ];

        if ($estado === 'en_ficha') {
            $cells[] = e($entry->movedBy?->name ?: '—');
        } else {
            $cells[] = $this->formatPendingActionsCell($entry, $canManage);
        }

        $cells[] = $fichaHref;

        return $cells;
    }

    private function formatPendingActionsCell(PersonalRequisitionFichaEntry $entry, bool $canManage): string
    {
        if (! $canManage) {
            return '—';
        }

        $badge = $entry->isRehirePending()
            ? '<span class="status-pill status-pill--req-en_gestion ficha-empleados-row__rehire-badge">Reingreso</span> '
            : '';

        $label = $entry->isRehirePending() ? 'Gestionar reingreso' : 'Gestionar Empleado';
        $href = route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]);

        return sprintf(
            '<div class="table-actions ficha-empleados-row__actions">%s<a href="%s" class="btn btn--primary btn--sm">%s</a></div>',
            $badge,
            e($href),
            e($label),
        );
    }
}
