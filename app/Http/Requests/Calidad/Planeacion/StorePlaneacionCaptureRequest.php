<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StorePlaneacionCaptureRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();
        $indicator = $this->route('indicator');

        if ($user === null || ! $indicator instanceof QualityPlanningIndicator) {
            return false;
        }

        return app(PlaneacionEstrategicaAccessService::class)->canCaptureIndicator($user, $indicator);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['year', 'month', 'input_data', 'replace_confirmed'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'input_data' => ['required', 'array'],
            'input_data.*' => ['nullable', 'numeric'],
            'analysis_text' => ['nullable', 'string', 'max:5000'],
            'improvement_required' => ['nullable', 'string', 'max:5000'],
            'replace_confirmed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'input_data.required' => 'Diligencie los campos de captura.',
            'replace_confirmed.boolean' => 'Confirme el reemplazo de la captura existente.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('replace_confirmed')) {
            $this->merge([
                'replace_confirmed' => $this->boolean('replace_confirmed'),
            ]);
        }
    }
}
