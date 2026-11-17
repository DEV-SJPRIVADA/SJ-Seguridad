<?php

namespace App\Services\GestionHumana;

use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionPositionPayrollMap;

/**
 * Export de la lista Pendientes con las columnas de la plantilla reimportable (nompr07):
 * parte de lo ya guardado en el perfil (carta de contratación rápida o Gestionar empleado)
 * y completa desde la requisición solo las celdas que el perfil todavía no tiene.
 * El export de En ficha sigue sin fallbacks de requisición.
 */
class PendingEmployeeFichaImportRowMapper
{
    public function __construct(
        private readonly EmployeeFichaImportRowMapper $rowMapper,
        private readonly EmployeeFichaImportValueNormalizer $valueNormalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function mapRow(PersonalRequisitionFichaEntry $entry): array
    {
        $entry->loadMissing(['profile', 'requisition.position']);

        $row = $this->rowMapper->mapRow($entry);
        $requisition = $entry->requisition;

        if ($requisition === null) {
            return $row;
        }

        // Solo se rellenan celdas vacías: el dato del perfil siempre manda.
        $this->fillMissing($row, 'fecha_nac', $requisition->hired_birth_date?->toDateString());
        $this->fillMissing($row, 'fecha_ingreso', $requisition->hiring_date?->toDateString());
        $this->fillMissing($row, 'salario', $requisition->base_salary);
        $this->fillMissing($row, 'sexo', $this->valueNormalizer->sex($requisition->sex));
        $this->fillMissing($row, 'cargo', $this->payrollPositionCode($requisition->position_id));
        $this->fillMissing($row, 'nombre_cargo', $requisition->position?->name);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fillMissing(array &$row, string $key, mixed $value): void
    {
        if (! array_key_exists($key, $row)) {
            return;
        }

        $current = $row[$key];

        if ($current !== null && $current !== '') {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $row[$key] = $value;
    }

    private function payrollPositionCode(?int $requisitionPositionId): ?string
    {
        if ($requisitionPositionId === null) {
            return null;
        }

        return RequisitionPositionPayrollMap::query()
            ->where('requisition_position_id', $requisitionPositionId)
            ->value('payroll_position_code');
    }
}
