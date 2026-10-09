<?php

namespace App\Exports;

use App\Models\EmployeeFichaProfile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export de la cola Validaciones MT-ST-04 (respeta filtros de la vista).
 */
class MtSt04ValidacionesExport extends BaseExport
{
    /**
     * @param  Collection<int, EmployeeFichaProfile>  $profiles
     */
    public function __construct(Collection $profiles)
    {
        $data = $profiles
            ->map(fn (EmployeeFichaProfile $profile): array => self::mapProfile($profile))
            ->values();

        parent::__construct(
            $data,
            self::columnDefinitions(),
            'mt_st_04_validaciones_'.now()->format('Y-m-d').'.xlsx',
            'MT-ST-04 Validaciones — '.config('app.name'),
        );
    }

    /**
     * @param  Collection<int, EmployeeFichaProfile>  $profiles
     */
    public static function downloadCollection(Collection $profiles): StreamedResponse
    {
        return (new self($profiles))->download();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function columnDefinitions(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'NOMBRE COMPLETO'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'ciudad', 'label' => 'CIUDAD'],
            ['key' => 'puesto', 'label' => 'PUESTO'],
            ['key' => 'requires_psicofisicos', 'label' => 'REQUIERE PSICOFISICOS'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapProfile(EmployeeFichaProfile $profile): array
    {
        $requires = $profile->requires_psicofisicos;

        return [
            'document_number' => $profile->document_number,
            'full_name' => (string) ($profile->full_name ?? ''),
            'cargo' => (string) ($profile->position_name ?? ''),
            'ciudad' => $profile->displayCityName(),
            'puesto' => (string) ($profile->cost_center_name ?? ''),
            'requires_psicofisicos' => $requires === false ? 'NO' : 'SI',
        ];
    }
}
