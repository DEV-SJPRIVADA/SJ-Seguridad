<?php

namespace App\Exports;

use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCasoObservacion;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioClienteFoGi22Export
{
    /**
     * @param  Collection<int, ServicioClienteCaso>  $casos
     * @param  Collection<int, ServicioClienteCasoObservacion>  $observaciones
     */
    public function download(Collection $casos, Collection $observaciones): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $casosSheet = $spreadsheet->getActiveSheet();
        $casosSheet->setTitle('Casos');

        (new BaseExport(
            $casos,
            $this->casoColumns(),
            'FO-GI-22.xlsx',
            'FO-GI-22 Servicio al cliente',
        ))->writeTo($casosSheet);

        $obsSheet = $spreadsheet->createSheet();
        $obsSheet->setTitle('Observaciones');

        (new BaseExport(
            $observaciones,
            $this->observacionColumns(),
            'FO-GI-22.xlsx',
            'Observaciones',
        ))->writeTo($obsSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'FO-GI-22.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<array{key: string|\Closure, label: string}>
     */
    private function casoColumns(): array
    {
        return [
            ['key' => 'consecutivo', 'label' => 'N° consecutivo'],
            ['key' => fn (ServicioClienteCaso $caso): string => optional($caso->fecha_recepcion)?->format('d-m-Y') ?? '', 'label' => 'Fecha de recepción'],
            ['key' => fn (ServicioClienteCaso $caso): string => $caso->tipoClienteLabel(), 'label' => 'Tipo de cliente'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->tipologia?->name ?? ''), 'label' => 'Tipología'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->fuente?->name ?? ''), 'label' => 'Fuente'],
            ['key' => 'cliente_nombre', 'label' => 'Nombre del cliente'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->ciudad?->name ?? ''), 'label' => 'Ciudad'],
            ['key' => 'descripcion', 'label' => 'Descripción de la situación'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->categoria?->name ?? ''), 'label' => 'Categoría'],
            ['key' => 'correccion', 'label' => 'Corrección (acción inmediata)'],
            ['key' => 'accion_realizar', 'label' => 'Acción a realizar'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->responsable?->name ?? ''), 'label' => 'Responsable'],
            ['key' => fn (ServicioClienteCaso $caso): string => optional($caso->fecha_ejecucion)?->format('d-m-Y') ?? '', 'label' => 'Fecha de ejecución'],
            ['key' => fn (ServicioClienteCaso $caso): string => optional($caso->fecha_verificacion)?->format('d-m-Y') ?? '', 'label' => 'Fecha de verificación'],
            ['key' => fn (ServicioClienteCaso $caso): string => optional($caso->fecha_plazo_respuesta)?->format('d-m-Y') ?? '', 'label' => 'Fecha de plazo de respuesta'],
            ['key' => fn (ServicioClienteCaso $caso): string => $caso->dias_habiles === null ? '' : (string) $caso->dias_habiles, 'label' => 'Días'],
            ['key' => fn (ServicioClienteCaso $caso): string => (string) ($caso->responsable_seguimiento ?? ''), 'label' => 'Responsable de seguimiento'],
            ['key' => fn (ServicioClienteCaso $caso): string => $caso->eficaciaLabel(), 'label' => 'Eficacia del seguimiento'],
        ];
    }

    /**
     * @return list<array{key: string|\Closure, label: string}>
     */
    private function observacionColumns(): array
    {
        return [
            ['key' => fn (ServicioClienteCasoObservacion $row): string => (string) ($row->caso?->consecutivo ?? ''), 'label' => 'N° consecutivo'],
            ['key' => fn (ServicioClienteCasoObservacion $row): string => optional($row->created_at)?->format('d-m-Y H:i') ?? '', 'label' => 'Fecha'],
            ['key' => fn (ServicioClienteCasoObservacion $row): string => (string) ($row->author?->name ?? ''), 'label' => 'Usuario'],
            ['key' => 'texto', 'label' => 'Observación'],
        ];
    }
}
