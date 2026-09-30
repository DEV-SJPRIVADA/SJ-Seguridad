<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;

class ReportesNovedadesFichaLookupService
{
    /**
     * Resuelve cédula → datos de Ficha para prefill de novedades.
     *
     * @return array{
     *     found: bool,
     *     message?: string,
     *     document_number?: string,
     *     employee_name?: string,
     *     cargo?: string|null,
     *     destino?: string|null,
     *     tipo?: string|null,
     *     fecha_ingreso?: string|null
     * }
     */
    public function lookupByDocument(string $documentNumber): array
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return [
                'found' => false,
                'message' => 'Ingrese un número de cédula.',
            ];
        }

        $entry = PersonalRequisitionFichaEntry::query()
            ->with(['profile', 'requisition.client', 'requisition.position'])
            ->where(function ($query) use ($documentNumber): void {
                $query->where('hired_document', $documentNumber)
                    ->orWhereHas('profile', static function ($profile) use ($documentNumber): void {
                        $profile->where('document_number', $documentNumber);
                    });
            })
            ->orderByDesc('id')
            ->first();

        if ($entry !== null) {
            $profile = $entry->profile;

            return [
                'found' => true,
                'document_number' => (string) ($entry->hired_document ?: $profile?->document_number ?: $documentNumber),
                'employee_name' => (string) ($entry->hired_full_name ?: $profile?->full_name ?: ''),
                'cargo' => $entry->positionName(),
                'destino' => $entry->clientName(),
                'tipo' => $profile?->contract_type_name,
                'fecha_ingreso' => optional($entry->hireDate())?->toDateString(),
            ];
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($profile === null) {
            return [
                'found' => false,
                'message' => 'No se encontró un empleado en Ficha para esa cédula. Puede completar los datos manualmente.',
            ];
        }

        return [
            'found' => true,
            'document_number' => (string) $profile->document_number,
            'employee_name' => (string) $profile->full_name,
            'cargo' => $profile->position_name,
            'destino' => $profile->work_center_name,
            'tipo' => $profile->contract_type_name,
            'fecha_ingreso' => optional($profile->hire_date)?->toDateString(),
        ];
    }
}
