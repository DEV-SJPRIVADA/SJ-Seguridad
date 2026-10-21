<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a quien pidió la anulación: el director la aprobó o la rechazó.
 */
class PurchaseOrderCancellationResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order, public bool $approved) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden de compra N.º '.$this->order->numeroOc.' — Anulación '.($this->approved ? 'aprobada' : 'rechazada'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-orders.cancellation-resolved',
        );
    }
}
