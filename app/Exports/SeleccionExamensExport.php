<?php

namespace App\Exports;

use App\Models\SeleccionExamenOcupacional;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeleccionExamensExport
{
    /**
     * @param  Collection<int, SeleccionExamenOcupacional>  $rows
     */
    public function download(Collection $rows): StreamedResponse
    {
        $columns = [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'APELLIDOS Y NOMBRES'],
            ['key' => 'position_name', 'label' => 'CARGO'],
            ['key' => 'servicio_sector', 'label' => 'SERVICIO/SECTOR'],
            ['key' => 'cliente', 'label' => 'CLIENTE'],
            ['key' => 'eps_name', 'label' => 'EPS'],
            ['key' => 'afp_name', 'label' => 'PENSION (AFP)'],
            ['key' => 'birth_date', 'label' => 'FECHA NACIMIENTO'],
            ['key' => 'city_name', 'label' => 'CIUDAD'],
            ['key' => 'address', 'label' => 'DIRECCION'],
            ['key' => 'email', 'label' => 'CORREO'],
            ['key' => 'phone', 'label' => 'CELULAR'],
            ['key' => 'marital_status_name', 'label' => 'ESTADO CIVIL'],
            ['key' => 'fecha_arl', 'label' => 'FECHA DE ARL'],
            ['key' => 'solicitud_status_name', 'label' => 'SOLICITUD'],
            ['key' => 'responsable', 'label' => 'RESPONSABLE'],
        ];

        $data = $rows->map(fn (SeleccionExamenOcupacional $row): array => [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'position_name' => $row->position_name,
            'servicio_sector' => $row->servicio_sector,
            'cliente' => $row->commercialClient?->name,
            'eps_name' => $row->eps_name,
            'afp_name' => $row->afp_name,
            'birth_date' => optional($row->birth_date)?->format('Y-m-d'),
            'city_name' => $row->city_name,
            'address' => $row->address,
            'email' => $row->email,
            'phone' => $row->phone,
            'marital_status_name' => $row->marital_status_name,
            'fecha_arl' => optional($row->fecha_arl)?->format('Y-m-d'),
            'solicitud_status_name' => $row->solicitud_status_name,
            'responsable' => $row->responsable?->name,
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'seleccion_examenes_'.now()->format('Y-m-d').'.xlsx',
            'Selección — Exámenes ocupacionales — '.config('app.name'),
        ))->download();
    }
}
