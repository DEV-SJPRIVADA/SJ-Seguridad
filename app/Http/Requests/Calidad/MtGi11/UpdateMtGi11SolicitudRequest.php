<?php

namespace App\Http\Requests\Calidad\MtGi11;

/**
 * Edición de solicitud MT-GI-11.
 *
 * Hereda las reglas del alta, pero la fecha de solicitud **no** se vuelve a
 * tomar del día: se conserva la del registro original, porque es la fecha en
 * que se hizo la solicitud.
 */
class UpdateMtGi11SolicitudRequest extends StoreMtGi11SolicitudRequest
{
    /**
     * Mantiene la fecha original del registro en lugar de la fecha actual.
     */
    protected function prepareForValidation(): void
    {
        $solicitud = $this->route('solicitud');

        $this->merge([
            'fecha_solicitud' => $solicitud?->fecha_solicitud?->toDateString()
                ?? now()->toDateString(),
        ]);
    }
}
