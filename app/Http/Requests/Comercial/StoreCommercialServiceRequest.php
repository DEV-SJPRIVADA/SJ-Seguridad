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

    public function rules(): array
    {
        return $this->serviceRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function serviceRules(): array
    {
        $docValues = array_keys(CommercialService::documentStatuses());

        $rules = [
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

        foreach (array_keys(CommercialService::documentFields()) as $field) {
            $rules[$field] = ['nullable', 'string', Rule::in($docValues)];
        }

        return $rules;
    }
}
