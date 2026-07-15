<?php

namespace App\Mail;

use App\Models\PersonalRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalRequisitionStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PersonalRequisition $requisition;

    public string $fromStatus;

    public string $toStatus;

    public function __construct(PersonalRequisition $requisition, string $fromStatus, string $toStatus)
    {
        $this->requisition = $requisition->load(['position', 'client', 'requester']);
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
    }

    public function envelope(): Envelope
    {
        $labels = PersonalRequisition::statuses();
        $toLabel = $labels[$this->toStatus] ?? $this->toStatus;

        return new Envelope(
            subject: 'Requisición '.$this->requisition->code.': '.$toLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.requisitions.status-changed',
            with: [
                'fromStatusLabel' => PersonalRequisition::statuses()[$this->fromStatus] ?? $this->fromStatus,
                'toStatusLabel' => PersonalRequisition::statuses()[$this->toStatus] ?? $this->toStatus,
            ],
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
