<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningDofa;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaneacionDofaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlaneacionEstrategicaAccessService::class)->canEditEstrategia($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'period_label' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'status' => ['sometimes', Rule::in([
                QualityPlanningDofa::STATUS_DRAFT,
                QualityPlanningDofa::STATUS_ACTIVE,
            ])],
            'quadrants.fortaleza' => ['nullable', 'array'],
            'quadrants.fortaleza.*' => ['nullable', 'string', 'max:500'],
            'quadrants.oportunidad' => ['nullable', 'array'],
            'quadrants.oportunidad.*' => ['nullable', 'string', 'max:500'],
            'quadrants.debilidad' => ['nullable', 'array'],
            'quadrants.debilidad.*' => ['nullable', 'string', 'max:500'],
            'quadrants.amenaza' => ['nullable', 'array'],
            'quadrants.amenaza.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Escriba el título del DOFA.',
            'period_label.required' => 'Indique la etiqueta del periodo (por ejemplo: 2026, T1 2026 o 2026-2028).',
            'ends_on.after_or_equal' => 'La fecha final debe ser igual o posterior al inicio.',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function quadrants(): array
    {
        $result = [];
        $input = $this->validated('quadrants') ?? [];

        foreach (['fortaleza', 'oportunidad', 'debilidad', 'amenaza'] as $quadrant) {
            $rows = is_array($input[$quadrant] ?? null) ? $input[$quadrant] : [];
            $result[$quadrant] = array_values(array_filter(
                array_map(fn ($value): string => trim((string) $value), $rows),
                fn (string $value): bool => $value !== '',
            ));
        }

        return $result;
    }
}
