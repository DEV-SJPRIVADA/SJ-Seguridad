<?php

namespace App\Exports;

use App\Models\ReportesNovedadesRetiro;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesRetirosExport
{
    /**
     * @param  Collection<int, ReportesNovedadesRetiro>  $rows
     * @param  list<array{key: string|\Closure, label: string}>  $columns
     */
    public function download(Collection $rows, array $columns): StreamedResponse
    {
        return (new BaseExport(
            $rows,
            $columns,
            'reportes_novedades_retiros_'.now()->format('Y-m-d').'.xlsx',
            'MT-GH-04 Novedades — Retiros — '.config('app.name'),
        ))->download();
    }
}
