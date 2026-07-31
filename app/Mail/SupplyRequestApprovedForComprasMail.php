<?php

namespace App\Mail;

use App\Models\SupplyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplyRequestApprovedForComprasMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupplyRequest $supplyRequest)
    {
        $this->supplyRequest->loadMissing(['user', 'items.product']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Suministro aprobado #'.$this->supplyRequest->id.' — bandeja Compras',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.supplies.approved-for-compras',
        );
    }
}
