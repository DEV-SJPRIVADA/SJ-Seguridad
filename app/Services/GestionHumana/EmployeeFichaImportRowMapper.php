<?php

namespace App\Services\GestionHumana;

use App\Models\PersonalRequisitionFichaEntry;
use Carbon\CarbonInterface;

class EmployeeFichaImportRowMapper
{
    /**
     * @return array<string, mixed>
     */
    public function mapRow(PersonalRequisitionFichaEntry $entry): array
    {
        $entry->loadMissing(['profile']);
        $profile = $entry->profile;

        return [
            'cedula' => $profile?->document_number ?: $entry->hired_document,
            'nombre' => $profile?->full_name ?: $entry->hired_full_name,
            'fecha_nac' => $this->dateString($profile?->birth_date),
            'tipo_documento' => $profile?->document_type,
            'codigo_lugar_exp_cedula' => $profile?->expedition_city_code,
            'lugar_exp_cedula' => $profile?->expedition_city_name,
            'fecha_expedicion' => $this->dateString($profile?->expedition_date),
            'codigo_lugar_residencia' => $profile?->residence_city_code,
            'lugar_residencia' => $profile?->residence_city_name,
            'codigo_ciudad_trabajo' => $profile?->work_city_code,
            'ciudad_trabajo' => $profile?->work_city_name,
            'direccion' => $profile?->address,
            'telefono' => $profile?->phone,
            'tipo_sangre' => $profile?->blood_type,
            'sexo' => $profile?->sex,
            'salario' => $profile?->salary,
            'escolaridad' => $profile?->education_level,
            'estado_civil' => $profile?->marital_status,
            'numero_hijos' => $profile?->children_count,
            'email' => $profile?->email,
            'tipo_vinculacion' => $profile?->linkage_type,
            'fecha_ingreso' => $this->dateString($profile?->hire_date),
            'fecha_vencimiento_contrato' => $this->dateString($profile?->contract_end_date),
            'fecha_retiro' => $this->dateString($profile?->termination_date),
            'nombre_centro_trabajo' => $profile?->work_center_name,
            'ccosto' => $profile?->cost_center_code,
            'nombre_ccosto' => $profile?->cost_center_name,
            'cargo' => $profile?->position_code,
            'nombre_cargo' => $profile?->position_name,
            'tipo_salario' => $profile?->salary_type_code,
            'tipo_contrato' => $profile?->contract_type_code,
            'codigo_eps' => $profile?->eps_code,
            'nombre_eps' => $profile?->eps_name,
            'codigo_afp' => $profile?->afp_code,
            'nombre_afp' => $profile?->afp_name,
            'nombre_arp' => $profile?->arp_name,
            'nivel_riesgo_arp' => $profile?->risk_level,
            'nombre_caja_compensacion' => $profile?->compensation_fund_name,
            'banco' => $profile?->bank_code ?: $profile?->bank_name,
            'tipo_de_cuenta' => $profile?->account_type,
            'cuenta' => $profile?->account_number,
            'forma_pago' => $profile?->payment_method_code,
            'actividad_economica' => $profile?->economic_activity_code,
            'nombre_actividad_economica' => $profile?->economic_activity_name,
            'codigo_requisicion' => $entry->requisitionCode(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapRowWithArchive(PersonalRequisitionFichaEntry $entry): array
    {
        return array_merge($this->mapRow($entry), [
            'estantes' => $entry->profile?->archive_shelf,
            'cajas' => $entry->profile?->archive_box,
        ]);
    }

    private function dateString(?CarbonInterface $value): ?string
    {
        return $value?->toDateString();
    }
}
