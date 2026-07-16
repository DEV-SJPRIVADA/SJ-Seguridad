<?php

namespace App\Http\Requests\Requisitions\Concerns;

use App\Services\Requisitions\CommercialClientBridge;
use Illuminate\Validation\Rule;

trait ResolvesCommercialClient
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('commercial_client_id')) {
            $this->merge([
                'client_id' => CommercialClientBridge::resolve($this->integer('commercial_client_id')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function commercialClientRules(): array
    {
        return [
            'commercial_client_id' => ['required', 'integer', Rule::exists('commercial_clients', 'id')],
            'client_id' => ['required', 'integer', Rule::exists('requisition_clients', 'id')],
        ];
    }
}
