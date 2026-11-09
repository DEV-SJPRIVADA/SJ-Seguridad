<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros + capturas PNG de ApexCharts para el Excel del dashboard GH.
 * No persiste texto: no usa UppercasesValidatedText.
 */
class ExportGestionHumanaAreaDashboardRequest extends FormRequest
{
    public const CHART_IMAGE_KEYS = [
        'evolucion_personal',
        'ingresos_vs_desvinculaciones',
        'evolucion_formacion',
        'personal_por_concepto',
        'estado_psicofisicos',
        'estado_acreditacion',
        'consultas_archivo',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->can('view.board.gestion_humana.dashboard')
            || $user->can('manage.users')
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $imageRules = ['nullable', 'string', 'max:2500000'];
        $rules = [
            'anio' => ['nullable', 'string', 'max:8'],
            'mes' => ['nullable', 'string', 'max:8'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'chart_images' => ['sometimes', 'array'],
        ];

        foreach (self::CHART_IMAGE_KEYS as $key) {
            $rules['chart_images.'.$key] = $imageRules;
        }

        return $rules;
    }
}
