<?php

namespace App\Mail;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a quien pidió la aprobación: el grupo de cotizaciones fue aprobado (con una
 * cotización ganadora) o rechazado.
 */
class PurchaseQuotationApprovalResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public string $batchLabel,
        public ?PurchaseQuotation $selected,
        public bool $approved,
        public ?string $comment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cotizaciones '.($this->approved ? 'aprobadas' : 'rechazadas')
                .' — Solicitud '.$this->purchaseRequest->folioVisible(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-requests.quotation-approval-resolved',
        );
    }
}
