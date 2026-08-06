<?php

namespace App\Services\Comercial;

use App\Models\CommercialService;
use App\Support\CommercialDocumentCatalog;
use Illuminate\Support\Carbon;

class CommercialMatrixImportRowMapper
{
    /**
     * @return array<string, mixed>
     */
    public function mapRow(CommercialService $service): array
    {
        $client = $service->client;
        $itemsByKey = $client?->documentItems->keyBy('document_key') ?? collect();

        $row = [
            'nit' => $client?->nit,
            'client_name' => $client?->name,
            'phone' => $client?->phone,
            'address' => $client?->address,
            'city' => $client?->city,
            'legal_rep_name' => $client?->legal_rep_name,
            'legal_rep_doc' => $client?->legal_rep_doc,
            'documentation_expires_on' => $this->formatIsoDate($client?->documentation_expires_on),
            'alert_days_before' => $client?->alert_days_before ?? CommercialDocumentCatalog::DEFAULT_ALERT_DAYS,
            'portfolio' => $service->portfolio,
            'contract_number' => $service->contract_number,
            'advisor_name' => $service->advisor_name,
            'sector' => $service->sector?->name,
            'client_type' => $service->clientType?->name,
            'service_type' => $service->serviceType?->name,
            'service_description' => $service->service_description,
            'contact_name' => $service->contact_name,
            'contact_role' => $service->contact_role,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'contract_start' => $this->formatIsoDate($service->contract_start),
            'contract_end' => $this->formatIsoDate($service->contract_end),
            'duration_months' => $service->duration_months,
            'is_active' => $service->is_active ? 1 : 0,
        ];

        foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
            $status = $itemsByKey->get($documentKey)?->status;
            $row[$documentKey] = $status !== null
                ? CommercialDocumentCatalog::statusLabel($status)
                : null;
        }

        return $row;
    }

    private function formatIsoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
