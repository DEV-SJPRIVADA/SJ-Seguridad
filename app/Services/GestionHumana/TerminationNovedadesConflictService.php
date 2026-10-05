<?php

namespace App\Services\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Models\ReportesNovedadesPermiso;
use App\Models\ReportesNovedadesVacacion;
use App\Models\User;
use App\Support\DisplayDate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Bloquea desvinculación si el retiro (último día + fecha desvinculación) se solapa
 * con vacaciones, incapacidades o permisos en MT-GH-04.
 */
class TerminationNovedadesConflictService
{
    /**
     * @return list<array{
     *     sheet: string,
     *     sheet_label: string,
     *     id: int,
     *     fecha_inicio: ?string,
     *     fecha_fin: ?string,
     *     open_ended: bool,
     *     detail: string
     * }>
     */
    public function findConflicts(
        string $documentNumber,
        CarbonInterface|string $lastWorkDay,
        CarbonInterface|string $terminationDate,
    ): array {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return [];
        }

        $termStart = Carbon::parse($lastWorkDay)->startOfDay();
        $termEnd = Carbon::parse($terminationDate)->startOfDay();

        if ($termEnd->lt($termStart)) {
            [$termStart, $termEnd] = [$termEnd->copy(), $termStart->copy()];
        }

        $conflicts = [];

        foreach ($this->vacacionesConflicts($documentNumber, $termStart, $termEnd) as $conflict) {
            $conflicts[] = $conflict;
        }

        foreach ($this->incapacidadesConflicts($documentNumber, $termStart, $termEnd) as $conflict) {
            $conflicts[] = $conflict;
        }

        foreach ($this->permisosConflicts($documentNumber, $termStart, $termEnd) as $conflict) {
            $conflicts[] = $conflict;
        }

