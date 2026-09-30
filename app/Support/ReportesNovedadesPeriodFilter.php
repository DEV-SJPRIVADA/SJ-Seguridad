<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class ReportesNovedadesPeriodFilter
{
    /**
     * Resuelve filtros GET: mes (YYYY-MM) + quincena (1|2) por defecto;
     * si hay fecha_desde o fecha_hasta, esas priman y se vacía mes/quincena en la UI.
     *
     * @return array{
     *     q: string,
     *     mes: string,
     *     quincena: string,
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     period_active: bool,
     *     period_desde: string|null,
     *     period_hasta: string|null
     * }
     */
    public static function resolveFromRequest(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $fechaDesde = trim((string) $request->query('fecha_desde', ''));
        $fechaHasta = trim((string) $request->query('fecha_hasta', ''));
        $hasDateRange = $fechaDesde !== '' || $fechaHasta !== '';

        if ($hasDateRange) {
            return [
                'q' => $q,
                'mes' => '',
                'quincena' => '',
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'period_active' => false,
                'period_desde' => null,
                'period_hasta' => null,
            ];
        }

        $mes = trim((string) $request->query('mes', ''));
        if ($mes === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            $mes = self::currentMes();
        }

        $quincenaRaw = trim((string) $request->query('quincena', ''));
        $quincena = (int) $quincenaRaw;
        if (! in_array($quincena, [1, 2], true)) {
            $quincena = self::currentQuincena();
        }

        [$desde, $hasta] = self::boundsFor($mes, $quincena);

        return [
            'q' => $q,
            'mes' => $mes,
            'quincena' => (string) $quincena,
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'period_active' => true,
            'period_desde' => $desde->toDateString(),
            'period_hasta' => $hasta->toDateString(),
        ];
    }

    public static function currentMes(?Carbon $now = null): string
    {
        return ($now ?? now())->format('Y-m');
    }

    public static function currentQuincena(?Carbon $now = null): int
    {
        return ($now ?? now())->day <= 15 ? 1 : 2;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function boundsFor(string $mes, int $quincena): array
    {
        $startOfMonth = Carbon::createFromFormat('Y-m-d', $mes.'-01')->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->startOfDay();

        if ($quincena === 1) {
            return [
                $startOfMonth->copy(),
                $startOfMonth->copy()->day(15)->startOfDay(),
            ];
        }

        return [
            $startOfMonth->copy()->day(16)->startOfDay(),
            $endOfMonth,
        ];
    }

    /**
     * @param  array{
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     period_active?: bool,
     *     period_desde?: string|null,
     *     period_hasta?: string|null
     * }  $filters
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function applyToQuery(Builder $query, array $filters, string $dateColumn): Builder
    {
        if (! empty($filters['period_active'])) {
            $desde = self::parseDate($filters['period_desde'] ?? null);
            $hasta = self::parseDate($filters['period_hasta'] ?? null);

            return $query
                ->when($desde !== null, fn (Builder $q) => $q->whereDate($dateColumn, '>=', $desde))
                ->when($hasta !== null, fn (Builder $q) => $q->whereDate($dateColumn, '<=', $hasta));
        }

        $fechaDesde = self::parseDate($filters['fecha_desde'] ?? null);
        $fechaHasta = self::parseDate($filters['fecha_hasta'] ?? null);

        return $query
            ->when($fechaDesde !== null, fn (Builder $q) => $q->whereDate($dateColumn, '>=', $fechaDesde))
            ->when($fechaHasta !== null, fn (Builder $q) => $q->whereDate($dateColumn, '<=', $fechaHasta));
    }

    public static function parseDate(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array{q?: string, mes?: string, quincena?: string, fecha_desde?: string, fecha_hasta?: string}  $filters
     * @return array<string, string>
     */
    public static function toQueryParams(array $filters): array
    {
        return array_filter([
            'q' => trim((string) ($filters['q'] ?? '')),
            'mes' => trim((string) ($filters['mes'] ?? '')),
            'quincena' => trim((string) ($filters['quincena'] ?? '')),
            'fecha_desde' => trim((string) ($filters['fecha_desde'] ?? '')),
            'fecha_hasta' => trim((string) ($filters['fecha_hasta'] ?? '')),
        ], static fn (string $value): bool => $value !== '');
    }

    /**
     * @param  array{q?: string, mes?: string, quincena?: string, fecha_desde?: string, fecha_hasta?: string}  $filters
     */
    public static function hasNonDefaultUiFilters(array $filters): bool
    {
        if (trim((string) ($filters['q'] ?? '')) !== '') {
            return true;
        }

        if (trim((string) ($filters['fecha_desde'] ?? '')) !== '' || trim((string) ($filters['fecha_hasta'] ?? '')) !== '') {
            return true;
        }

        $mes = trim((string) ($filters['mes'] ?? ''));
        $quincena = trim((string) ($filters['quincena'] ?? ''));

        if ($mes === '' && $quincena === '') {
            return false;
        }

        return $mes !== self::currentMes()
            || (int) $quincena !== self::currentQuincena();
    }
}
