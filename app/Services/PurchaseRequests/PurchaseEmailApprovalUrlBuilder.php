<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\ApplicationUrls;
use Illuminate\Support\Carbon;

final class PurchaseEmailApprovalUrlBuilder
{
    public function expiresAt(): Carbon
    {
        return now()->addDays(config('purchase-requests.email_approval_link_days'));
    }

    public function showUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.email-approval.show',
            $this->expiresAt(),
            [
                'purchase_request' => $purchaseRequest->id,
                'director' => $director->id,
            ],
        );
    }

    public function updateUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.email-approval.update',
            $this->expiresAt(),
            [
                'purchase_request' => $purchaseRequest->id,
                'director' => $director->id,
            ],
        );
    }

    public function pdfUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.email-approval.pdf',
            $this->expiresAt(),
            [
                'purchase_request' => $purchaseRequest->id,
                'director' => $director->id,
            ],
        );
    }
}
