<?php

namespace App\Mail;

use App\Support\DisplayDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CommercialDocumentationDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{
     *     nit: string,
     *     name: string,
     *     documentation_expires_on: Carbon,
     *     status_label: string,
     *     days_remaining: ?int,
     *     checklist_url: string,
     * }>  $clients
     */
    public function __construct(
        public Carbon $asOf,
        public array $clients,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->clients);
        $dateLabel = DisplayDate::date($this->asOf);

        return new Envelope(
            subject: '['.config('app.name')."] Documentacion comercial — {$count} cliente(s) ({$dateLabel})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comercial.documentation-digest',
            with: [
                'asOf' => $this->asOf,
                'clients' => $this->clients,
                'checklistIndexUrl' => route('comercial.matriz.clients.checklist.index'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
