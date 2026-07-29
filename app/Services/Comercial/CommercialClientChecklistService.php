<?php

namespace App\Services\Comercial;

use App\Models\CommercialClient;
use App\Models\CommercialClientDocumentItem;
use App\Models\CommercialService;
use App\Support\CommercialDocumentCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CommercialClientChecklistService
{
    public function ensureItemsForClient(CommercialClient $client): void
    {
        foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
            CommercialClientDocumentItem::query()->firstOrCreate(
                [
                    'commercial_client_id' => $client->id,
                    'document_key' => $documentKey,
                ],
                ['status' => CommercialDocumentCatalog::DOC_PENDING]
            );
        }
    }

    public function ensureItemsForAllClients(): void
    {
        CommercialClient::query()->each(fn (CommercialClient $client) => $this->ensureItemsForClient($client));
    }

    /**
     * @param  array<string, string|null>  $statusByKey
     */
    public function applyImportedDocumentStatuses(CommercialClient $client, array $statusByKey, ?Carbon $expiresOn): void
    {
        $this->ensureItemsForClient($client);

        foreach ($statusByKey as $documentKey => $status) {
            if (! in_array($documentKey, CommercialDocumentCatalog::documentKeys(), true)) {
                continue;
            }

            if ($status === null) {
                continue;
            }

            CommercialClientDocumentItem::query()
                ->where('commercial_client_id', $client->id)
                ->where('document_key', $documentKey)
                ->update(['status' => $status]);
        }

        if ($expiresOn !== null) {
            $current = $client->documentation_expires_on;
            $next = $current instanceof Carbon
                ? $current->copy()->startOfDay()
                : null;

            if ($next === null || $expiresOn->lt($next)) {
                $client->documentation_expires_on = $expiresOn;
                $client->save();
            }
        }
    }

    /**
     * @param  Collection<int, Carbon>  $candidateDates
     */
    public function mergeClientDocumentationExpiry(CommercialClient $client, Collection $candidateDates): void
    {
        $dates = $candidateDates
            ->filter(fn ($date) => $date instanceof Carbon)
            ->map(fn (Carbon $date) => $date->copy()->startOfDay());

        if ($client->documentation_expires_on instanceof Carbon) {
            $dates->push($client->documentation_expires_on->copy()->startOfDay());
        }

        if ($dates->isEmpty()) {
            return;
        }

        $client->documentation_expires_on = $dates->sortBy(fn (Carbon $d) => $d->timestamp)->first();
        if ($client->alert_days_before === null) {
            $client->alert_days_before = CommercialDocumentCatalog::DEFAULT_ALERT_DAYS;
        }
        $client->save();
    }

    public function migrateFromLegacyServiceColumns(): void
    {
        if (! $this->legacyDocumentColumnsExist()) {
            $this->ensureItemsForAllClients();

            return;
        }

        CommercialClient::query()->with(['services' => function ($query): void {
            $query->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS);
        }])->each(function (CommercialClient $client): void {
            $this->ensureItemsForClient($client);

            $activeServices = $client->services;

            foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
                $winner = $activeServices
                    ->filter(fn (CommercialService $service) => $service->{$documentKey} !== null)
                    ->sortByDesc('updated_at')
                    ->first();

                if ($winner === null) {
                    continue;
                }

                CommercialClientDocumentItem::query()
                    ->where('commercial_client_id', $client->id)
                    ->where('document_key', $documentKey)
                    ->update(['status' => $winner->{$documentKey}]);
            }

            $expiryDates = collect();
            foreach ($activeServices as $service) {
                foreach ($this->legacyExpiryMeta() as $documentKey => $meta) {
                    if (! $service->{$meta['tracks']}) {
                        continue;
                    }
                    $expires = $service->{$meta['expires']};
                    if ($expires instanceof Carbon) {
                        $expiryDates->push($expires->copy()->startOfDay());
                    }
                }
            }

            if ($expiryDates->isNotEmpty()) {
                $client->documentation_expires_on = $expiryDates->sortBy(fn (Carbon $d) => $d->timestamp)->first();
                $client->alert_days_before = $client->alert_days_before ?? CommercialDocumentCatalog::DEFAULT_ALERT_DAYS;
                $client->save();
            }
        });
    }

    public function legacyDocumentColumnsExist(): bool
    {
        return Schema::hasColumn('commercial_services', 'doc_economic_proposal');
    }

    /**
     * @return array<string, array{tracks: string, expires: string}>
     */
    private function legacyExpiryMeta(): array
    {
        $map = [];
        foreach (CommercialDocumentCatalog::documentKeys() as $field) {
            $map[$field] = [
                'tracks' => "{$field}_tracks_expiry",
                'expires' => "{$field}_expires_on",
            ];
        }

        return $map;
    }
}
