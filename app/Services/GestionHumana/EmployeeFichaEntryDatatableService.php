<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        ?string $employmentStatus = null,
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $showRehireable = $estado === 'en_ficha'
            && $employmentStatus === EmployeeFichaProfile::STATUS_DESVINCULADO;

        $recordsTotal = (clone $query)->count();

        $this->applyDatatableSearch($query, $request);

        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, $length));
        }

        if ($showRehireable) {
            $query->with([
                'employmentPeriods' => fn ($periods) => $periods
                    ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
                    ->orderByDesc('sequence'),
            ]);
        }

        /** @var Collection<int, PersonalRequisitionFichaEntry> $entries */
        $entries = $query->get();

        $rows = $entries
            ->map(fn (PersonalRequisitionFichaEntry $entry): array => $this->formatRow(
                $entry,
                $estado,
                $canManage,
                $showRehireable,
            ))
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

        $like = "%{$search}%";
        $statusKeys = $this->employmentStatusKeysMatching($search);
        $searchDate = $this->parsedSearchDate($search);

        $query->where(function (Builder $inner) use ($like, $statusKeys, $searchDate): void {
            $inner->where('hired_document', 'like', $like)
                ->orWhere('hired_full_name', 'like', $like)
                ->orWhereHas('movedBy', fn (Builder $user) => $user->where('name', 'like', $like))
                ->orWhereHas('profile', function (Builder $profile) use ($like, $statusKeys, $searchDate): void {
                    $profile->where('position_name', 'like', $like)
                        ->orWhere('work_center_name', 'like', $like)
                        ->orWhere('residence_city_name', 'like', $like);

                    if ($statusKeys !== []) {
                        $profile->orWhereIn('employment_status', $statusKeys);
                    }

                    if ($searchDate !== null) {
                        $profile->orWhereDate('hire_date', $searchDate)
                            ->orWhereDate('termination_date', $searchDate);
                    }
                })
                ->orWhereHas('requisition', function (Builder $requisition) use ($like, $searchDate): void {
                    $requisition->where('code', 'like', $like)
                        ->orWhereHas('position', fn (Builder $position) => $position->where('name', 'like', $like))
                        ->orWhereHas('client', fn (Builder $client) => $client->where('name', 'like', $like))
                        ->orWhereHas('city', fn (Builder $city) => $city->where('name', 'like', $like));

                    if ($searchDate !== null) {
                        $requisition->orWhereDate('hiring_date', $searchDate);
                    }
                });
        });
    }

    /**
     * @return list<string>
     */
    private function employmentStatusKeysMatching(string $search): array
    {
        $needle = mb_strtolower($search);

        /** @var array<string, string> $labels */
        $labels = config('employee_ficha.employment_status', []);

        return collect($labels)
            ->filter(fn (string $label, string $key): bool => str_contains(mb_strtolower($label), $needle)
                || str_contains(mb_strtolower($key), $needle))
            ->keys()
            ->values()
            ->all();
    }

    private function parsedSearchDate(string $search): ?string
    {
        foreach (['d/m/y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $search);
            } catch (\Throwable) {
                continue;
            }

            if ($date !== false && $date->format($format) === $search) {
                return $date->toDateString();
            }
        }

        return null;
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
    private function formatRow(
        PersonalRequisitionFichaEntry $entry,
        string $estado,
        bool $canManage,
        bool $showRehireable = false,
    ): array {
        $status = $entry->employmentStatus();
        $statusLabel = $entry->employmentStatusLabel();

        $statusCell = $statusLabel !== null
            ? sprintf(
                '<span class="status-pill status-pill--ficha-%s">%s</span>',
                e($status),
                e($statusLabel),
            )
            : '—';

        $fichaHref = $estado === 'en_ficha'
            ? route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry)
            : '';

        $cells = [
            e($entry->hired_document),
            e($entry->hired_full_name),
            e($entry->positionName() ?: '—'),
            e($entry->clientName() ?: '—'),
            e($entry->cityName() ?: '—'),
            e(DisplayDate::date($entry->hireDate())),
            e(DisplayDate::date($entry->terminationDate())),
            $statusCell,
        ];

        if ($showRehireable) {
            $cells[] = e($entry->rehireableLabel() ?: '—');
        }

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

        $actions = sprintf(
            '<a href="%s" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" title="%s" aria-label="%s">%s</a>',
            e($href),
            e($label),
            e($label),
            $this->userPenSvg(),
        );

        // Carta rápida solo para contrataciones nuevas (no reingreso).
        if (! $entry->isRehirePending()) {
            $letterHref = route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry);
            $actions .= sprintf(
                '<a href="%s" class="cursos-catalogo-page__icon-btn" title="%s" aria-label="%s">%s</a>',
                e($letterHref),
                e('Carta de contratación'),
                e('Carta de contratación'),
                $this->fileTextSvg(),
            );
        }

        return sprintf(
            '<div class="table-actions ficha-empleados-row__actions">%s%s</div>',
            $badge,
            $actions,
        );
    }

    private function userPenSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M11.5 15H7a4 4 0 0 0-4 4v2"/>'.
            '<path d="M21.378 16.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>'.
            '<circle cx="10" cy="7" r="4"/></svg>';
    }

    private function fileTextSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>'.
            '<path d="M14 2v4a2 2 0 0 0 2 2h4"/>'.
            '<path d="M10 9H8"/>'.
            '<path d="M16 13H8"/>'.
            '<path d="M16 17H8"/></svg>';
    }
}
