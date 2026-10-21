<?php

namespace App\Enums;

use App\Models\PurchaseRequest;

/**
 * Etapa común del pedido (compra SC- y suministro SU-).
 *
 * Solo traduce las columnas existentes; no se guarda en BD.
 * Compra: `estado` (director) + `estado_compras` (máquina). Suministro: `status`.
 * Quién autoriza (Director o Calidad) es un dato aparte, no parte de la etapa.
 */
enum PedidoEtapa: string
{
    case PorAutorizar = 'por_autorizar';
    case Devuelta = 'devuelta';
    case PorComprar = 'por_comprar';
    case ConOrden = 'con_orden';
    case Cerrado = 'cerrado';
    case CerradoSinCompra = 'cerrado_sin_compra';

    public function label(): string
    {
        return match ($this) {
            self::PorAutorizar => 'Por autorizar',
            self::Devuelta => 'Devuelta',
            self::PorComprar => 'Por comprar',
            self::ConOrden => 'Con orden',
            self::Cerrado => 'Cerrado',
            self::CerradoSinCompra => 'Cerrado sin compra',
        };
    }

    public function pillClass(): string
    {
        return 'status-pill--etapa-'.$this->value;
    }

    /**
     * Opciones para filtros (`value => label`).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function fromPurchase(?string $estado, ?string $estadoCompras): self
    {
        return match ($estado) {
            PurchaseRequest::ESTADO_PENDIENTE => self::PorAutorizar,
            PurchaseRequest::ESTADO_RECHAZADO => self::Devuelta,
            PurchaseRequest::ESTADO_APROBADO => self::fromComprasKey($estadoCompras) ?? self::PorComprar,
            default => self::PorAutorizar,
        };
    }

    public static function fromSupply(?string $status): self
    {
        return match ($status) {
            'rechazada_calidad' => self::Devuelta,
            'aprobada_calidad' => self::PorComprar,
            'en_compras' => self::ConOrden,
            'completada' => self::Cerrado,
            default => self::PorAutorizar,
        };
    }

    /**
     * Etapa a partir de `estado_compras` (también la clave que usan Pedidos y el dashboard).
     */
    public static function fromComprasKey(?string $estadoCompras): ?self
    {
        return match ($estadoCompras) {
            PurchaseRequest::COMPRAS_PENDIENTE => self::PorComprar,
            PurchaseRequest::COMPRAS_EN_CURSO => self::ConOrden,
            PurchaseRequest::COMPRAS_COMPLETADO => self::Cerrado,
            PurchaseRequest::COMPRAS_RECHAZADO => self::CerradoSinCompra,
            default => null,
        };
    }

    /**
     * Clave `estado_compras` equivalente, solo para las etapas de Compras.
     */
    public function comprasKey(): ?string
    {
        return match ($this) {
            self::PorComprar => PurchaseRequest::COMPRAS_PENDIENTE,
            self::ConOrden => PurchaseRequest::COMPRAS_EN_CURSO,
            self::Cerrado => PurchaseRequest::COMPRAS_COMPLETADO,
            self::CerradoSinCompra => PurchaseRequest::COMPRAS_RECHAZADO,
            default => null,
        };
    }

    /**
     * Valores de `supply_requests.status` que corresponden a la etapa.
     * Un suministro nunca queda Cerrado sin compra.
     *
     * @return list<string>
     */
    public function supplyStatuses(): array
    {
        return match ($this) {
            self::PorAutorizar => ['pendiente_calidad'],
            self::Devuelta => ['rechazada_calidad'],
            self::PorComprar => ['aprobada_calidad'],
            self::ConOrden => ['en_compras'],
            self::Cerrado => ['completada'],
            self::CerradoSinCompra => [],
        };
    }
}
