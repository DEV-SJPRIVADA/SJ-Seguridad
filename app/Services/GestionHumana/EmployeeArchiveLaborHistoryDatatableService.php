<?php

namespace App\Services\GestionHumana;

use App\Models\PersonalRequisitionFichaEntry;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EmployeeArchiveLaborHistoryDatatableService
{
    /**
     * @param  Builder<PersonalRequisitionFichaEntry>  $query
     * @param  array{q: string, consultation: int|null}  $filters
     */
    public function respond(
        Request $request,
        Builder $query,
        array $filters,
        bool $canManage,
    ): JsonResponse {
        $query->with([
            'profile',
            'requisition.position',
            'requisition.client',
            'requisition.city',
            'employmentPeriods',
        ]);

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
            ->map(fn (PersonalRequisitionFichaEntry $entry): array => $this->formatRow($entry, $filters, $canManage))
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
                ->orWhereHas('profile', function (Builder $profile) use ($like, $statusKeys, $searchDate): void {
                    $profile->where('document_number', 'like', $like)
                        ->orWhere('full_name', 'like', $like)
                        ->orWhere('position_name', 'like', $like)
                        ->orWhere('work_center_name', 'like', $like)
                        ->orWhere('residence_city_name', 'like', $like)
                        ->orWhere('archive_shelf', 'like', $like)
                        ->orWhere('archive_box', 'like', $like);

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
            $query->orderByDesc('moved_to_ficha_at');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('hired_document', $direction),
            1 => $query->orderBy('hired_full_name', $direction),
            default => $query->orderByDesc('moved_to_ficha_at'),
        };
    }

    /**
     * @param  array{q: string, consultation: int|null}  $filters
     * @return array<int, string>
     */
    private function formatRow(PersonalRequisitionFichaEntry $entry, array $filters, bool $canManage): array
    {
        $rowFormId = 'archivo-row-'.$entry->id;
        $document = $entry->profile?->document_number ?: $entry->hired_document;
        $name = $entry->profile?->full_name ?: $entry->hired_full_name;
        $shelf = $entry->profile?->archive_shelf;
        $box = $entry->profile?->archive_box;

        $cells = [
            e((string) $document),
            e((string) $name),
            e($entry->positionName() ?: '—'),
            e($entry->clientName() ?: '—'),
            e($entry->cityName() ?: '—'),
            e(DisplayDate::date($entry->hireDate())),
            e($entry->employmentStatusLabel() ?: '—'),
            e($entry->rehireableLabel() ?: '—'),
            e(DisplayDate::date($entry->terminationDate())),
        ];

        if ($canManage) {
            $cells[] = $this->formatInlineInput($rowFormId, 'shelf', 'archive_shelf', 'Estante', $shelf);
            $cells[] = $this->formatInlineInput($rowFormId, 'box', 'archive_box', 'Caja', $box);
            $cells[] = $this->formatActionsCell($entry, $rowFormId, $filters);
        } else {
            $cells[] = e($shelf ?: '—');
            $cells[] = e($box ?: '—');
        }

        return $cells;
    }

    private function formatInlineInput(
        string $rowFormId,
        string $fieldKey,
        string $name,
        string $label,
        ?string $value,
    ): string {
        $inputId = $rowFormId.'-'.$fieldKey;

        return sprintf(
            '<div class="archivo-page__field-cell">'.
            '<label class="sr-only" for="%s">%s</label>'.
            '<input id="%s" form="%s" type="text" name="%s" class="form-input archivo-page__inline-input" maxlength="100" value="%s" placeholder="%s">'.
            '</div>',
            e($inputId),
            e($label),
            e($inputId),
            e($rowFormId),
            e($name),
            e((string) $value),
            e($label),
        );
    }

    /**
     * @param  array{q: string, consultation: int|null}  $filters
     */
    private function formatActionsCell(PersonalRequisitionFichaEntry $entry, string $rowFormId, array $filters): string
    {
        $hiddenFilters = '';

        if ($filters['q'] !== '') {
            $hiddenFilters .= sprintf(
                '<input type="hidden" name="q" value="%s">',
                e($filters['q']),
            );
        }

        if ($filters['consultation'] !== null) {
            $hiddenFilters .= sprintf(
                '<input type="hidden" name="consultation" value="%s">',
                e((string) $filters['consultation']),
            );
        }

        return sprintf(
            '<div class="table-actions archivo-page__actions-cell">'.
            '<form id="%s" method="POST" action="%s" class="archivo-page__row-form">'.
            '%s'.
            '<input type="hidden" name="_method" value="PATCH">'.
            '%s'.
            '<button type="submit" class="btn btn--primary btn--sm">Actualizar</button>'.
            '</form>'.
            '</div>',
            e($rowFormId),
            e(route('gestion-humana.archivo.update', $entry)),
            csrf_field(),
            $hiddenFilters,
        );
    }
}
