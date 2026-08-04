<?php

namespace App\Services\PurchaseRequests;

use App\Mail\PurchaseRequestApprovedForComprasMail;
use App\Mail\PurchaseRequestCreatedMail;
use App\Mail\PurchaseRequestProcessedMail;
use App\Mail\PurchaseRequestResolvedMail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestMailLog;
use App\Models\User;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PurchaseRequestNotificationService
{
    public function __construct(
        private readonly NotificationConfigService $notificationConfig,
    ) {}

    public function notifyDirectorAssigned(PurchaseRequest $purchaseRequest, User $director): bool
    {
        return $this->sendToRecipient(
            $purchaseRequest,
            $director->email,
            new PurchaseRequestCreatedMail($purchaseRequest, $director),
            PurchaseRequestMailLog::TYPE_DIRECTOR_ASSIGNED,
            'PDF adjunto + enlace de aprobacion por correo',
        );
    }

    public function notifyRequesterResolved(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing('user');

        if ($purchaseRequest->user === null) {
            return;
        }

        $this->sendToRecipient(
            $purchaseRequest,
            $purchaseRequest->user->email,
            new PurchaseRequestResolvedMail($purchaseRequest),
            PurchaseRequestMailLog::TYPE_REQUESTER_RESOLVED,
            'Estado: '.$purchaseRequest->estado,
        );
    }

    public function notifyComprasApproved(PurchaseRequest $purchaseRequest): void
    {
        $recipients = $this->notificationConfig->recipientEmails(
            'purchase_requests',
            'purchase_request_approved_for_compras',
        );

        foreach ($recipients as $email) {
            $this->sendToRecipient(
                $purchaseRequest,
                $email,
                new PurchaseRequestApprovedForComprasMail($purchaseRequest),
                PurchaseRequestMailLog::TYPE_COMPRAS_APPROVED,
                'Bandeja: solicitud aprobada por director',
            );
        }
    }

    public function notifyRequesterProcessed(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing('user');

        if ($purchaseRequest->user === null) {
            return;
        }

        $this->sendToRecipient(
            $purchaseRequest,
            $purchaseRequest->user->email,
            new PurchaseRequestProcessedMail($purchaseRequest),
            PurchaseRequestMailLog::TYPE_REQUESTER_PROCESSED,
            'Estado compras: '.($purchaseRequest->estado_compras ?? '—'),
        );
    }

    private function sendToRecipient(
        PurchaseRequest $purchaseRequest,
        string $recipientEmail,
        object $mailable,
        string $mailType,
        ?string $detail = null,
    ): bool {
        try {
            Mail::to($recipientEmail)->send($mailable);
            $this->logMail(
                $purchaseRequest,
                $mailType,
                $recipientEmail,
                PurchaseRequestMailLog::STATUS_ENVIADO,
                $detail,
            );

            return true;
        } catch (\Throwable $exception) {
            $this->logMail(
                $purchaseRequest,
                $mailType,
                $recipientEmail,
                PurchaseRequestMailLog::STATUS_FALLIDO,
                $exception->getMessage(),
            );

            Log::warning('No se pudo enviar correo de solicitud de compra', [
                'purchase_request_id' => $purchaseRequest->id,
                'mail_type' => $mailType,
                'recipient' => $recipientEmail,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function logMail(
        PurchaseRequest $purchaseRequest,
        string $mailType,
        string $recipientEmail,
        string $status,
        ?string $detail = null,
    ): void {
        PurchaseRequestMailLog::query()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'mail_type' => $mailType,
            'recipient_email' => $recipientEmail,
            'status' => $status,
            'detail' => $detail,
            'sent_at' => now(),
        ]);
    }
}
