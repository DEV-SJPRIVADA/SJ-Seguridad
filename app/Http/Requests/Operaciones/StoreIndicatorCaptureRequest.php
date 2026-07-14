<?php

namespace App\Http\Requests\Operaciones;

use App\Models\Indicator;
use App\Services\Indicadores\IndicatorMetricCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIndicatorCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operations.capture')
            || $this->user()?->can('operations.manage')
            || false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Indicator $indicator */
        $indicator = $this->route('indicator');
        $calculator = app(IndicatorMetricCalculator::class);
        $form = (array) $this->input('form', []);

        $rules = [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'operations_leader_id' => ['required', 'integer', 'exists:operations_leaders,id'],
            'improvement.analysis' => ['required', 'string'],
            'improvement.action_taken' => ['required', 'string'],
            'improvement.action_defined' => ['required', 'string'],
            'improvement.improvement_required' => ['nullable', 'string'],
            '_open_improvement_modal' => ['sometimes', 'boolean'],
            '_open_classification_modal' => ['sometimes', 'boolean'],
        ];

        return array_merge($rules, $calculator->fieldRules($indicator->code, $form));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Indicator $indicator */
            $indicator = $this->route('indicator');
            $calculator = app(IndicatorMetricCalculator::class);
            $form = app(\App\Services\Indicadores\IndicatorCaptureService::class)
                ->normalizePostedForm($indicator->code, (array) $this->input('form', []));

            $metrics = $calculator->calculate($indicator, $form);

            if (! empty($metrics['errors'])) {
                $validator->errors()->add('form', implode(' | ', $metrics['errors']));
            }

            if (! $metrics['complies'] && trim((string) $this->input('improvement.improvement_required', '')) === '') {
                $validator->errors()->add(
                    'improvement.improvement_required',
                    'Debe agregar mejora es obligatorio cuando no se cumple la meta.'
                );
            }
        });
    }

    /**
     * @return array{analysis: string, action_taken: string, action_defined: string, improvement_required: string|null}
     */
    public function improvementPayload(): array
    {
        return [
            'analysis' => (string) $this->input('improvement.analysis', ''),
            'action_taken' => (string) $this->input('improvement.action_taken', ''),
            'action_defined' => (string) $this->input('improvement.action_defined', ''),
            'improvement_required' => $this->input('improvement.improvement_required'),
        ];
    }
}
