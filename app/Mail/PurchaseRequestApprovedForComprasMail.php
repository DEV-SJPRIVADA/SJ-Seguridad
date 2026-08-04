<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestApprovedForComprasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest->loadMissing(['user', 'items', 'aprobador']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de compra aprobada N.º '.$this->purchaseRequest->folio(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.approved-for-compras',
        );
    }
}
