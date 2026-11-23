<?php

namespace App\Models\Calidad;

use Illuminate\Database\Eloquent\Builder;

class MtGi11Estado extends MtGi11Catalog
{
    protected $table = 'calidad_mt_gi_11_estados';

    /** Estado por defecto al crear una solicitud. */
    public const CODE_PENDIENTE = 'PENDIENTE';

    /**
     * Estado de cierre: con él la solicitud se guarda completa. Se detecta por
     * nombre o por código, porque el código técnico puede diferir del label.
     */
    public const NAME_GESTIONADO = 'GESTIONADO';

    /** @var list<string> */
    protected $fillable = ['code', 'name', 'color', 'is_active', 'sort_order'];

    /**
     * El registro corresponde al estado GESTIONADO (por código o por nombre).
     */
    public function isGestionado(): bool
    {
        return self::normalize((string) $this->code) === self::NAME_GESTIONADO
            || self::normalize((string) $this->name) === self::NAME_GESTIONADO;
    }

    /**
     * Id del estado GESTIONADO en el catálogo (null si no existe).
     */
    public static function gestionadoId(): ?int
    {
        $id = static::query()
            ->where(function (Builder $query): void {
                $query->whereRaw('UPPER(code) = ?', [self::NAME_GESTIONADO])
                    ->orWhereRaw('UPPER(name) = ?', [self::NAME_GESTIONADO]);
            })
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * ¿El id recibido del formulario es el estado GESTIONADO?
     */
    public static function isGestionadoId(mixed $id): bool
    {
        $gestionado = self::gestionadoId();

        return $gestionado !== null && $id !== null && $id !== '' && (int) $id === $gestionado;
    }

    private static function normalize(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }

    /**
     * Variante válida de `status-pill`; cualquier otro valor cae en muted.
     */
    public function pillClass(): string
    {
        $allowed = ['success', 'warning', 'danger', 'info', 'muted'];

        return in_array((string) $this->color, $allowed, true) ? (string) $this->color : 'muted';
    }
}
