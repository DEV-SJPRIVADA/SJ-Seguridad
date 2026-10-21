<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al director elegido: Compras pide anular una orden de compra.
 */
class PurchaseOrderCancellationRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order, public string $approvalUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de anulación — Orden de compra N.º '.$this->order->numeroOc,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-orders.cancellation-requested',
        );
    }
}
