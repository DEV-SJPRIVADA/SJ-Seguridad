<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;

/**
 * Busca empleados en Ficha por cedula para autollenar datos en Cliente interno
 * (modal Nueva solicitud). El nombre sugerido es editable en el formulario.
 */
class ClienteInternoFichaLookupService
{
    /**
     * Prefiere el perfil activo; si hay varios con la misma cedula, el mas reciente.
     *
     * @return array{document_number: string, full_name: string}|null
     */
    public function lookupByDocument(string $documentNumber): ?array
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return null;
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $documentNumber)
            ->orderByRaw(
                'CASE WHEN employment_status = ? THEN 0 ELSE 1 END',
                [EmployeeFichaProfile::STATUS_ACTIVO]
            )
            ->orderByDesc('id')
            ->first();

        if ($profile === null) {
            return null;
        }

        return [
            'document_number' => (string) ($profile->document_number ?: $documentNumber),
            'full_name' => trim((string) $profile->full_name),
        ];
    }
}
