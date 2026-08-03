<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseEmailApprovalUrlBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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
        $urlBuilder = app(PurchaseEmailApprovalUrlBuilder::class);

        return new Content(
            markdown: 'emails.purchase-requests.created',
            with: [
                'emailApprovalUrl' => $urlBuilder->showUrl($this->purchaseRequest, $this->director),
                'platformUrl' => route('purchase-requests.show', [
                    'module' => $this->purchaseRequest->area_key,
                    'purchase_request' => $this->purchaseRequest->id,
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
