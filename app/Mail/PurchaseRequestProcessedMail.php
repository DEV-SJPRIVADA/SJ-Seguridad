<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest->loadMissing(['procesadoComprasPor']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de compra N.º '.$this->purchaseRequest->folio().' — '.$this->purchaseRequest->estadoComprasLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.processed',
        );
    }
}
