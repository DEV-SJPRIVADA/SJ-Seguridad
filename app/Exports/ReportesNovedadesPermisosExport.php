<?php

namespace App\Exports;

use App\Models\ReportesNovedadesPermiso;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesPermisosExport
{
    /**
     * @param  Collection<int, ReportesNovedadesPermiso>  $rows
     * @param  list<array{key: string|\Closure, label: string}>  $columns
     */
    public function download(Collection $rows, array $columns): StreamedResponse
    {
        return (new BaseExport(
            $rows,
            $columns,
            'reportes_novedades_permisos_'.now()->format('Y-m-d').'.xlsx',
            'MT-GH-04 Novedades — Permisos — '.config('app.name'),
        ))->download();
    }
}
