<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al aprobador elegido: Compras pide aprobar una orden de compra.
 */
class PurchaseOrderApprovalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order, public string $approvalUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aprobación de orden de compra N.º '.$this->order->numeroOc,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-orders.approval-requested',
        );
    }
}
