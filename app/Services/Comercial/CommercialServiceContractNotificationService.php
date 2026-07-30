<?php

namespace App\Services\Comercial;

use App\Mail\CommercialServiceContractExpiringDigestMail;
use App\Models\CommercialService;
use App\Models\CommercialServiceContractNotificationLog;
use App\Models\NotificationType;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommercialServiceContractNotificationService
{
    public function __construct(
        private readonly NotificationConfigService $notificationConfig,
    ) {}

    /**
     * @return list<array{
     *     commercial_service_id: int,
     *     nit: string,
     *     client_name: string,
     *     contract_number: ?string,
     *     contract_end: Carbon,
     *     days_remaining: int,
     *     edit_url: string,
     * }>
     */
    public function collectCandidates(Carbon $asOf): array
    {
        $asOf = $asOf->copy()->startOfDay();

        $services = CommercialService::query()
            ->with('client')
            ->filterByContractEstado('expiring', $asOf)
            ->orderBy('contract_end')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($services as $service) {
            if ($this->alreadyNotified($service)) {
                continue;
            }

            $rows[] = $this->rowForService($service, $asOf);
        }

        return $rows;
    }

    /**
     * @return int Number of services included in the digest (0 if nothing sent).
     */
    public function sendDigest(Carbon $asOf, bool $dryRun = false): int
    {
        $asOf = $asOf->copy()->startOfDay();
        $candidates = $this->collectCandidates($asOf);

        if ($candidates === []) {
            return 0;
        }

        if ($dryRun) {
            return count($candidates);
        }

        $recipients = $this->notificationConfig->recipientEmails(
            NotificationType::MODULE_COMERCIAL,
            NotificationType::SLUG_SERVICE_CONTRACT_EXPIRING
        );

        Mail::to($recipients)->send(new CommercialServiceContractExpiringDigestMail($asOf, $candidates));

        $notifiedAt = now();

        DB::transaction(function () use ($candidates, $notifiedAt): void {
            foreach ($candidates as $row) {
                CommercialServiceContractNotificationLog::query()->create([
                    'commercial_service_id' => $row['commercial_service_id'],
                    'contract_end' => $row['contract_end'],
                    'notified_at' => $notifiedAt,
                ]);
            }
        });

        return count($candidates);
    }

    private function alreadyNotified(CommercialService $service): bool
    {
        if (! $service->contract_end instanceof Carbon) {
            return true;
        }

        return CommercialServiceContractNotificationLog::query()
            ->where('commercial_service_id', $service->id)
            ->whereDate('contract_end', $service->contract_end)
            ->exists();
    }

    /**
     * @return array{
     *     commercial_service_id: int,
     *     nit: string,
     *     client_name: string,
     *     contract_number: ?string,
     *     contract_end: Carbon,
     *     days_remaining: int,
     *     edit_url: string,
     * }
     */
    private function rowForService(CommercialService $service, Carbon $asOf): array
    {
        $contractEnd = $service->contract_end->copy()->startOfDay();

        return [
            'commercial_service_id' => $service->id,
            'nit' => $service->client?->nit ?? '',
            'client_name' => $service->client?->name ?? '',
            'contract_number' => $service->contract_number,
            'contract_end' => $contractEnd,
            'days_remaining' => (int) $asOf->diffInDays($contractEnd, false),
            'edit_url' => route('comercial.matriz.services.edit', $service),
        ];
    }
}
