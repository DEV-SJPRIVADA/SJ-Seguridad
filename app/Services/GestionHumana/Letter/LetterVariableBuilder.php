<?php

namespace App\Services\GestionHumana\Letter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LetterVariableBuilder
{
    private const MONTHS = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Build all available variables from employee data.
     *
     * @return array<string, string>
     */
    public function build(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        ?EmployeeFichaProfile $profile,
        ?CarbonInterface $letterDate = null,
        ?int $signatoryId = null,
    ): array {
        $letterDate ??= now();
        $firma = $this->resolveSignatory($signatoryId);
        $requisition = $entry->requisition;

        $variables = [];

        // --- System-generated ---
        $variables['FECHA'] = $this->formatLongDate($letterDate);

        // --- Firma ---
        $variables['FIRMA'] = $firma['name'];
        $variables['CARGO_FIRMA'] = $firma['code'];

        // --- From PersonalRequisitionFichaEntry ---
        $variables['CEDULA'] = (string) $entry->hired_document;
        $variables['NOMBRE_COMPLETO_ENTRADA'] = (string) $entry->hired_full_name;
        $variables['FECHA_MUDANZA_FICHA'] = $entry->moved_to_ficha_at
            ? $this->formatLongDate($entry->moved_to_ficha_at)
            : '';

        // --- From EmployeeFichaProfile ---
        if ($profile !== null) {
            $variables['DOCUMENTO'] = (string) $profile->document_number;
            $variables['NOMBRE_COMPLETO'] = (string) $profile->full_name;
            $variables['PRIMER_APELLIDO'] = (string) $profile->first_surname;
            $variables['SEGUNDO_APELLIDO'] = (string) $profile->second_surname;
            $variables['PRIMER_NOMBRE'] = (string) $profile->first_name;
            $variables['SEGUNDO_NOMBRE'] = (string) $profile->second_name;
            $variables['TIPO_DOCUMENTO'] = (string) $profile->document_type;
            $variables['FECHA_NACIMIENTO'] = $this->formatLongDate($profile->birth_date);
            $variables['EDAD'] = $profile->age !== null ? (string) $profile->age : '';
            $variables['CIUDAD_EXPEDICION'] = (string) $profile->expedition_city_name;
            $variables['FECHA_EXPEDICION'] = $this->formatLongDate($profile->expedition_date);
            $variables['CIUDAD_RESIDENCIA'] = (string) $profile->residence_city_name;
            $variables['DIRECCION'] = (string) $profile->address;
            $variables['TELEFONO'] = (string) $profile->phone;
            $variables['TELEFONO_SECUNDARIO'] = (string) $profile->phone_secondary;
            $variables['TIPO_SANGRE'] = (string) $profile->blood_type;
            $variables['SEXO'] = (string) $profile->sex;
            $variables['SALARIO'] = $this->formatSalary($profile->salary);
            $variables['NIVEL_EDUCATIVO'] = (string) $profile->education_level;
            $variables['ESTADO_CIVIL'] = (string) $profile->marital_status;
            $variables['NUMERO_HIJOS'] = $profile->children_count !== null ? (string) $profile->children_count : '';
            $variables['EMAIL'] = (string) $profile->email;
            $variables['TIPO_VINCULACION'] = (string) $profile->linkage_type;
            $variables['TIPO_CONTRIBUYENTE'] = (string) $profile->contributor_type;
            $variables['FECHA_CONTRATO'] = $this->formatLongDate($profile->hire_date);
            $variables['FECHA_TERMINACION_PERFIL'] = $this->formatLongDate($profile->termination_date);
            $variables['ESTADO_LABORAL'] = (string) $profile->employment_status;
            $variables['CENTRO_TRABAJO'] = (string) $profile->work_center_name;
            $variables['CENTRO_COSTO'] = (string) $profile->cost_center_code;
            $variables['CENTRO_COSTO_NOMBRE'] = (string) $profile->cost_center_name;
            $variables['CARGO'] = (string) $profile->position_name;
            $variables['CODIGO_CARGO'] = (string) $profile->position_code;
            $variables['ESCALA_SALARIO'] = (string) $profile->salary_scale;
            $variables['TIPO_SALARIO'] = (string) $profile->salary_type_name;
            $variables['TIPO_CONTRATO_PERFIL'] = (string) $profile->contract_type_name;
            $variables['EPS'] = (string) $profile->eps_name;
            $variables['AFP'] = (string) $profile->afp_name;
            $variables['ARP'] = (string) $profile->arp_name;
            $variables['NIVEL_RIESGO'] = (string) $profile->risk_level;
            $variables['FONDO_COMPENSACION'] = (string) $profile->compensation_fund_name;
            $variables['BANCO'] = (string) $profile->bank_name;
            $variables['TIPO_CUENTA'] = (string) $profile->account_type;
            $variables['NUMERO_CUENTA'] = (string) $profile->account_number;
            $variables['METODO_PAGO'] = (string) $profile->payment_method_code;
            $variables['ACTIVIDAD_ECONOMICA'] = (string) $profile->economic_activity_name;
        }

        // --- From EmployeeFichaEmploymentPeriod ---
        $variables['NUMERO_VINCULO'] = $period->sequence !== null ? (string) $period->sequence : '';
        $variables['FECHA_INGRESO'] = $this->formatLongDate($period->hire_date);
        $variables['SALARIO_VINCULO'] = $this->formatSalary($period->salary);
        $variables['CODIGO_CARGO_VINCULO'] = (string) $period->position_code;
        $variables['CARGO_VINCULO'] = (string) $period->position_name;
        $variables['CENTRO_COSTO_VINCULO'] = (string) $period->cost_center_code;
        $variables['CENTRO_COSTO_NOMBRE_VINCULO'] = (string) $period->cost_center_name;
        $variables['TIPO_CONTRATO_VINCULO'] = (string) $period->contract_type_code;
        $variables['TIPO_CONTRATO_NOMBRE_VINCULO'] = (string) $period->contract_type_name;
        $variables['FECHA_FIN_CONTRATO'] = $this->formatLongDate($period->contract_end_date);
        $variables['CENTRO_TRABAJO_VINCULO'] = (string) $period->work_center_name;
        $variables['EPS_VINCULO'] = (string) $period->eps_name;
        $variables['AFP_VINCULO'] = (string) $period->afp_name;
        $variables['TIPO_VINCULACION_VINCULO'] = (string) $period->linkage_type;
        $variables['CAUSAL_TERMINACION'] = (string) $period->termination_cause_code;
        $variables['CAUSAL_TERMINACION_NOMBRE'] = (string) $period->termination_cause_name;
        $variables['RECONTRATABLE'] = $period->is_rehireable !== null ? ($period->is_rehireable ? 'Si' : 'No') : '';
        $variables['ULTIMO_DIA_LABORES'] = $this->formatLongDate($period->last_work_day);
        $variables['FECHA_TERMINACION_VINCULO'] = $this->formatLongDate($period->termination_date);
        $variables['OBSERVACIONES_TERMINACION'] = (string) $period->termination_notes;
        $variables['FECHA_TERMINACION'] = $this->formatLongDate($period->last_work_day ?? $period->termination_date);

        // --- From PersonalRequisition ---
        if ($requisition !== null) {
            $variables['CODIGO_REQUISICION'] = (string) $requisition->code;
            $variables['SOLICITADO_POR'] = (string) $requisition->requester?->name;
            $variables['GERENTE'] = (string) $requisition->manager?->name;
            $variables['FECHA_SOLICITUD'] = $this->formatLongDate($requisition->request_date);
            $variables['LIDER'] = (string) $requisition->leader_name;
            $variables['AREA_SOLICITANTE'] = (string) $requisition->requesting_area_key;
            $variables['CARGO_REQUISITO'] = (string) $requisition->position?->name;
            $variables['SEXO_REQUISICION'] = (string) $requisition->sex;
            $variables['CANTIDAD'] = $requisition->quantity !== null ? (string) $requisition->quantity : '';
            $variables['DOCUMENTO_REEMPLAZO'] = (string) $requisition->replacement_document;
            $variables['NOMBRE_REEMPLAZO'] = (string) $requisition->replacement_name;
            $variables['DURACION_CONTRATO'] = (string) $requisition->contract_duration;
            $variables['SALARIO_BASE'] = $this->formatSalary($requisition->base_salary);
            $variables['AUXILIO_TRANSPORTE'] = $this->formatSalary($requisition->transport_allowance);
            $variables['AUXILIO_MOVILIDAD'] = $this->formatSalary($requisition->mobility_allowance);
            $variables['PRIMA_ESTATUTARIA'] = $this->formatSalary($requisition->statutory_bonus);
            $variables['PRIMA_NO_ESTATUTARIA'] = $this->formatSalary($requisition->non_statutory_bonus);
            $variables['OTROS_AUXILIOS'] = $this->formatSalary($requisition->other_allowances);
            $variables['CONTRATO_ARRENDAMIENTO'] = (string) $requisition->leasing_contract;
            $variables['AREA_OPERATIVA'] = (string) $requisition->operating_area_key;
            $variables['MOTIVO_SOLICITUD'] = (string) $requisition->requestReason?->name;
            $variables['CLIENTE'] = (string) $requisition->client?->name;
            $variables['CIUDAD_REQUISICION'] = (string) $requisition->city?->name;
            $variables['TIPO_CLIENTE'] = (string) $requisition->clientType?->name;
            $variables['TIPO_PROGRAMACION'] = (string) $requisition->programmingType?->name;
            $variables['PERFIL_REQUERIDO'] = (string) $requisition->required_profile;
            $variables['UNIFORME'] = (string) $requisition->uniform?->name;
            $variables['ESTRUCTURA_SERVICIO'] = (string) $requisition->service_structure;
            $variables['CENTRO_COSTO_REQUISICION'] = (string) $requisition->cost_center;
            $variables['OBSERVACIONES_SOLICITANTE'] = (string) $requisition->requester_observation;
            $variables['OBSERVACIONES_RH'] = (string) $requisition->human_resources_observation;
            $variables['RECLUTADOR'] = (string) $requisition->displayRecruiterName();
            $variables['FECHA_CONTRATACION_REQUISICION'] = $this->formatLongDate($requisition->hiring_date);
            $variables['ESTADO_REQUISICION'] = (string) $requisition->status;
        }

        return $variables;
    }

    /**
     * @return array{name: string, code: string}
     */
    private function resolveSignatory(?int $signatoryId): array
    {
        if ($signatoryId !== null) {
            $item = PayrollCatalogItem::query()
                ->where('id', $signatoryId)
                ->where('catalog_type', 'firmas')
                ->first();

            if ($item !== null) {
                return ['name' => (string) $item->name, 'code' => (string) $item->code];
            }
        }

        $fallback = config('employee_ficha.termination_letter_signatory', []);

        return ['name' => (string) ($fallback['name'] ?? ''), 'code' => (string) ($fallback['title'] ?? '')];
    }

    private function formatLongDate(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = Carbon::parse($date);
        $month = self::MONTHS[(int) $carbon->format('n')] ?? ucfirst(mb_strtolower($carbon->format('F')));

        return sprintf('%d de %s del %d', (int) $carbon->format('j'), $month, (int) $carbon->format('Y'));
    }

    private function formatSalary(mixed $salary): string
    {
        if ($salary === null || $salary === '') {
            return '';
        }

        return '$ '.number_format((float) $salary, 0, ',', '.');
    }
}
