<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PurchaseRequestCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public User $director,
    ) {
        $this->purchaseRequest->loadMissing(['user', 'items', 'aprobador']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de compra N.º '.$this->purchaseRequest->folio().' pendiente de autorizacion',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.created',
            with: [
                'approvalUrl' => URL::temporarySignedRoute(
                    'purchase-requests.email-approval.show',
                    now()->addDays(config('purchase-requests.email_approval_link_days', 7)),
                    ['purchase_request' => $this->purchaseRequest->id, 'director' => $this->director->id],
                ),
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
