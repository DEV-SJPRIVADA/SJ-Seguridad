<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use App\Services\Calidad\Planeacion\FormulaEvaluator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlaneacionIndicatorRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlaneacionEstrategicaAccessService::class)->canEditIndicadores($user);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['code', 'formula', 'process_key', 'target_operator', 'frequency', 'unit', 'fields'];
    }

    protected function prepareForValidation(): void
    {
        $nullable = [];
        foreach (['process_key', 'critical_value', 'objective', 'insumos', 'medicion'] as $key) {
            if ($this->input($key) === '') {
                $nullable[$key] = null;
            }
        }
        if ($nullable !== []) {
            $this->merge($nullable);
        }

        $fields = $this->input('fields', []);

        if (! is_array($fields)) {
            return;
        }

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $fields[$index]['required'] = $this->boolean('fields.'.$index.'.required');
            $fields[$index]['decimals'] = (int) ($field['decimals'] ?? 2);
        }

        $this->merge(['fields' => array_values($fields)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $processKeys = array_keys(config('quality-documents.processes', []));

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique((new QualityPlanningIndicator)->getTable(), 'code')],
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'insumos' => ['nullable', 'string', 'max:2000'],
            'medicion' => ['nullable', 'string', 'max:2000'],
            'process_key' => ['nullable', 'string', Rule::in($processKeys)],
            'unit' => ['required', 'string', Rule::in(['percentage', 'number'])],
            'frequency' => ['required', 'string', Rule::in([QualityPlanningIndicatorVersion::FREQUENCY_MONTHLY])],
            'target_value' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'target_operator' => ['required', 'string', Rule::in(['>=', '<=', '>', '<', '='])],
            'critical_value' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'formula' => ['required', 'string', 'max:2000'],
            'fields' => ['required', 'array', 'min:2', 'max:12'],
            'fields.*.key' => ['required', 'string', 'distinct', 'regex:/^[a-z][a-z0-9_]*$/', 'max:40'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.decimals' => ['required', 'integer', Rule::in([0, 2])],
            'fields.*.required' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Ya existe un indicador con ese código.',
            'fields.min' => 'Defina entre 2 y 12 campos.',
            'fields.max' => 'Defina entre 2 y 12 campos.',
            'fields.*.key.regex' => 'La clave del campo debe empezar con una letra minúscula y solo usar letras, números o guion bajo.',
            'fields.*.key.distinct' => 'Las claves de los campos no pueden repetirse.',
            'fields.*.label.required' => 'Cada campo necesita una etiqueta.',
        ];
    }

    /**
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $keys = collect($this->input('fields', []))
                    ->pluck('key')
                    ->filter()
                    ->map(fn ($key) => (string) $key)
                    ->all();

                $errors = app(FormulaEvaluator::class)->validate((string) $this->input('formula'), $keys);

                foreach ($errors as $error) {
                    $validator->errors()->add('formula', $error);
                }
            },
        ];
    }
}
