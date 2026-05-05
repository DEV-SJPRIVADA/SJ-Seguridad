<?php

namespace App\Mail;

use App\Models\PersonalRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalRequisitionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $requisition;
    public $totalQuantity;

    /**
     * Create a new message instance.
     */
    public function __construct(PersonalRequisition $requisition, int $totalQuantity = 1)
    {
        $this->requisition = $requisition->load(['position', 'client', 'requester']);
        $this->totalQuantity = $totalQuantity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Nueva Requisición de Personal: ' . $this->requisition->code;
        
        if ($this->totalQuantity > 1) {
            $subject .= ' (' . $this->totalQuantity . ' vacantes)';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.requisitions.requested',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
