<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a quien pidió la aprobación: la orden fue aprobada o rechazada (rechazada = anulada).
 */
class PurchaseOrderApprovalResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order, public bool $approved) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden de compra N.º '.$this->order->numeroOc.' — '.($this->approved ? 'Aprobada' : 'Rechazada'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-orders.approval-resolved',
        );
    }
}
