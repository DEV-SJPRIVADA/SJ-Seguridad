<?php

namespace App\Mail;

use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestMessage;
use App\Models\User;
use App\Support\ApplicationUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DevelopmentRequestMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DevelopmentRequest $developmentRequest,
        public DevelopmentRequestMessage $message,
        public User $author,
    ) {
        $this->developmentRequest->loadMissing(['creator', 'leader']);
    }

    public function envelope(): Envelope
    {
        $code = $this->developmentRequest->code ?: '#'.$this->developmentRequest->id;

        return new Envelope(
            subject: 'Nuevo mensaje en solicitud '.$code,
        );
    }

    public function content(): Content
    {
        $module = $this->developmentRequest->area_key ?: 'tic';

        return new Content(
            markdown: 'emails.development-requests.message',
            with: [
                'excerpt' => mb_substr(trim((string) $this->message->body), 0, 280),
                'platformUrl' => ApplicationUrls::route('development-requests.show', [
                    'module' => $module,
                    'development_request' => $this->developmentRequest->id,
                ]).'#conversation',
            ],
        );
    }
}
