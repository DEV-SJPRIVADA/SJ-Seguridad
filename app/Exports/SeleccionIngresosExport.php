<?php

namespace App\Exports;

use App\Models\SeleccionIngreso;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeleccionIngresosExport
{
    /**
     * @param  Collection<int, SeleccionIngreso>  $rows
     */
    public function download(Collection $rows): StreamedResponse
    {
        $columns = [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'APELLIDOS Y NOMBRE'],
            ['key' => 'email', 'label' => 'CORREO'],
            ['key' => 'phone', 'label' => 'TELEFONO'],
            ['key' => 'city_name', 'label' => 'CIUDAD'],
            ['key' => 'position_name', 'label' => 'CARGO'],
            ['key' => 'cliente', 'label' => 'CLIENTE'],
            ['key' => 'shirt_size', 'label' => 'TALLA CAMISA'],
            ['key' => 'pants_size', 'label' => 'TALLA PANTALON'],
            ['key' => 'shoes_size', 'label' => 'TALLA ZAPATOS'],
            ['key' => 'dotacion', 'label' => 'TIPO DOTACION'],
            ['key' => 'fecha_ingreso', 'label' => 'FECHA DE INGRESO'],
            ['key' => 'blood_type_name', 'label' => 'RH'],
            ['key' => 'reemplaza_a', 'label' => 'REEMPLAZA A'],
            ['key' => 'responsable', 'label' => 'RESPONSABLE'],
            ['key' => 'jefe_ope', 'label' => 'JEFE OPE ASIGNADO'],
        ];

        $data = $rows->map(fn (SeleccionIngreso $row): array => [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'email' => $row->email,
            'phone' => $row->phone,
            'city_name' => $row->city_name,
            'position_name' => $row->position_name,
            'cliente' => $row->commercialClient?->name,
            'shirt_size' => $row->shirt_size,
            'pants_size' => $row->pants_size,
            'shoes_size' => $row->shoes_size,
            'dotacion' => $row->uniform?->name,
            'fecha_ingreso' => optional($row->fecha_ingreso)?->format('Y-m-d'),
            'blood_type_name' => $row->blood_type_name,
            'reemplaza_a' => $row->reemplaza_a,
            'responsable' => $row->responsable?->name,
            'jefe_ope' => $row->jefe_ope,
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'seleccion_ingresos_'.now()->format('Y-m-d').'.xlsx',
            'Selección — Ingresos — '.config('app.name'),
        ))->download();
    }
}
