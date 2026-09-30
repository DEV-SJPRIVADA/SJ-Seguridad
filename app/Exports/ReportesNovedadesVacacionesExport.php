<?php

namespace App\Exports;

use App\Models\ReportesNovedadesVacacion;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesVacacionesExport
{
    /**
     * @param  Collection<int, ReportesNovedadesVacacion>  $rows
     * @param  list<array{key: string|\Closure, label: string}>  $columns
     */
    public function download(Collection $rows, array $columns): StreamedResponse
    {
        return (new BaseExport(
            $rows,
            $columns,
            'reportes_novedades_vacaciones_'.now()->format('Y-m-d').'.xlsx',
            'MT-GH-04 Novedades — Vacaciones — '.config('app.name'),
        ))->download();
    }
}
