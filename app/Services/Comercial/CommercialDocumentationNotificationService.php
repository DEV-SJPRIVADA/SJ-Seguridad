<?php

namespace App\Services\Comercial;

use App\Mail\CommercialDocumentationDigestMail;
use App\Models\CommercialClient;
use App\Models\CommercialClientDocumentationNotificationLog;
use App\Models\NotificationType;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommercialDocumentationNotificationService
{
    public function __construct(
        private readonly NotificationConfigService $notificationConfig,
    ) {}

    /**
     * @return list<array{
     *     commercial_client_id: int,
     *     nit: string,
     *     name: string,
     *     documentation_expires_on: Carbon,
     *     status: string,
     *     status_label: string,
     *     days_remaining: ?int,
     *     checklist_url: string,
     *     alert_kind: string,
     * }>
     */
    public function collectCandidates(Carbon $asOf): array
    {
        $asOf = $asOf->copy()->startOfDay();
        $rows = collect();

        $expiredClients = CommercialClient::query()
            ->documentationExpired($asOf)
            ->orderBy('documentation_expires_on')
            ->orderBy('name')
            ->get();

        foreach ($expiredClients as $client) {
            if ($this->alreadyNotified($client, CommercialClientDocumentationNotificationLog::KIND_EXPIRED)) {
                continue;
            }

            $rows->push($this->rowForClient($client, CommercialClientDocumentationNotificationLog::KIND_EXPIRED, $asOf));
        }

        $expiringClients = CommercialClient::query()
            ->documentationExpiring($asOf)
            ->orderBy('documentation_expires_on')
            ->orderBy('name')
            ->get();

        foreach ($expiringClients as $client) {
            if ($this->alreadyNotified($client, CommercialClientDocumentationNotificationLog::KIND_EXPIRING)) {
                continue;
            }

            $rows->push($this->rowForClient($client, CommercialClientDocumentationNotificationLog::KIND_EXPIRING, $asOf));
        }

        return $rows
            ->sortBy(fn (array $row): array => [
                $row['alert_kind'] === CommercialClientDocumentationNotificationLog::KIND_EXPIRED ? 0 : 1,
                $row['documentation_expires_on']->format('Y-m-d'),
                $row['name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return int Number of clients included in the digest (0 if nothing sent).
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
            NotificationType::SLUG_DOCUMENTATION_EXPIRING
        );

        Mail::to($recipients)->send(new CommercialDocumentationDigestMail($asOf, $candidates));

        $notifiedAt = now();

        DB::transaction(function () use ($candidates, $notifiedAt): void {
            foreach ($candidates as $row) {
                CommercialClientDocumentationNotificationLog::query()->create([
                    'commercial_client_id' => $row['commercial_client_id'],
                    'documentation_expires_on' => $row['documentation_expires_on'],
                    'alert_kind' => $row['alert_kind'],
                    'notified_at' => $notifiedAt,
                ]);
            }
        });

        return count($candidates);
    }

    private function alreadyNotified(CommercialClient $client, string $alertKind): bool
    {
        if (! $client->documentation_expires_on instanceof Carbon) {
            return true;
        }

        return CommercialClientDocumentationNotificationLog::query()
            ->where('commercial_client_id', $client->id)
            ->whereDate('documentation_expires_on', $client->documentation_expires_on)
            ->where('alert_kind', $alertKind)
            ->exists();
    }

    /**
     * @return array{
     *     commercial_client_id: int,
     *     nit: string,
     *     name: string,
     *     documentation_expires_on: Carbon,
     *     status: string,
     *     status_label: string,
     *     days_remaining: ?int,
     *     checklist_url: string,
     *     alert_kind: string,
     * }
     */
    private function rowForClient(CommercialClient $client, string $alertKind, Carbon $asOf): array
    {
        $expiresOn = $client->documentation_expires_on->copy()->startOfDay();
        $isExpired = $alertKind === CommercialClientDocumentationNotificationLog::KIND_EXPIRED;

        $checklistQuery = $isExpired
            ? ['doc_vigencia' => 'expired']
            : ['doc_vigencia' => 'expiring'];

        $daysRemaining = $isExpired
            ? null
            : (int) $asOf->diffInDays($expiresOn, false);

        return [
            'commercial_client_id' => $client->id,
            'nit' => $client->nit,
            'name' => $client->name,
            'documentation_expires_on' => $expiresOn,
            'status' => $alertKind,
            'status_label' => $isExpired ? 'Vencida' : 'Por vencer',
            'days_remaining' => $daysRemaining,
            'checklist_url' => route('comercial.matriz.clients.checklist.index', $checklistQuery),
            'alert_kind' => $alertKind,
        ];
    }
}
