<?php

namespace App\Http\Requests\Requisitions\Concerns;

use App\Services\Requisitions\CommercialClientBridge;
use Illuminate\Validation\Rule;

trait ResolvesCommercialClient
{
    protected function prepareForValidation(): void
    {
        if ($this->isInternalClientType()) {
            $this->merge([
                'commercial_client_id' => null,
                'client_id' => CommercialClientBridge::resolveInternalClientId(),
            ]);

            return;
        }

        $clientTypeId = is_numeric($this->input('client_type_id')) ? (int) $this->input('client_type_id') : null;

        if ($clientTypeId !== null && ! CommercialClientBridge::requiresCommercialClientSelection($clientTypeId)) {
            $this->merge([
                'commercial_client_id' => null,
                'client_id' => CommercialClientBridge::resolveInternalClientId(),
            ]);

            return;
        }

        if ($this->filled('commercial_client_id')) {
            $this->merge([
                'client_id' => CommercialClientBridge::resolve($this->integer('commercial_client_id')),
            ]);
        }
    }

    protected function isInternalClientType(): bool
    {
        $clientTypeId = $this->input('client_type_id');

        return CommercialClientBridge::isInternalClientType(
            is_numeric($clientTypeId) ? (int) $clientTypeId : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function commercialClientRules(): array
    {
        $clientTypeId = is_numeric($this->input('client_type_id')) ? (int) $this->input('client_type_id') : null;
        $requiresCommercialClient = CommercialClientBridge::requiresCommercialClientSelection($clientTypeId);

        return [
            'commercial_client_id' => [
                Rule::requiredIf($requiresCommercialClient),
                'nullable',
                'integer',
                Rule::exists('commercial_clients', 'id'),
            ],
            'client_id' => ['required', 'integer', Rule::exists('requisition_clients', 'id')],
        ];
    }
}
