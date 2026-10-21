<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al solicitante: Compras devolvió la solicitud para corregirla.
 */
class PurchaseRequestReturnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseRequest $purchaseRequest, public string $motivo) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de compra N.º '.$this->purchaseRequest->folioVisible().' — Devuelta por Compras',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.returned',
        );
    }
}
