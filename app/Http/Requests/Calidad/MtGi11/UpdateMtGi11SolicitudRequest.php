<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Services\Access\MtGi11AccessService;

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
     * Misma regla que el controlador (`canManageSolicitud`): `matriz.edit` sobre
     * lo propio, o `view_all` (Calidad) sobre cualquier solicitud. Crear solo
     * requiere `matriz.view`.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $solicitud = $this->route('solicitud');

        if ($user === null) {
            return false;
        }

        $access = app(MtGi11AccessService::class);

        if ($solicitud === null) {
            return $access->canEdit($user);
        }

        return $access->canManageSolicitud($user, (int) $solicitud->created_by);
    }

    /**
     * Al editar sin permiso sobre la sección, se conservan los valores que ya
     * tenía la solicitud en lugar de vaciarlos (el campo está deshabilitado y no
     * viaja en el POST).
     *
     * @return array<string, mixed>
     */
    protected function estandarizacionFallback(): array
    {
        $solicitud = $this->route('solicitud');

        return [
            'codigo_estandarizacion' => $solicitud?->codigo_estandarizacion,
            'fecha_estandarizacion' => $solicitud?->fecha_estandarizacion?->toDateString(),
            'version_actualizada' => $solicitud?->version_actualizada,
            'estado_id' => $solicitud?->estado_id,
        ];
    }

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

        $this->discardEstandarizacionWithoutPermission();
    }
}
