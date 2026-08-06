<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use App\Support\ApplicationUrls;
use Illuminate\Support\Carbon;

final class RequisitionEmailApprovalUrlBuilder
{
    public function expiresAt(): Carbon
    {
        return now()->addDays(config('requisitions.email_approval_link_days'));
    }

    public function showUrl(PersonalRequisition $requisition): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'requisitions.email-approval.show',
            $this->expiresAt(),
            ['requisition' => $requisition->id],
        );
    }

    public function updateUrl(PersonalRequisition $requisition): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'requisitions.email-approval.update',
            $this->expiresAt(),
            ['requisition' => $requisition->id],
        );
    }
}
