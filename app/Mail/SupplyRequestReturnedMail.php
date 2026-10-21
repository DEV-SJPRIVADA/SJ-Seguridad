<?php

namespace App\Mail;

use App\Models\SupplyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al solicitante: Compras devolvió el suministro para corregirlo.
 */
class SupplyRequestReturnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupplyRequest $supplyRequest, public string $motivo) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Suministro '.$this->supplyRequest->folioVisible().' — Devuelto por Compras',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.supplies.returned',
        );
    }
}
