<?php

namespace App\Mail;

use App\Models\PersonalRequisition;
use App\Services\Requisitions\RequisitionEmailApprovalUrlBuilder;
use App\Support\ApplicationUrls;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalRequisitionManagementApprovalMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public PersonalRequisition $requisition,
        public int $totalQuantity = 1,
    ) {
        $this->requisition->load(['position', 'client', 'requester', 'requestReason']);
    }

    public function envelope(): Envelope
    {
        $subject = 'Autorizacion requerida — Requisicion '.$this->requisition->code;

        if ($this->totalQuantity > 1) {
            $subject .= ' ('.$this->totalQuantity.' vacantes)';
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $urlBuilder = app(RequisitionEmailApprovalUrlBuilder::class);

        return new Content(
            markdown: 'emails.requisitions.management-approval',
            with: [
                'emailApprovalUrl' => $urlBuilder->showUrl($this->requisition),
                'platformUrl' => ApplicationUrls::route('requisitions.management-approval.show', [
                    'module' => 'gestion_humana',
                    'requisition' => $this->requisition->id,
                ]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