        return $conflicts;
    }

    /**
     * @throws ValidationException
     */
    public function assertNoConflictsOrForced(
        string $documentNumber,
        CarbonInterface|string $lastWorkDay,
        CarbonInterface|string $terminationDate,
        ?User $actor,
        bool $force = false,
    ): void {
        $conflicts = $this->findConflicts($documentNumber, $lastWorkDay, $terminationDate);

        if ($conflicts === []) {
            return;
        }

        if ($force && $actor !== null && $actor->hasRole('super-admin')) {
            return;
        }

        $messages = array_map(
            static fn (array $conflict): string => $conflict['detail'],
            $conflicts,
        );

        $hint = ($actor !== null && $actor->hasRole('super-admin'))
            ? ' Como super-admin puede marcar «Forzar a pesar del cruce» para continuar.'
            : '';

        throw ValidationException::withMessages([
            'novedades_conflict' => array_values(array_filter([
                'No es posible desvincular: hay novedades que se cruzan con el retiro.'.$hint,
                ...$messages,
            ])),
        ]);
    }

    /**
     * @return list<array{sheet: string, sheet_label: string, id: int, fecha_inicio: ?string, fecha_fin: ?string, open_ended: bool, detail: string}>
     */
    private function vacacionesConflicts(string $documentNumber, Carbon $termStart, Carbon $termEnd): array
    {
        $rows = ReportesNovedadesVacacion::query()
            ->where('document_number', $documentNumber)
            ->whereNotNull('fecha_inicio')
            ->get(['id', 'novedad', 'fecha_inicio', 'fecha_fin', 'dias_novedad']);

        $out = [];

        foreach ($rows as $row) {
            $start = $row->fecha_inicio?->copy()->startOfDay();
            if ($start === null) {
                continue;
            }

            $openEnded = $row->fecha_fin === null;
            $end = $openEnded
                ? null
                : $row->fecha_fin->copy()->startOfDay();

            if (! $this->rangesOverlap($termStart, $termEnd, $start, $end)) {
                continue;
            }

            $out[] = $this->conflictPayload(
                sheet: 'vacaciones',
                sheetLabel: 'Vacaciones',
                id: (int) $row->id,
                start: $start,
                end: $end,
                openEnded: $openEnded,
                extra: (string) $row->novedad,
            );
        }

        return $out;
    }

    /**
     * @return list<array{sheet: string, sheet_label: string, id: int, fecha_inicio: ?string, fecha_fin: ?string, open_ended: bool, detail: string}>
     */
    private function incapacidadesConflicts(string $documentNumber, Carbon $termStart, Carbon $termEnd): array
    {
        $rows = ReportesNovedadesIncapacidad::query()
            ->where('document_number', $documentNumber)
            ->whereNotNull('fecha_inicio')
            ->get(['id', 'tipo_incapacidad', 'fecha_inicio', 'fecha_fin']);

        $out = [];

        foreach ($rows as $row) {
            $start = $row->fecha_inicio?->copy()->startOfDay();
            if ($start === null) {
                continue;
            }

            $openEnded = $row->fecha_fin === null;
            $end = $openEnded ? null : $row->fecha_fin->copy()->startOfDay();

            if (! $this->rangesOverlap($termStart, $termEnd, $start, $end)) {
                continue;
            }

            $out[] = $this->conflictPayload(
                sheet: 'incapacidades',
                sheetLabel: 'Incapacidades',
                id: (int) $row->id,
                start: $start,
                end: $end,
                openEnded: $openEnded,
                extra: (string) ($row->tipo_incapacidad ?: ''),
            );
        }

        return $out;
    }

    /**
     * @return list<array{sheet: string, sheet_label: string, id: int, fecha_inicio: ?string, fecha_fin: ?string, open_ended: bool, detail: string}>
     */
    private function permisosConflicts(string $documentNumber, Carbon $termStart, Carbon $termEnd): array
    {
        $rows = ReportesNovedadesPermiso::query()
            ->where('document_number', $documentNumber)
            ->whereNotNull('fecha_inicio')
            ->get(['id', 'novedad', 'fecha_inicio', 'fecha_fin']);

        $out = [];

        foreach ($rows as $row) {
            $start = $row->fecha_inicio?->copy()->startOfDay();
            if ($start === null) {
                continue;
            }

            $openEnded = $row->fecha_fin === null;
            $end = $openEnded ? null : $row->fecha_fin->copy()->startOfDay();

            if (! $this->rangesOverlap($termStart, $termEnd, $start, $end)) {
                continue;
            }

            $out[] = $this->conflictPayload(
                sheet: 'permisos',
                sheetLabel: 'Permisos',
                id: (int) $row->id,
                start: $start,
                end: $end,
                openEnded: $openEnded,
                extra: (string) ($row->novedad ?: ''),
            );
        }

        return $out;
    }

    /**
     * Solape inclusivo. Si $noveltyEnd es null, el rango de novedad está abierto (sin fin).
     */
    private function rangesOverlap(
        Carbon $termStart,
        Carbon $termEnd,
        Carbon $noveltyStart,
        ?Carbon $noveltyEnd,
    ): bool {
        if ($noveltyEnd === null) {
            // Abierto: bloquea si el retiro termina en o después del inicio de la novedad.
            return $termEnd->gte($noveltyStart);
        }

        return $termStart->lte($noveltyEnd) && $noveltyStart->lte($termEnd);
    }

    /**
     * @return array{sheet: string, sheet_label: string, id: int, fecha_inicio: ?string, fecha_fin: ?string, open_ended: bool, detail: string}
     */
    private function conflictPayload(
        string $sheet,
        string $sheetLabel,
        int $id,
        Carbon $start,
        ?Carbon $end,
        bool $openEnded,
        string $extra,
    ): array {
        $range = DisplayDate::date($start);
        $range .= $openEnded
            ? ' → sin fecha fin (vigente)'
            : (' → '.DisplayDate::date($end));

        $label = trim($extra) !== '' ? "{$sheetLabel} ({$extra})" : $sheetLabel;

        return [
            'sheet' => $sheet,
            'sheet_label' => $sheetLabel,
            'id' => $id,
            'fecha_inicio' => $start->toDateString(),
            'fecha_fin' => $end?->toDateString(),
            'open_ended' => $openEnded,
            'detail' => "{$label}: {$range}.",
        ];
    }
}
