<?php

namespace App\Models\Calidad;

class MtGi11Estado extends MtGi11Catalog
{
    protected $table = 'calidad_mt_gi_11_estados';

    /** Estado por defecto al crear una solicitud. */
    public const CODE_PENDIENTE = 'PENDIENTE';

    /** @var list<string> */
    protected $fillable = ['code', 'name', 'color', 'is_active', 'sort_order'];

    /**
     * Variante válida de `status-pill`; cualquier otro valor cae en muted.
     */
    public function pillClass(): string
    {
        $allowed = ['success', 'warning', 'danger', 'info', 'muted'];

        return in_array((string) $this->color, $allowed, true) ? (string) $this->color : 'muted';
    }
}
