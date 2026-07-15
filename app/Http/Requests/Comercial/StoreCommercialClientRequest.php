<?php

namespace App\Http\Requests\Comercial;

use App\Models\CommercialClient;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommercialClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nit')) {
            $this->merge([
                'nit' => CommercialClient::normalizeNit($this->input('nit')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:32', 'unique:commercial_clients,nit'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'legal_rep_name' => ['nullable', 'string', 'max:255'],
            'legal_rep_doc' => ['nullable', 'string', 'max:50'],
        ];
    }
}
