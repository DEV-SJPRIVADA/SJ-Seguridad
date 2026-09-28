<?php

namespace Database\Seeders;

use App\Models\AcreditacionExportApoSetting;
use Illuminate\Database\Seeder;

class AcreditacionExportApoSettingSeeder extends Seeder
{
    /**
     * Valores iniciales cerrados (FEAT-039). Idempotente: exactamente 1 fila.
     *
     * @return array{
     *     nit: string,
     *     razon_social: string,
     *     tipo_documento: string,
     *     tipo_establecimiento: string,
     *     telefono_r: string,
     *     direccion_r: string,
     *     direccion_p: string,
     *     departamento: string,
     *     ciudad: string,
     *     educacion_bm: string,
     *     educacion_s: string,
     *     discapacidad: string
     * }
     */
    public static function defaultAttributes(): array
    {
        return [
            'nit' => '9005767186',
            'razon_social' => 'SJ SEGURIDAD PRIVADA LTDA',
            'tipo_documento' => '1',
            'tipo_establecimiento' => 'Principal',
            'telefono_r' => '3043413064',
            'direccion_r' => 'MANZANA 8 CASA 27',
            'direccion_p' => 'AV 4N26N 39',
            'departamento' => 'ValledelCauca',
            'ciudad' => 'CALI',
            'educacion_bm' => '11',
            'educacion_s' => 'Ninguna',
            'discapacidad' => 'Ninguna',
        ];
    }

    public function run(): void
    {
        $existing = AcreditacionExportApoSetting::query()->orderBy('id')->first();

        if ($existing !== null) {
            return;
        }

        AcreditacionExportApoSetting::query()->create(self::defaultAttributes());
    }
}
