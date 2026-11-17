<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Services\PurchaseRequests\PurchaseOrderApprovalService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a un aprobador de la OC: paso 1 (quien autorizó la solicitud) o paso 2 (Director Admon.).
 */
class PurchaseOrderApprovalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $order,
        public string $approvalUrl,
        public string $paso = PurchaseOrderApprovalService::PASO_ADMON,
        public bool $yaPuedeDecidir = false,
    ) {}

    public function envelope(): Envelope
    {
        $suffix = $this->paso === PurchaseOrderApprovalService::PASO_AREA
            ? ' — visto bueno del área'
            : ' — autorización administrativa';

        return new Envelope(
            subject: 'Aprobación de orden de compra N.º '.$this->order->numeroOc.$suffix,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-orders.approval-requested',
        );
    }
}
