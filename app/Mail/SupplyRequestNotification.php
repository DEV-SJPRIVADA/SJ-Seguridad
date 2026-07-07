<?php

namespace App\Mail;

use App\Models\SupplyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplyRequestNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SupplyRequest $supplyRequest;

    public function __construct(SupplyRequest $supplyRequest)
    {
        $this->supplyRequest = $supplyRequest->load(['user', 'items.product']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de suministros #'.$this->supplyRequest->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.supplies.submitted',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
