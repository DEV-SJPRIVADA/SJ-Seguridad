<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionChangeLog;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionContractType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRecruiter;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use Illuminate\Support\Str;

class PersonalRequisitionChangeLogger
{
    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'position_id' => 'Cargo solicitado',
        'sex' => 'Sexo',
        'quantity' => 'Cantidad',
        'replacement_document' => 'Documento a reemplazar',
        'replacement_name' => 'Nombre a reemplazar',
        'operating_area_key' => 'Area operativa',
        'request_reason_id' => 'Motivo de solicitud',
        'client_id' => 'Cliente',
        'city_id' => 'Ciudad',
        'client_type_id' => 'Tipo de cliente',
        'programming_type_id' => 'Tipo de programacion',
        'required_profile' => 'Perfil requerido',
        'uniform_id' => 'Uniforme requerido',
        'contract_type_id' => 'Tipo de contrato',
        'contract_duration' => 'Duracion del contrato',
        'base_salary' => 'Salario base',
        'transport_allowance' => 'Auxilio de transporte',
        'mobility_allowance' => 'Auxilio de movilidad',
        'statutory_bonus' => 'Prima legal',
        'non_statutory_bonus' => 'Prima extralegal',
        'other_allowances' => 'Otros auxilios',
        'leasing_contract' => 'Contrato leasing',
        'recruiter_id' => 'Encargado de seleccion',
        'recruiter_name' => 'Nombre reclutador',
        'cost_center' => 'Centro de costo',
        'requester_observation' => 'Observacion del solicitante',
        'human_resources_observation' => 'Observacion de gestion humana',
        'hiring_date' => 'Fecha de contratacion',
        'status' => 'Estado',
    ];

    /**
     * @var array<string, string>
     */
    private const SEX_LABELS = [
        'masculino' => 'Masculino',
        'femenino' => 'Femenino',
        'indiferente' => 'Indiferente',
    ];

    /**
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const FK_MODELS = [
        'position_id' => RequisitionPosition::class,
        'request_reason_id' => RequisitionRequestReason::class,
        'client_id' => RequisitionClient::class,
        'city_id' => RequisitionCity::class,
        'client_type_id' => RequisitionClientType::class,
        'programming_type_id' => RequisitionProgrammingType::class,
        'uniform_id' => RequisitionUniform::class,
        'contract_type_id' => RequisitionContractType::class,
        'recruiter_id' => RequisitionRecruiter::class,
    ];

    /**
     * @var array<string, string|null>
     */
    private array $fkLabelCache = [];

    /**
     * @param  array<string, mixed>  $updateData
     */
    public function logUpdate(PersonalRequisition $requisition, array $updateData, int $userId): void
    {
        $batch = (string) Str::uuid();
        $rows = [];

        foreach (self::FIELD_LABELS as $field => $label) {
            if (! array_key_exists($field, $updateData)) {
                continue;
            }

            $oldRaw = $requisition->getAttribute($field);
            $newRaw = $updateData[$field];

            if ($this->valuesAreEqual($field, $oldRaw, $newRaw)) {
                continue;
            }

            $rows[] = [
                'personal_requisition_id' => $requisition->id,
                'change_batch' => $batch,
                'field_key' => $field,
                'field_label' => $label,
                'old_value' => $this->formatDisplayValue($field, $oldRaw),
                'new_value' => $this->formatDisplayValue($field, $newRaw),
                'changed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        PersonalRequisitionChangeLog::query()->insert($rows);
    }

    private function valuesAreEqual(string $field, mixed $oldRaw, mixed $newRaw): bool
    {
        return $this->normalizeComparable($field, $oldRaw) === $this->normalizeComparable($field, $newRaw);
    }

    private function normalizeComparable(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (in_array($field, [
            'base_salary',
            'transport_allowance',
            'mobility_allowance',
            'statutory_bonus',
            'non_statutory_bonus',
        ], true) && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        if ($field === 'quantity' && is_numeric($value)) {
            return (string) (int) $value;
        }

        if (str_ends_with($field, '_id') && is_numeric($value)) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    private function formatDisplayValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'sex') {
            return self::SEX_LABELS[(string) $value] ?? (string) $value;
        }

        if ($field === 'operating_area_key') {
            return config('access.areas.'.(string) $value, (string) $value);
        }

        if ($field === 'status') {
            return PersonalRequisition::statuses()[(string) $value] ?? (string) $value;
        }

        if ($field === 'hiring_date') {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return (string) $value;
        }

        if (in_array($field, [
            'base_salary',
            'transport_allowance',
            'mobility_allowance',
            'statutory_bonus',
            'non_statutory_bonus',
        ], true) && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        if (isset(self::FK_MODELS[$field])) {
            return $this->resolveForeignLabel($field, $value);
        }

        return trim((string) $value);
    }

    private function resolveForeignLabel(string $field, mixed $value): string
    {
        $id = (int) $value;
        $cacheKey = "{$field}:{$id}";

        if (array_key_exists($cacheKey, $this->fkLabelCache)) {
            return $this->fkLabelCache[$cacheKey] ?? '—';
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
        $modelClass = self::FK_MODELS[$field];
        $label = $modelClass::query()->whereKey($id)->value('name');

        $this->fkLabelCache[$cacheKey] = $label ? (string) $label : "ID {$id}";

        return $this->fkLabelCache[$cacheKey];
    }
}
