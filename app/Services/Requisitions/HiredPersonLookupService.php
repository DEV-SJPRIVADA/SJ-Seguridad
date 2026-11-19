<?php

namespace App\Services\Requisitions;

use App\Models\EmployeeFichaProfile;
use App\Services\GestionHumana\EmployeeFichaNameParser;

/**
 * Precarga de «Datos de la persona contratada» cuando la cédula ya está
 * desvinculada en Ficha (reingreso). No incluye fecha de ingreso.
 */
class HiredPersonLookupService
{
    /**
     * @return array{
     *     found: bool,
     *     hired_birth_date?: ?string,
     *     hired_sex?: ?string,
     *     hired_first_surname?: ?string,
     *     hired_second_surname?: ?string,
     *     hired_first_name?: ?string,
     *     hired_second_name?: ?string
     * }
     */
    public function lookupDesvinculado(string $document): array
    {
        $document = preg_replace('/\D+/', '', $document) ?? '';

        if (strlen($document) < 5) {
            return ['found' => false];
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $document)
            ->where('employment_status', EmployeeFichaProfile::STATUS_DESVINCULADO)
            ->with('fichaEntry')
            ->orderByDesc('id')
            ->first();

        if ($profile === null) {
            return ['found' => false];
        }

        $entry = $profile->fichaEntry;
        $names = $this->resolveNameParts($profile, $entry?->hired_full_name);

        return [
            'found' => true,
            'hired_birth_date' => $profile->birth_date?->format('Y-m-d'),
            'hired_sex' => $this->mapSexToHiredForm($profile->sex),
            'hired_first_surname' => $names['first_surname'],
            'hired_second_surname' => $names['second_surname'],
            'hired_first_name' => $names['first_name'],
            'hired_second_name' => $names['second_name'],
        ];
    }

    /**
     * @return array{first_surname: ?string, second_surname: ?string, first_name: ?string, second_name: ?string}
     */
    private function resolveNameParts(EmployeeFichaProfile $profile, ?string $entryFullName): array
    {
        $firstSurname = trim((string) ($profile->first_surname ?: $profile->fichaEntry?->first_surname));
        $secondSurname = trim((string) ($profile->second_surname ?: $profile->fichaEntry?->second_surname));
        $firstName = trim((string) ($profile->first_name ?: $profile->fichaEntry?->first_name));
        $secondName = trim((string) ($profile->second_name ?: $profile->fichaEntry?->second_name));

        if ($firstSurname !== '' || $firstName !== '') {
            return [
                'first_surname' => $firstSurname !== '' ? $firstSurname : null,
                'second_surname' => $secondSurname !== '' ? $secondSurname : null,
                'first_name' => $firstName !== '' ? $firstName : null,
                'second_name' => $secondName !== '' ? $secondName : null,
            ];
        }

        $parsed = EmployeeFichaNameParser::parse((string) ($profile->full_name ?: $entryFullName));

        return [
            'first_surname' => $parsed['first_surname'],
            'second_surname' => $parsed['second_surname'],
            'first_name' => $parsed['first_name'],
            'second_name' => $parsed['second_name'],
        ];
    }

    private function mapSexToHiredForm(?string $sex): ?string
    {
        return match (mb_strtolower(trim((string) $sex))) {
            'm', 'masculino' => 'masculino',
            'f', 'femenino' => 'femenino',
            default => null,
        };
    }
}
