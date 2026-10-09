<?php

namespace App\Exports;

use App\Models\MtSt04Registro;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export de la matriz MT-ST-04: ficha live + editables + vencimientos/estados persistidos.
 */
class MtSt04Export extends BaseExport
{
    /**
     * @param  Collection<int, MtSt04Registro>  $registros
     */
    public function __construct(Collection $registros)
    {
        $data = $registros
            ->map(fn (MtSt04Registro $registro): array => self::mapRegistro($registro))
            ->values();

        parent::__construct(
            $data,
            self::columnDefinitions(),
            'mt_st_04_matriz_'.now()->format('Y-m-d').'.xlsx',
            'MT-ST-04 Matriz — '.config('app.name'),
        );
    }

    /**
     * @param  Collection<int, MtSt04Registro>  $registros
     */
    public static function downloadCollection(Collection $registros): StreamedResponse
    {
        return (new self($registros))->download();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function columnDefinitions(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'en_ficha', 'label' => 'EN FICHA'],
            ['key' => 'full_name', 'label' => 'NOMBRE COMPLETO'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'ciudad', 'label' => 'CIUDAD'],
            ['key' => 'puesto', 'label' => 'PUESTO'],
            ['key' => 'arma', 'label' => 'ARMA'],
            ['key' => 'fecha_examen_1', 'label' => 'FECHA DE EXAMEN'],
            ['key' => 'fecha_vencimiento_1', 'label' => 'FECHA VENCIMIENTO'],
            ['key' => 'apto', 'label' => 'APTO'],
            ['key' => 'observaciones_1', 'label' => 'OBSERVACIONES'],
            ['key' => 'estado_1', 'label' => 'ESTADO'],
            ['key' => 'fecha_examen_2', 'label' => 'FECHA EXAMEN'],
            ['key' => 'fecha_vencimiento_2', 'label' => 'FECHA VENCIMIENTO2'],
            ['key' => 'observaciones_2', 'label' => 'OBSERVACIONES2'],
            ['key' => 'estado_2', 'label' => 'ESTADO2'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapRegistro(MtSt04Registro $registro): array
    {
        $enFicha = $registro->ficha_profile_id !== null;

        return [
            'document_number' => $registro->document_number,
            'en_ficha' => $enFicha ? 'SI' : 'NO',
            'full_name' => $enFicha ? (string) ($registro->ficha_full_name ?? '') : 'SIN FICHA',
            'cargo' => (string) ($registro->ficha_position_name ?? ''),
            'ciudad' => (string) ($registro->ficha_work_city_name ?? ''),
            'puesto' => (string) ($registro->ficha_cost_center_name ?? ''),
            'arma' => $registro->arma,
            'fecha_examen_1' => optional($registro->fecha_examen_1)?->format('Y-m-d'),
            'fecha_vencimiento_1' => optional($registro->fecha_vencimiento_1)?->format('Y-m-d'),
            'apto' => $registro->apto,
            'observaciones_1' => $registro->observaciones_1,
            'estado_1' => $registro->estado_1,
            'fecha_examen_2' => optional($registro->fecha_examen_2)?->format('Y-m-d'),
            'fecha_vencimiento_2' => optional($registro->fecha_vencimiento_2)?->format('Y-m-d'),
            'observaciones_2' => $registro->observaciones_2,
            'estado_2' => $registro->estado_2,
        ];
    }
}
