<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AcreditacionReporteDiarioListService
{
    /**
     * @param  array{
     *     fecha_reporte?: string|null,
     *     origen?: string|null,
     *     q?: string|null,
     * }  $filters
     * @return Builder<AcreditacionReporteDiarioFila>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $fecha = $this->resolveFecha($filters['fecha_reporte'] ?? null);

        $query = AcreditacionReporteDiarioFila::query()
            ->whereHas('carga', function (Builder $carga) use ($fecha): void {
                $carga->whereDate('fecha_reporte', $fecha);
            });

        $origen = (string) ($filters['origen'] ?? 'todos');
        if ($origen !== '' && $origen !== 'todos' && in_array($origen, AcreditacionReporteDiarioFila::ORIGENES, true)) {
            $query->where('origen', $origen);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('document_number', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('cargo', 'like', $like)
                    ->orWhere('apellido1', 'like', $like)
                    ->orWhere('apellido2', 'like', $like)
                    ->orWhere('nombre1', 'like', $like)
                    ->orWhere('nombre2', 'like', $like);
            });
        }

        if ($ordered) {
            $query->orderBy('origen')->orderBy('full_name')->orderBy('id');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AcreditacionReporteDiarioFila>
     */
    public function all(array $filters): Collection
    {
        return $this->filteredQuery($filters)->get();
    }

    /**
     * @return Collection<int, AcreditacionReporteDiarioCarga>
     */
    public function cargasList(): Collection
    {
        return AcreditacionReporteDiarioCarga::query()
            ->with(['procesoLoadedBy:id,name', 'acreditadoLoadedBy:id,name'])
            ->orderByDesc('fecha_reporte')
            ->get();
    }

    public function resolveFecha(?string $fecha): string
    {
        $fecha = trim((string) $fecha);
        $today = Carbon::now(config('app.timezone'))->toDateString();

        if ($fecha === '') {
            return $today;
        }

        try {
            $parsed = Carbon::parse($fecha, config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            return $today;
        }

        if ($parsed > $today) {
            return $today;
        }

        return $parsed;
    }
}
