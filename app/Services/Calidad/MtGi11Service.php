<?php

namespace App\Services\Calidad;

use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Solicitud;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Alta, edición, eliminación y «nueva versión» de solicitudes MT-GI-11.
 *
 * Los adjuntos se guardan en disco privado y se borran físicamente al eliminar
 * la solicitud (el registro usa soft delete para conservar el histórico).
 */
final class MtGi11Service
{
    public function __construct(
        private readonly MtGi11AdjuntoService $adjuntos,
        private readonly MtGi11AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function create(array $data, array $files, int $userId): MtGi11Solicitud
    {
        $solicitud = DB::transaction(function () use ($data, $files, $userId): MtGi11Solicitud {
            $payload = Arr::only($data, $this->fillableKeys());
            $payload['created_by'] = $userId;
            $payload['updated_by'] = $userId;

            // El consecutivo es opcional: si viene vacío se guarda un valor
            // temporal y, ya con el id, se reemplaza por uno provisional.
            $sinConsecutivo = trim((string) ($payload['consecutivo'] ?? '')) === '';
            $payload['consecutivo'] = $sinConsecutivo ? '' : $payload['consecutivo'];

            $solicitud = MtGi11Solicitud::query()->create($payload);

            if ($sinConsecutivo) {
                $solicitud->forceFill(['consecutivo' => $this->consecutivoProvisional($solicitud)])->save();
            }

            if ($files !== []) {
                $this->adjuntos->storeMany($solicitud, $files, $userId);
            }

            return $solicitud;
        });

        $this->audit->logModelChange('create', $solicitud, null, $this->snapshot($solicitud), $userId);

        return $solicitud->load($this->relations());
    }

    /**
     * Consecutivo provisional para altas sin consecutivo: MT-GI-11-{id}.
     * Quien tenga permiso de edición puede reemplazarlo después.
     */
    public function consecutivoProvisional(MtGi11Solicitud $solicitud): string
    {
        return 'MT-GI-11-'.str_pad((string) $solicitud->id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function update(MtGi11Solicitud $solicitud, array $data, array $files, int $userId): MtGi11Solicitud
    {
        $before = $this->snapshot($solicitud);

        DB::transaction(function () use ($solicitud, $data, $files, $userId): void {
            $payload = Arr::only($data, $this->fillableKeys());
            $payload['updated_by'] = $userId;

            // El consecutivo es opcional en el alta, pero la columna no acepta
            // vacío: al editar sin consecutivo se conserva el que ya tenía.
            if (array_key_exists('consecutivo', $payload) && trim((string) $payload['consecutivo']) === '') {
                unset($payload['consecutivo']);
            }

            $solicitud->update($payload);

            if ($files !== []) {
                $this->adjuntos->storeMany($solicitud, $files, $userId);
            }
        });

        $solicitud->refresh();

        $this->audit->logModelChange('update', $solicitud, $before, $this->snapshot($solicitud), $userId);

        return $solicitud->load($this->relations());
    }

    /**
     * Elimina archivos del disco y hace soft delete del registro.
     */
    public function delete(MtGi11Solicitud $solicitud, int $userId): void
    {
        $metadata = $this->snapshot($solicitud);

        DB::transaction(function () use ($solicitud): void {
            $this->adjuntos->deleteAll($solicitud);
            $solicitud->delete();
        });

        $this->audit->logEvent(
            action: 'delete',
            eventType: 'mt_gi_11',
            metadata: $metadata,
            userId: $userId,
        );
    }

    /**
     * Datos de precarga para «Nueva versión»: mismo documento, versión + 1.
     *
     * No copia fecha de solicitud (se usa hoy), justificación, fecha de
     * estandarización ni adjuntos: esos los define quien crea la nueva versión.
     *
     * @return array<string, mixed>
     */
    public function nuevaVersionDefaults(MtGi11Solicitud $solicitud): array
    {
        $estadoPendiente = MtGi11Estado::query()
            ->where('code', MtGi11Estado::CODE_PENDIENTE)
            ->value('id');

        return [
            'fecha_solicitud' => now()->toDateString(),
            'proceso_id' => (string) $solicitud->proceso_id,
            'consecutivo' => (string) $solicitud->consecutivo,
            'tipo_documento_id' => (string) $solicitud->tipo_documento_id,
            'nombre_documento' => (string) $solicitud->nombre_documento,
            'tipo_solicitud_id' => (string) $solicitud->tipo_solicitud_id,
            'codigo_actual' => (string) ($solicitud->codigo_actual ?? ''),
            'version_actual' => (string) ($solicitud->version_actual ?? ''),
            'justificacion' => '',
            'responsable_proceso' => (string) $solicitud->responsable_proceso,
            'cargo_responsable_id' => (string) $solicitud->cargo_responsable_id,
            'codigo_estandarizacion' => (string) ($solicitud->codigo_estandarizacion ?? ''),
            'fecha_estandarizacion' => '',
            'version_actualizada' => (string) $this->nextVersion($solicitud),
            'estado_id' => (string) ($estadoPendiente ?? $solicitud->estado_id),
        ];
    }

    /**
     * Versión actualizada sugerida: la del registro origen + 1.
     */
    public function nextVersion(MtGi11Solicitud $solicitud): int
    {
        $base = $solicitud->version_actualizada;

        if ($base === null) {
            // Sin versión numérica previa: intenta derivarla de "V1.2" / "1.2".
            if (preg_match('/(\d+)/', (string) $solicitud->version_actual, $matches) === 1) {
                return max(1, (int) $matches[1] + 1);
            }

            return 1;
        }

        return max(1, (int) $base + 1);
    }

    /**
     * @return list<string>
     */
    public function fillableKeys(): array
    {
        return [
            'fecha_solicitud',
            'proceso_id',
            'consecutivo',
            'tipo_documento_id',
            'nombre_documento',
            'tipo_solicitud_id',
            'codigo_actual',
            'version_actual',
            'justificacion',
            'responsable_proceso',
            'cargo_responsable_id',
            'codigo_estandarizacion',
            'fecha_estandarizacion',
            'version_actualizada',
            'estado_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return [
            'proceso',
            'tipoDocumento',
            'tipoSolicitud',
            'cargoResponsable',
            'estado',
            'creator',
            'adjuntos',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(MtGi11Solicitud $solicitud): array
    {
        return [
            'id' => (int) $solicitud->id,
            'consecutivo' => (string) $solicitud->consecutivo,
            'nombre_documento' => (string) $solicitud->nombre_documento,
            'proceso_id' => (int) $solicitud->proceso_id,
            'tipo_documento_id' => (int) $solicitud->tipo_documento_id,
            'tipo_solicitud_id' => (int) $solicitud->tipo_solicitud_id,
            'estado_id' => (int) $solicitud->estado_id,
            'fecha_solicitud' => $solicitud->fecha_solicitud?->toDateString(),
            'created_by' => $solicitud->created_by,
        ];
    }
}
