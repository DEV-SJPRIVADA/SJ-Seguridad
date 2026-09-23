<?php

namespace Database\Seeders;

use App\Models\AcreditacionCargo;
use Illuminate\Database\Seeder;

class AcreditacionCargoSeeder extends Seeder
{
    /**
     * Seed idempotente (upsert por cargo_manager + cargo_apo).
     *
     * @return list<array{cargo_manager: string, cargo_apo: string, cargo_informe: string, cargo_acreditacion: string}>
     */
    public static function defaultRows(): array
    {
        return [
            ['cargo_manager' => 'GUARDA', 'cargo_apo' => 'VIGILANTE', 'cargo_informe' => 'GUARDAS', 'cargo_acreditacion' => '1'],
            ['cargo_manager' => 'GUARDA MENSUAL', 'cargo_apo' => 'VIGILANTE', 'cargo_informe' => 'GUARDAS', 'cargo_acreditacion' => '1'],
            ['cargo_manager' => 'GUARDA SJ', 'cargo_apo' => 'VIGILANTE', 'cargo_informe' => 'GUARDAS', 'cargo_acreditacion' => '1'],
            ['cargo_manager' => 'COORDINADOR CARTAGENA', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'COORDINADORES', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'COORDINADOR DE OPERACIONES', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'COORDINADORES', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'COORDINADORES', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'COORDINADORES', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'COORDINADORES MENSUAL', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'COORDINADORES', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'DIRECTOR NACIONAL OPERACIONES Y GESTION DE RIESGOS', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'DIRECTOR NACIONAL', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'ESCOLTA', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'ESCOLTA', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'ESCOLTA MENSUAL', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'ESCOLTA', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'JEFE DE SEGURIDAD', 'cargo_apo' => 'ESCOLTA', 'cargo_informe' => 'JEFES DE SEGURIDAD', 'cargo_acreditacion' => '2'],
            ['cargo_manager' => 'SUPERVISOR SJ', 'cargo_apo' => 'SUPERVISOR', 'cargo_informe' => 'SUPERVISORES', 'cargo_acreditacion' => '4'],
            ['cargo_manager' => 'SUPERVISORES', 'cargo_apo' => 'SUPERVISOR', 'cargo_informe' => 'SUPERVISORES', 'cargo_acreditacion' => '4'],
            ['cargo_manager' => 'SUPERVISORES MENSUAL', 'cargo_apo' => 'SUPERVISOR', 'cargo_informe' => 'SUPERVISORES', 'cargo_acreditacion' => '4'],
            ['cargo_manager' => 'OPERADOR', 'cargo_apo' => 'OPERADOR DE MEDIOS TECNOLOGICOS', 'cargo_informe' => 'OMT(OPERADOR)', 'cargo_acreditacion' => '5'],
            ['cargo_manager' => 'OPERADOR SJ', 'cargo_apo' => 'OPERADOR DE MEDIOS TECNOLOGICOS', 'cargo_informe' => 'OMT(OPERADOR)', 'cargo_acreditacion' => '5'],
            ['cargo_manager' => 'GUARDA MANEJADOR CANINO', 'cargo_apo' => 'MANEJADOR CANINO', 'cargo_informe' => 'MANEJADOR CANINO', 'cargo_acreditacion' => '6'],
        ];
    }

    public function run(): void
    {
        foreach (self::defaultRows() as $index => $row) {
            AcreditacionCargo::query()->updateOrCreate(
                [
                    'cargo_manager' => $row['cargo_manager'],
                    'cargo_apo' => $row['cargo_apo'],
                ],
                [
                    'cargo_informe' => $row['cargo_informe'],
                    'cargo_acreditacion' => $row['cargo_acreditacion'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }
}
