<?php

namespace App\Http\Requests\Comercial;

use App\Models\CommercialService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommercialServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $duration = $this->input('duration_months');
        if (is_numeric($duration) && (int) $duration > 600) {
            $this->merge(['duration_months' => null]);
        }

        foreach (['contract_start', 'contract_end'] as $dateField) {
            $value = $this->input($dateField);
            if (is_string($value) && $this->isInvalidImportedDate($value)) {
                $this->merge([$dateField => null]);
            }
        }

        $this->normalizeDocumentExpiryInputs();
    }

    public function rules(): array
    {
        return $this->serviceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'duration_months.max' => 'La duracion en meses no puede superar 600.',
            'contract_end.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'commercial_client_id.required' => 'Debe seleccionar un cliente.',
            'commercial_service_type_id.exists' => 'El tipo de servicio seleccionado no es valido.',
        ];

        foreach (CommercialService::documentFields() as $field => $label) {
            $messages["{$field}_expires_on.required"] = "La fecha de vencimiento de {$label} es obligatoria cuando el estado es OK y tiene vencimiento.";
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'duration_months' => 'duracion (meses)',
            'contract_start' => 'inicio contrato',
            'contract_end' => 'fin contrato',
            'commercial_service_type_id' => 'tipo de servicio',
        ];

        foreach (CommercialService::documentFields() as $field => $label) {
            $attributes[$field] = $label;
            $attributes["{$field}_tracks_expiry"] = "tiene vencimiento ({$label})";
            $attributes["{$field}_expires_on"] = "fecha de vencimiento ({$label})";
        }

        return $attributes;
    }

    private function isInvalidImportedDate(string $value): bool
    {
        $year = (int) substr($value, 0, 4);

        return $year > 0 && $year < 1980;
    }

    private function normalizeDocumentExpiryInputs(): void
    {
        $merge = [];

        foreach (CommercialService::documentExpiryFields() as $field => $meta) {
            $status = $this->input($field);
            $tracksKey = $meta['tracks'];
            $expiresKey = $meta['expires'];

            $statusAllowsExpiry = is_string($status)
                && in_array($status, CommercialService::documentStatusesWithExpiry(), true);

            if (! $statusAllowsExpiry) {
                $merge[$tracksKey] = false;
                $merge[$expiresKey] = null;

                continue;
            }

            $tracks = $this->boolean($tracksKey);
            $merge[$tracksKey] = $tracks;

            if (! $tracks) {
                $merge[$expiresKey] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function serviceRules(): array
    {
        $docValues = array_keys(CommercialService::documentStatuses());

        $rules = [
            'commercial_client_id' => ['required', 'integer', Rule::exists('commercial_clients', 'id')],
            'portfolio' => ['required', 'string', Rule::in(array_keys(CommercialService::portfolios()))],
            'contract_number' => ['nullable', 'string', 'max:80'],
            'advisor_name' => ['nullable', 'string', 'max:120'],
            'commercial_sector_id' => ['nullable', 'integer', Rule::exists('commercial_sectors', 'id')],
            'commercial_client_type_id' => ['nullable', 'integer', Rule::exists('commercial_client_types', 'id')],
            'commercial_service_type_id' => ['nullable', 'integer', Rule::exists('commercial_service_types', 'id')],
            'service_description' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'string', 'max:255'],
            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'duration_months' => ['nullable', 'integer', 'min:0', 'max:600'],
        ];

        foreach (CommercialService::documentExpiryFields() as $field => $meta) {
            $rules[$field] = ['nullable', 'string', Rule::in($docValues)];
            $rules[$meta['tracks']] = ['required', 'boolean'];
            $rules[$meta['expires']] = [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->input($field) === CommercialService::DOC_OK
                    && $this->boolean($meta['tracks'])),
            ];
        }

        return $rules;
    }
}
