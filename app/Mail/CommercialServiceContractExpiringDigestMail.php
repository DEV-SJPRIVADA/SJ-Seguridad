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

class CommercialServiceContractExpiringDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{
     *     nit: string,
     *     client_name: string,
     *     contract_number: ?string,
     *     contract_end: Carbon,
     *     days_remaining: int,
     *     edit_url: string,
     * }>  $services
     */
    public function __construct(
        public Carbon $asOf,
        public array $services,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->services);
        $dateLabel = DisplayDate::date($this->asOf);

        return new Envelope(
            subject: '['.config('app.name')."] Contratos de servicio por vencer — {$count} servicio(s) ({$dateLabel})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comercial.service-contract-expiring-digest',
            with: [
                'asOf' => $this->asOf,
                'services' => $this->services,
                'servicesIndexUrl' => route('comercial.matriz.services.index', ['vigencia' => 'expiring']),
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
