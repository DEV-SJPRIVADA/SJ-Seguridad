<?php

namespace App\Exports;

use App\Models\FormacionRegistro;
use App\Services\GestionHumana\FormacionDatatableService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormacionExport extends BaseExport
{
    /**
     * @param  Collection<int, FormacionRegistro>  $registros
     */
    public function __construct(Collection $registros)
    {
        $data = $registros
            ->map(fn (FormacionRegistro $registro): array => self::mapRegistro($registro))
            ->values();

        parent::__construct(
            $data,
            self::columnDefinitions(),
            'formacion_registros_'.now()->format('Y-m-d').'.xlsx',
            'Formaciones — '.config('app.name'),
        );
    }

    /**
     * @param  Collection<int, FormacionRegistro>  $registros
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
        $importLabels = config('formacion.import.columns', []);

        return [
            ['key' => 'numero_id', 'label' => (string) ($importLabels['numero_id'] ?? 'Número de ID')],
            ['key' => 'nombre_completo', 'label' => (string) ($importLabels['nombre_completo'] ?? 'Nombre completo')],
            ['key' => 'fecha_inicio', 'label' => (string) ($importLabels['fecha_inicio'] ?? 'Fecha de inicio del curso')],
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'anio', 'label' => 'Año'],
            ['key' => 'nombre_curso', 'label' => (string) ($importLabels['nombre_curso'] ?? 'Nombre completo del curso')],
            ['key' => 'calificacion', 'label' => (string) ($importLabels['calificacion'] ?? 'Calificación')],
            ['key' => 'categoria', 'label' => (string) ($importLabels['categoria'] ?? 'Nombre de la categoría')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapRegistro(FormacionRegistro $registro): array
    {
        $mes = (int) $registro->mes;

        return [
            'numero_id' => $registro->numero_id,
            'nombre_completo' => $registro->nombre_completo,
            'fecha_inicio' => optional($registro->fecha_inicio)?->format('Y-m-d'),
            'mes' => FormacionDatatableService::MESES[$mes] ?? $mes,
            'anio' => $registro->anio,
            'nombre_curso' => $registro->nombre_curso,
            'calificacion' => $registro->calificacion,
            'categoria' => $registro->categoria,
        ];
    }
}
