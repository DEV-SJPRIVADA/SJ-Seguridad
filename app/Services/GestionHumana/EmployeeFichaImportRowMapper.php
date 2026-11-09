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

        $firstSurname = $profile?->first_surname ?: $entry->first_surname;
        $secondSurname = $profile?->second_surname ?: $entry->second_surname;
        $firstName = $profile?->first_name ?: $entry->first_name;
        $secondName = $profile?->second_name ?: $entry->second_name;
        $fullName = $profile?->full_name ?: $entry->hired_full_name;

        return [
            'cedula' => $profile?->document_number ?: $entry->hired_document,
            'nombre' => $fullName,
            'primer_apellido' => $firstSurname,
            'segundo_apellido' => $secondSurname,
            'primer_nombre' => $firstName,
            'segundo_nombre' => $secondName,
            'fecha_nac' => $this->dateString($profile?->birth_date),
            'tipo_documento' => $profile?->document_type,
            'edad' => $profile?->payrollExtraValue('age'),
            'codigo_lugar_exp_cedula' => $profile?->expedition_city_code,
            'lugar_exp_cedula' => $profile?->expedition_city_name,
            'fecha_expedicion' => $this->dateString($profile?->expedition_date),
            'codigo_lugar_residencia' => $profile?->residence_city_code,
            'lugar_residencia' => $profile?->residence_city_name,
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
            'tipo_cotizante' => $profile?->payrollExtraValue('contributor_type'),
            'fecha_ingreso' => $this->dateString($profile?->hire_date),
            'fecha_vencimiento_contrato' => $this->dateString($profile?->contract_end_date),
            'fecha_retiro' => $this->dateString($profile?->termination_date),
            'nombre_centro_trabajo' => $profile?->work_center_name,
            'ccosto' => $profile?->cost_center_code,
            'nombre_ccosto' => $profile?->cost_center_name,
            'cargo' => $profile?->position_code,
            'nombre_cargo' => $profile?->position_name,
            'escala' => $profile?->payrollExtraValue('salary_scale'),
            'tipo_salario' => $profile?->salary_type_code,
            'tipo_contrato' => $profile?->contract_type_code,
            'ultimo_periodo_vacaciones' => $profile?->payrollExtraValue('last_vacation_period'),
            'total_dias_vacaciones' => $profile?->payrollExtraValue('total_vacation_days'),
            'periodos_pendientes_vacaciones' => $profile?->payrollExtraValue('pending_vacation_periods'),
            'excedente_pdte_vacaciones' => $profile?->payrollExtraValue('vacation_excess_days'),
            'dias_pendientes_a_disfrutar' => $profile?->payrollExtraValue('vacation_days_to_enjoy'),
            'valor_pendiente_de_vacaciones' => $profile?->payrollExtraValue('pending_vacation_value'),
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
            'codigo_ciudad_trabajo' => $profile?->work_city_code,
            'ciudad_trabajo' => $profile?->work_city_name,
            'codigo_requisicion' => $entry->requisitionCode(),
            'lugar_nacimiento' => $profile?->birth_place,
            'telefono_2' => $profile?->phone_secondary,
            'codigo_centro_trabajo' => $profile?->payrollExtraValue('work_center_code'),
            'codigo_caja_compensacion' => $profile?->payrollExtraValue('ccf_code'),
            'codigo_arp' => $profile?->payrollExtraValue('arp_code'),
            'fecha_ingreso_eps' => $this->looseDate($profile?->payrollExtraValue('eps_start_date')),
            'fecha_ingreso_afp' => $this->looseDate($profile?->payrollExtraValue('afp_start_date')),
            'jornada' => $profile?->payrollExtraValue('workday'),
            'tipo_retencion' => $profile?->payrollExtraValue('withholding_type'),
            'tipo_gasto' => $profile?->payrollExtraValue('expense_type'),
            'codigo_sucursal' => $profile?->payrollExtraValue('branch_code'),
            'nombre_sucursal' => $profile?->payrollExtraValue('branch_name'),
            'codigo_destino' => $profile?->payrollExtraValue('destination_code'),
            'nombre_destino' => $profile?->payrollExtraValue('destination_name'),
            'codigo_zona' => $profile?->payrollExtraValue('zone_code'),
            'nombre_zona' => $profile?->payrollExtraValue('zone_name'),
            'codigo_admon_cesantias' => $profile?->payrollExtraValue('severance_admin_code'),
            'nombre_admon_cesantias' => $profile?->payrollExtraValue('severance_admin_name'),
            'fecha_base_vacaciones' => $this->looseDate($profile?->payrollExtraValue('vacation_base_date')),
            'libreta_militar' => $profile?->payrollExtraValue('military_book'),
            'excluir_auxilio_transporte' => $profile?->payrollExtraValue('exclude_transport_allowance'),
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

    private function looseDate(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
