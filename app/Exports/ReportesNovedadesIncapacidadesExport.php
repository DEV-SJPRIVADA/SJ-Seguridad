<?php

namespace App\Exports;

use App\Models\ReportesNovedadesIncapacidad;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesIncapacidadesExport
{
    /**
     * @param  Collection<int, ReportesNovedadesIncapacidad>  $rows
     * @param  list<array{key: string|\Closure, label: string}>  $columns
     */
    public function download(Collection $rows, array $columns): StreamedResponse
    {
        return (new BaseExport(
            $rows,
            $columns,
            'reportes_novedades_incapacidades_'.now()->format('Y-m-d').'.xlsx',
            'MT-GH-04 Novedades — Incapacidades — '.config('app.name'),
        ))->download();
    }
}
