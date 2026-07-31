<?php

namespace App\Services\PurchaseRequests;

use App\Mail\PurchaseRequestApprovedForComprasMail;
use App\Mail\PurchaseRequestCreatedMail;
use App\Mail\PurchaseRequestProcessedMail;
use App\Mail\PurchaseRequestResolvedMail;
use App\Models\PurchaseRequest;
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
        try {
            Mail::to($director->email)->queue(new PurchaseRequestCreatedMail($purchaseRequest, $director));

            return true;
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar correo al director', [
                'purchase_request_id' => $purchaseRequest->id,
                'director_id' => $director->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyRequesterResolved(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing('user');

        if ($purchaseRequest->user === null) {
            return;
        }

        Mail::to($purchaseRequest->user->email)->queue(new PurchaseRequestResolvedMail($purchaseRequest));
    }

    public function notifyComprasApproved(PurchaseRequest $purchaseRequest): void
    {
        $recipients = $this->notificationConfig->recipientEmails(
            'purchase_requests',
            'purchase_request_approved_for_compras',
        );

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new PurchaseRequestApprovedForComprasMail($purchaseRequest));
        }
    }

    public function notifyRequesterProcessed(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing('user');

        if ($purchaseRequest->user === null) {
            return;
        }

        Mail::to($purchaseRequest->user->email)->queue(new PurchaseRequestProcessedMail($purchaseRequest));
    }
}
