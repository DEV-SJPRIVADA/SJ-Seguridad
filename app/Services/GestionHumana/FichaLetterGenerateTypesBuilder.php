<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\WordDocumentType;

class FichaLetterGenerateTypesBuilder
{
    /**
     * Tipos activos de Plantillas Word con URLs/disponibilidad para el modal Generar Cartas.
     *
     * @return list<array{
     *     code: string,
     *     name: string,
     *     enabled: bool,
     *     disabled_reason: string|null,
     *     templates_url: string|null,
     *     generate_url: string|null,
     *     firmas_url: string|null
     * }>
     */
    public function build(
        ?EmployeeFichaEmploymentPeriod $activePeriod,
        ?EmployeeFichaEmploymentPeriod $letterPeriod,
        bool $canGenerateContratacionLetters,
        bool $canGenerateLetters,
    ): array {
        $contratacionCode = (string) config('employee_ficha.word_document_type_codes.contratacion');
        $desvinculacionCode = (string) config('employee_ficha.word_document_type_codes.desvinculacion');

        // Tipos genéricos: activo+manage o cerrado+terminate (prioriza activo).
        $genericPeriod = null;
        if ($canGenerateContratacionLetters && $activePeriod !== null) {
            $genericPeriod = $activePeriod;
        } elseif ($canGenerateLetters && $letterPeriod !== null) {
            $genericPeriod = $letterPeriod;
        }

        return WordDocumentType::query()
            ->active()
            ->ordered()
            ->get(['id', 'code', 'name'])
            ->map(function (WordDocumentType $type) use (
                $activePeriod,
                $letterPeriod,
                $canGenerateContratacionLetters,
                $canGenerateLetters,
                $contratacionCode,
                $desvinculacionCode,
                $genericPeriod,
            ): array {
                $code = (string) $type->code;
                $name = (string) $type->name;

                if ($code === $contratacionCode) {
                    if ($canGenerateContratacionLetters && $activePeriod !== null) {
                        return [
                            'code' => $code,
                            'name' => $name,
                            'enabled' => true,
                            'disabled_reason' => null,
                            'templates_url' => route('gestion-humana.ficha-empleados.employees.contratacion.templates', $activePeriod),
                            'generate_url' => route('gestion-humana.ficha-empleados.employees.contratacion.generate', $activePeriod),
                            'firmas_url' => route('gestion-humana.ficha-empleados.employees.contratacion.firmas', $activePeriod),
                        ];
                    }

                    $reason = $activePeriod === null || $activePeriod->status !== EmployeeFichaEmploymentPeriod::STATUS_ACTIVO
                        ? 'Requiere un vínculo laboral activo.'
                        : 'No tiene permiso para generar cartas de contratación.';

                    return [
                        'code' => $code,
                        'name' => $name,
                        'enabled' => false,
                        'disabled_reason' => $reason,
                        'templates_url' => null,
                        'generate_url' => null,
                        'firmas_url' => null,
                    ];
                }

                if ($code === $desvinculacionCode) {
                    if ($canGenerateLetters && $letterPeriod !== null) {
                        return [
                            'code' => $code,
                            'name' => $name,
                            'enabled' => true,
                            'disabled_reason' => null,
                            'templates_url' => route('gestion-humana.ficha-empleados.employees.period.letters.templates', $letterPeriod),
                            'generate_url' => route('gestion-humana.ficha-empleados.employees.period.letters.generate', $letterPeriod),
                            'firmas_url' => route('gestion-humana.ficha-empleados.employees.period.letters.firmas', $letterPeriod),
                        ];
                    }

                    $reason = $letterPeriod === null || $letterPeriod->status !== EmployeeFichaEmploymentPeriod::STATUS_CERRADO
                        ? 'Requiere un vínculo laboral cerrado (desvinculación).'
                        : 'No tiene permiso para generar cartas de desvinculación.';

                    return [
                        'code' => $code,
                        'name' => $name,
                        'enabled' => false,
                        'disabled_reason' => $reason,
                        'templates_url' => null,
                        'generate_url' => null,
                        'firmas_url' => null,
                    ];
                }

                if ($genericPeriod !== null) {
                    return [
                        'code' => $code,
                        'name' => $name,
                        'enabled' => true,
                        'disabled_reason' => null,
                        'templates_url' => route('gestion-humana.ficha-empleados.employees.type-letters.templates', [
                            'period' => $genericPeriod,
                            'typeCode' => $code,
                        ]),
                        'generate_url' => route('gestion-humana.ficha-empleados.employees.type-letters.generate', [
                            'period' => $genericPeriod,
                            'typeCode' => $code,
                        ]),
                        'firmas_url' => route('gestion-humana.ficha-empleados.employees.type-letters.firmas', [
                            'period' => $genericPeriod,
                            'typeCode' => $code,
                        ]),
                    ];
                }

                return [
                    'code' => $code,
                    'name' => $name,
                    'enabled' => false,
                    'disabled_reason' => 'Requiere un vínculo laboral activo o cerrado y el permiso correspondiente.',
                    'templates_url' => null,
                    'generate_url' => null,
                    'firmas_url' => null,
                ];
            })
            ->values()
            ->all();
    }
}
