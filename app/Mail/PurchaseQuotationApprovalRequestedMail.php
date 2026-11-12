<?php

namespace App\Mail;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Aviso al director elegido: Compras pide aprobar un grupo de cotizaciones (una OC futura)
 * y elegir la cotización ganadora.
 */
class PurchaseQuotationApprovalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     */
    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public string $batchLabel,
        public Collection $quotations,
        public string $approvalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aprobación de cotizaciones — Solicitud '.$this->purchaseRequest->folioVisible(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.quotation-approval-requested',
        );
    }
}
