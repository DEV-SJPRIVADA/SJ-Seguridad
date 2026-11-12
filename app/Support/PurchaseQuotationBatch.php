<?php

namespace App\Support;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lote de cotizaciones que representa una OC futura.
 *
 * No es una tabla: se compone con las cotizaciones que comparten `lote`. El lote se
 * envía completo al director, que elige una cotización ganadora (las demás quedan
 * como `no_seleccionada`).
 */
class PurchaseQuotationBatch
{
    public const ESTADO_ABIERTA = 'abierta';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly Collection $quotations,
    ) {}

    public function first(): ?PurchaseQuotation
    {
        return $this->quotations->first();
    }

    public function purchaseRequest(): ?PurchaseRequest
    {
        return $this->first()?->purchaseRequest;
    }

    public function count(): int
    {
        return $this->quotations->count();
    }

    public function aprobador(): ?User
    {
        return $this->first()?->aprobador;
    }

    public function solicitadaPor(): ?User
    {
        return $this->first()?->solicitadaPor;
    }

    public function solicitadaAt(): ?Carbon
    {
        return $this->first()?->solicitada_at;
    }

    public function resueltaAt(): ?Carbon
    {
        return $this->first()?->resuelta_at;
    }

    public function comentario(): ?string
    {
        $comment = trim((string) $this->first()?->comentario);

        return $comment !== '' ? $comment : null;
    }

    public function seleccionada(): ?PurchaseQuotation
    {
        return $this->quotations->first(fn (PurchaseQuotation $quotation): bool => $quotation->seleccionada);
    }

    public function aprobadas(): Collection
    {
        return $this->quotations->filter(fn (PurchaseQuotation $quotation): bool => $quotation->estaAprobada())->values();
    }

    public function pendientes(): Collection
    {
        return $this->quotations->filter(fn (PurchaseQuotation $quotation): bool => $quotation->estaPendiente())->values();
    }

    public function estaPendiente(): bool
    {
        return $this->pendientes()->isNotEmpty();
    }

    public function estaAprobada(): bool
    {
        return $this->seleccionada() !== null;
    }

    public function estaRechazada(): bool
    {
        return ! $this->estaPendiente()
            && ! $this->estaAprobada()
            && $this->quotations->isNotEmpty()
            && $this->quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->estaRechazada());
    }

    public function estaAbierta(): bool
    {
        return ! $this->estaPendiente() && ! $this->estaAprobada();
    }

    public function estado(): string
    {
        return match (true) {
            $this->estaPendiente() => self::ESTADO_PENDIENTE,
            $this->estaAprobada() => self::ESTADO_APROBADA,
            $this->estaRechazada() => self::ESTADO_RECHAZADA,
            default => self::ESTADO_ABIERTA,
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado()) {
            self::ESTADO_PENDIENTE => 'Aprobación pendiente',
            self::ESTADO_APROBADA => 'Aprobada',
            self::ESTADO_RECHAZADA => 'Rechazada',
            default => 'En preparación',
        };
    }

    public function estadoPillClass(): string
    {
        return match ($this->estado()) {
            self::ESTADO_PENDIENTE => 'status-pill--warning',
            self::ESTADO_APROBADA => 'status-pill--success',
            self::ESTADO_RECHAZADA => 'status-pill--danger',
            default => 'status-pill--muted',
        };
    }

    public function puedeSolicitarAprobacion(): bool
    {
        return $this->estaAbierta()
            && $this->quotations->isNotEmpty()
            && $this->quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->puedeParticiparEnLote() && ! $quotation->estaVinculada());
    }

    public function puedeAgregarCotizacion(int $maximo): bool
    {
        return $this->estaAbierta()
            && ! $this->estaAprobada()
            && $this->count() < $maximo;
    }

    public function puedeRetirarSolicitud(): bool
    {
        return $this->estaPendiente();
    }

    public function resumenCotizaciones(): string
    {
        return $this->quotations
            ->map(fn (PurchaseQuotation $quotation): string => $quotation->descripcionVisible())
            ->implode(' · ');
    }
}
