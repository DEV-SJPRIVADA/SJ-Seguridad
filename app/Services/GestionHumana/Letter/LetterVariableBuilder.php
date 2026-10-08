<?php

namespace App\Services\GestionHumana\Letter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\SpanishMoneyWords;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Builder único de variables Word (${CLAVE}) para desvinculación, contratación y tipos futuros.
 *
 * Para agregar una variable nueva: (1) dato en ficha/BD si falta, (2) `letter_placeholders` en
 * config/employee_ficha.php, (3) mapear aquí en build(). No tocar controladores de generación.
 */
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
            $variables['DOCUMENTO'] = (string) ($profile->document_number ?: $entry->hired_document);
            $variables['NOMBRE_COMPLETO'] = (string) ($profile->full_name ?: $entry->hired_full_name);
            $variables['PRIMER_APELLIDO'] = (string) ($profile->first_surname ?: $entry->first_surname);
            $variables['SEGUNDO_APELLIDO'] = (string) ($profile->second_surname ?: $entry->second_surname);
            $variables['PRIMER_NOMBRE'] = (string) ($profile->first_name ?: $entry->first_name);
            $variables['SEGUNDO_NOMBRE'] = (string) ($profile->second_name ?: $entry->second_name);
            $variables['TIPO_DOCUMENTO'] = (string) $profile->document_type;
            $variables['FECHA_NACIMIENTO'] = $this->formatLongDate($profile->birth_date);
            $variables['LUGAR_NACIMIENTO'] = (string) ($profile->birth_place ?? '');
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
            $variables['SALARIO_EN_LETRAS'] = SpanishMoneyWords::pesos($profile->salary);
            $variables['NIVEL_EDUCATIVO'] = (string) $profile->education_level;
            $variables['ESTADO_CIVIL'] = (string) $profile->marital_status;
            $variables['NUMERO_HIJOS'] = $profile->children_count !== null ? (string) $profile->children_count : '';
            $variables['EMAIL'] = (string) $profile->email;
            $variables['TIPO_VINCULACION'] = (string) $profile->linkage_type;
            $variables['FECHA_CONTRATO'] = $this->formatLongDate($profile->hire_date);
            $variables['FECHA_TERMINACION_PERFIL'] = $this->formatLongDate($profile->termination_date);
            $variables['ESTADO_LABORAL'] = (string) $profile->employment_status;
            $variables['CENTRO_TRABAJO'] = (string) $profile->work_center_name;
            $variables['CENTRO_COSTO'] = (string) $profile->cost_center_code;
            $variables['CENTRO_COSTO_NOMBRE'] = (string) $profile->cost_center_name;
            $variables['CARGO'] = (string) $profile->position_name;
            $variables['CODIGO_CARGO'] = (string) $profile->position_code;
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
        $variables['SALARIO_VINCULO_EN_LETRAS'] = SpanishMoneyWords::pesos($period->salary);
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

        // Garantizar claves del catálogo UI aunque falte perfil/requisición (valor vacío).
        foreach ($this->catalogKeys() as $key) {
            if (! array_key_exists($key, $variables)) {
                $variables[$key] = '';
            }
        }

        return $this->withLegacyAliases($variables, $entry, $period, $profile);
    }

    /**
     * Variables para Cartas Vacaciones (Cliente interno): fila de grilla + ficha por cédula.
     *
     * @param  array{
     *     cedula: string,
     *     nombre_completo: string,
     *     fecha_inicio: string|CarbonInterface,
     *     fecha_fin: string|CarbonInterface,
     *     fecha_reintegro: string|CarbonInterface,
     *     periodos: string,
     *     dias_disfrutados: string|int|float,
     *     signatory_id: int
     * }  $row
     * @return array<string, string>
     */
    public function buildForCartasVacaciones(array $row): array
    {
        $firma = $this->resolveSignatory(isset($row['signatory_id']) ? (int) $row['signatory_id'] : null);
        $cedula = trim((string) ($row['cedula'] ?? ''));
        $nombreFila = trim((string) ($row['nombre_completo'] ?? ''));
        $profile = $this->resolveProfileByDocument($cedula);

        // Catálogo en vacío; ficha rellena CARGO/CIUDAD/etc.; la fila manda en cédula/nombre/vacaciones.
        $variables = [];
        foreach ($this->catalogKeys() as $key) {
            $variables[$key] = '';
        }

        if ($profile !== null) {
            $this->fillProfileVariables($variables, $profile);
        }

        $variables['CEDULA'] = $cedula !== '' ? $cedula : (string) ($variables['CEDULA'] ?? '');
        $variables['DOCUMENTO'] = $variables['CEDULA'] !== ''
            ? $variables['CEDULA']
            : (string) ($variables['DOCUMENTO'] ?? '');
        $variables['NOMBRE_COMPLETO'] = $nombreFila !== ''
            ? $nombreFila
            : (string) ($variables['NOMBRE_COMPLETO'] ?? '');
        // Fechas de vacaciones en mayúsculas (p. ej. «1 DE ENERO DEL 2026»).
        $variables['FECHA_INICIO'] = $this->formatLongDate(
            isset($row['fecha_inicio']) ? Carbon::parse($row['fecha_inicio']) : null,
            uppercase: true,
        );
        $variables['FECHA_FIN'] = $this->formatLongDate(
            isset($row['fecha_fin']) ? Carbon::parse($row['fecha_fin']) : null,
            uppercase: true,
        );
        $variables['FECHA_REINTEGRO'] = $this->formatLongDate(
            isset($row['fecha_reintegro']) ? Carbon::parse($row['fecha_reintegro']) : null,
            uppercase: true,
        );
        $variables['PERIODOS'] = (string) ($row['periodos'] ?? '');
        $variables['DIAS_DISFRUTADOS'] = (string) ($row['dias_disfrutados'] ?? '');
        $variables['FIRMA'] = $firma['name'];
        $variables['CARGO_FIRMA'] = $firma['code'];
        $variables['FECHA'] = $this->formatLongDate(now());

        // Aliases cortos usados en plantillas (igual que desvinculación).
        $variables['NOMBRE'] = $variables['NOMBRE_COMPLETO'];
        $variables['CIUDAD'] = (string) ($variables['CIUDAD_RESIDENCIA'] ?? '');

        return $variables;
    }

    /**
     * Variables para Cartas Notificación (tablero GH): fila de grilla + ficha por cédula.
     * FECHA_TERMINACION proviene de la grilla (no del vínculo/perfil).
     *
     * @param  array{
     *     cedula: string,
     *     nombre_completo: string,
     *     duracion_contrato: int|string,
     *     fecha_terminacion: string|CarbonInterface,
     *     signatory_id: int
     * }  $row
     * @return array<string, string>
     */
    public function buildForCartasNotificacion(array $row): array
    {
        $firma = $this->resolveSignatory(isset($row['signatory_id']) ? (int) $row['signatory_id'] : null);
        $cedula = trim((string) ($row['cedula'] ?? ''));
        $nombreFila = trim((string) ($row['nombre_completo'] ?? ''));
        $profile = $this->resolveProfileByDocument($cedula);

        // Catálogo vacío → ficha rellena CARGO/CIUDAD/etc. → override de fila.
        $variables = [];
        foreach ($this->catalogKeys() as $key) {
            $variables[$key] = '';
        }

        if ($profile !== null) {
            $this->fillProfileVariables($variables, $profile);
        }

        $variables['CEDULA'] = $cedula !== '' ? $cedula : (string) ($variables['CEDULA'] ?? '');
        $variables['DOCUMENTO'] = $variables['CEDULA'] !== ''
            ? $variables['CEDULA']
            : (string) ($variables['DOCUMENTO'] ?? '');
        $variables['NOMBRE_COMPLETO'] = $nombreFila !== ''
            ? $nombreFila
            : (string) ($variables['NOMBRE_COMPLETO'] ?? '');
        $variables['DURACION_CONTRATO'] = $this->normalizeDuracionContrato($row['duracion_contrato'] ?? null);
        // FECHA_TERMINACION de la grilla en mayúsculas (p. ej. «15 DE JUNIO DEL 2026»).
        $variables['FECHA_TERMINACION'] = $this->formatLongDate(
            isset($row['fecha_terminacion']) && $row['fecha_terminacion'] !== ''
                ? Carbon::parse($row['fecha_terminacion'])
                : null,
            uppercase: true,
        );
        $variables['FIRMA'] = $firma['name'];
        $variables['CARGO_FIRMA'] = $firma['code'];
        $variables['FECHA'] = $this->formatLongDate(now());

        $variables['NOMBRE'] = $variables['NOMBRE_COMPLETO'];
        $variables['CIUDAD'] = (string) ($variables['CIUDAD_RESIDENCIA'] ?? '');

        return $variables;
    }

    /**
     * Normaliza duración contrato a "6" | "12" (o vacío si no es opción válida).
     */
    private function normalizeDuracionContrato(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $allowed = array_map('intval', config('cartas_notificacion.duracion_contrato_options', [6, 12]));
        $int = (int) $value;

        return in_array($int, $allowed, true) ? (string) $int : '';
    }

    /**
     * Variables de ficha reutilizables en cartas sin periodo/entrada.
     *
     * @param  array<string, string>  $variables
     */
    private function fillProfileVariables(array &$variables, EmployeeFichaProfile $profile): void
    {
        $variables['DOCUMENTO'] = (string) ($profile->document_number ?? '');
        $variables['CEDULA'] = (string) ($profile->document_number ?? '');
        $variables['NOMBRE_COMPLETO'] = (string) ($profile->full_name ?? '');
        $variables['PRIMER_APELLIDO'] = (string) ($profile->first_surname ?? '');
        $variables['SEGUNDO_APELLIDO'] = (string) ($profile->second_surname ?? '');
        $variables['PRIMER_NOMBRE'] = (string) ($profile->first_name ?? '');
        $variables['SEGUNDO_NOMBRE'] = (string) ($profile->second_name ?? '');
        $variables['TIPO_DOCUMENTO'] = (string) ($profile->document_type ?? '');
        $variables['FECHA_NACIMIENTO'] = $this->formatLongDate($profile->birth_date);
        $variables['LUGAR_NACIMIENTO'] = (string) ($profile->birth_place ?? '');
        $variables['EDAD'] = $profile->age !== null ? (string) $profile->age : '';
        $variables['CIUDAD_EXPEDICION'] = (string) ($profile->expedition_city_name ?? '');
        $variables['FECHA_EXPEDICION'] = $this->formatLongDate($profile->expedition_date);
        $variables['CIUDAD_RESIDENCIA'] = (string) ($profile->residence_city_name ?? '');
        $variables['DIRECCION'] = (string) ($profile->address ?? '');
        $variables['TELEFONO'] = (string) ($profile->phone ?? '');
        $variables['TELEFONO_SECUNDARIO'] = (string) ($profile->phone_secondary ?? '');
        $variables['TIPO_SANGRE'] = (string) ($profile->blood_type ?? '');
        $variables['SEXO'] = (string) ($profile->sex ?? '');
        $variables['SALARIO'] = $this->formatSalary($profile->salary);
        $variables['SALARIO_EN_LETRAS'] = SpanishMoneyWords::pesos($profile->salary);
        $variables['NIVEL_EDUCATIVO'] = (string) ($profile->education_level ?? '');
        $variables['ESTADO_CIVIL'] = (string) ($profile->marital_status ?? '');
        $variables['NUMERO_HIJOS'] = $profile->children_count !== null ? (string) $profile->children_count : '';
        $variables['EMAIL'] = (string) ($profile->email ?? '');
        $variables['TIPO_VINCULACION'] = (string) ($profile->linkage_type ?? '');
        $variables['FECHA_CONTRATO'] = $this->formatLongDate($profile->hire_date);
        $variables['FECHA_TERMINACION_PERFIL'] = $this->formatLongDate($profile->termination_date);
        $variables['ESTADO_LABORAL'] = (string) ($profile->employment_status ?? '');
        $variables['CENTRO_TRABAJO'] = (string) ($profile->work_center_name ?? '');
        $variables['CENTRO_COSTO'] = (string) ($profile->cost_center_code ?? '');
        $variables['CENTRO_COSTO_NOMBRE'] = (string) ($profile->cost_center_name ?? '');
        $variables['CARGO'] = (string) ($profile->position_name ?? '');
        $variables['CODIGO_CARGO'] = (string) ($profile->position_code ?? '');
        $variables['TIPO_SALARIO'] = (string) ($profile->salary_type_name ?? '');
        $variables['TIPO_CONTRATO_PERFIL'] = (string) ($profile->contract_type_name ?? '');
        $variables['EPS'] = (string) ($profile->eps_name ?? '');
        $variables['AFP'] = (string) ($profile->afp_name ?? '');
        $variables['ARP'] = (string) ($profile->arp_name ?? '');
        $variables['NIVEL_RIESGO'] = (string) ($profile->risk_level ?? '');
        $variables['FONDO_COMPENSACION'] = (string) ($profile->compensation_fund_name ?? '');
        $variables['BANCO'] = (string) ($profile->bank_name ?? '');
        $variables['TIPO_CUENTA'] = (string) ($profile->account_type ?? '');
        $variables['NUMERO_CUENTA'] = (string) ($profile->account_number ?? '');
        $variables['METODO_PAGO'] = (string) ($profile->payment_method_code ?? '');
        $variables['ACTIVIDAD_ECONOMICA'] = (string) ($profile->economic_activity_name ?? '');
    }

    private function resolveProfileByDocument(string $cedula): ?EmployeeFichaProfile
    {
        $cedula = trim($cedula);
        if ($cedula === '') {
            return null;
        }

        $find = static function (string $document): ?EmployeeFichaProfile {
            return EmployeeFichaProfile::query()
                ->where('document_number', $document)
                ->orderByRaw(
                    'CASE WHEN employment_status = ? THEN 0 ELSE 1 END',
                    [EmployeeFichaProfile::STATUS_ACTIVO]
                )
                ->orderByDesc('id')
                ->first();
        };

        $profile = $find($cedula);
        if ($profile !== null) {
            return $profile;
        }

        // Fallback si el lote trae puntos/espacios y la ficha solo dígitos.
        $digits = preg_replace('/\D+/', '', $cedula) ?? '';
        if ($digits !== '' && $digits !== $cedula) {
            return $find($digits);
        }

        return null;
    }

    /**
     * Claves publicadas en Plantillas Word (`config/employee_ficha.letter_placeholders`).
     *
     * @return list<string>
     */
    private function catalogKeys(): array
    {
        $keys = [];
        foreach (config('employee_ficha.letter_placeholders', []) as $group) {
            if (! is_array($group)) {
                continue;
            }
            foreach (array_keys($group) as $key) {
                $keys[] = (string) $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Aliases cortos de plantillas legacy de desvinculación.
     *
     * @param  array<string, string>  $variables
     * @return array<string, string>
     */
    private function withLegacyAliases(
        array $variables,
        PersonalRequisitionFichaEntry $entry,
        EmployeeFichaEmploymentPeriod $period,
        ?EmployeeFichaProfile $profile,
    ): array {
        $nombre = $variables['NOMBRE_COMPLETO']
            ?: $variables['NOMBRE_COMPLETO_ENTRADA']
            ?: (string) $entry->hired_full_name;

        $ciudad = $variables['CIUDAD_RESIDENCIA']
            ?: (string) ($period->work_center_name ?? '')
            ?: (string) ($profile?->residence_city_name ?? '');

        $tipoContrato = $variables['TIPO_CONTRATO_NOMBRE_VINCULO']
            ?: $variables['TIPO_CONTRATO_PERFIL']
            ?: (string) ($period->contract_type_name ?? '');

        $salario = $variables['SALARIO'] !== ''
            ? $variables['SALARIO']
            : ($variables['SALARIO_VINCULO'] ?? '');

        $variables['NOMBRE'] = $nombre;
        $variables['CIUDAD'] = $ciudad;
        $variables['TIPO_CONTRATO'] = $tipoContrato;

        if (($variables['SALARIO'] ?? '') === '' && $salario !== '') {
            $variables['SALARIO'] = $salario;
        }

        if (($variables['SALARIO_EN_LETRAS'] ?? '') === '') {
            $variables['SALARIO_EN_LETRAS'] = SpanishMoneyWords::pesos(
                $profile?->salary ?? $period->salary,
            );
        }

        if (($variables['DOCUMENTO'] ?? '') === '') {
            $variables['DOCUMENTO'] = $variables['CEDULA'] ?: (string) $entry->hired_document;
        }

        if (($variables['CEDULA'] ?? '') === '') {
            $variables['CEDULA'] = $variables['DOCUMENTO'] ?: (string) $entry->hired_document;
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

    private function formatLongDate(?CarbonInterface $date, bool $uppercase = false): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = Carbon::parse($date);
        $month = self::MONTHS[(int) $carbon->format('n')] ?? ucfirst(mb_strtolower($carbon->format('F')));

        $formatted = sprintf('%d de %s del %d', (int) $carbon->format('j'), $month, (int) $carbon->format('Y'));

        return $uppercase ? mb_strtoupper($formatted, 'UTF-8') : $formatted;
    }

    private function formatSalary(mixed $salary): string
    {
        if ($salary === null || $salary === '') {
            return '';
        }

        return '$ '.number_format((float) $salary, 0, ',', '.');
    }
}
