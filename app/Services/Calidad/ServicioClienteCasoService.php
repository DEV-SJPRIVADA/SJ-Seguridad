<?php

namespace App\Services\Calidad;

use App\Models\CommercialClient;
use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCasoObservacion;
use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use App\Support\UppercaseFormText;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioClienteCasoService
{
    public function __construct(
        private readonly ServicioClienteBusinessDaysService $businessDays,
        private readonly ServicioClienteAdjuntoService $adjuntos,
        private readonly ServicioClienteAuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function create(array $data, array $files, int $userId): ServicioClienteCaso
    {
        $this->assertCatalogsActive($data);
        $this->assertEvidence($data['eficacia'], 0, $files);

        return DB::transaction(function () use ($data, $files, $userId): ServicioClienteCaso {
            $next = ((int) ServicioClienteCaso::query()->lockForUpdate()->max('consecutivo')) + 1;
            $attributes = $this->attributesFrom($data);
            $attributes['consecutivo'] = $next;
            $attributes['created_by'] = $userId;
            $attributes['updated_by'] = $userId;

            $caso = ServicioClienteCaso::query()->create($attributes);
            $this->adjuntos->storeMany($caso, $files, $userId);

            $this->audit->logModelChange(
                eventType: 'fo_gi_22',
                action: 'create',
                model: $caso,
                before: null,
                after: $this->snapshot($caso),
                userId: $userId,
            );

            return $caso->fresh(['adjuntos']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function update(ServicioClienteCaso $caso, array $data, array $files, int $userId): ServicioClienteCaso
    {
        $this->assertCatalogsActive($data, $caso);
        $existing = $caso->adjuntos()->count();
        $this->assertEvidence($data['eficacia'], $existing, $files);

        return DB::transaction(function () use ($caso, $data, $files, $userId): ServicioClienteCaso {
            $before = $this->snapshot($caso);
            $attributes = $this->attributesFrom($data);
            $attributes['updated_by'] = $userId;
            $caso->update($attributes);

            if ($files !== []) {
                $this->adjuntos->storeMany($caso, $files, $userId);
            }

            $caso->refresh();
            $this->audit->logModelChange(
                eventType: 'fo_gi_22',
                action: 'update',
                model: $caso,
                before: $before,
                after: $this->snapshot($caso),
                userId: $userId,
            );

            return $caso;
        });
    }

    public function delete(ServicioClienteCaso $caso, int $userId): void
    {
        $snapshot = $this->snapshot($caso);
        $snapshot['observaciones'] = $caso->observaciones()->orderBy('id')->get(['id', 'texto', 'created_by', 'created_at'])->toArray();
        $snapshot['adjuntos'] = $caso->adjuntos()->orderBy('id')->get(['id', 'original_name', 'stored_path'])->toArray();

        DB::transaction(function () use ($caso, $snapshot, $userId): void {
            $this->adjuntos->deleteAll($caso);
            $this->audit->logModelChange(
                eventType: 'fo_gi_22',
                action: 'delete',
                model: $caso,
                before: $snapshot,
                after: null,
                reason: 'Eliminación definitiva aceptada por el usuario',
                userId: $userId,
            );
            $caso->delete();
        });
    }

    public function addObservacion(ServicioClienteCaso $caso, string $texto, int $userId): ServicioClienteCasoObservacion
    {
        $observacion = $caso->observaciones()->create([
            'texto' => UppercaseFormText::uppercaseString($texto),
            'created_by' => $userId,
        ]);

        $this->audit->logEvent(
            eventType: 'fo_gi_22_observacion',
            action: 'create',
            metadata: [
                'caso_id' => $caso->id,
                'consecutivo' => $caso->consecutivo,
                'observacion_id' => $observacion->id,
            ],
            model: $observacion,
            userId: $userId,
        );

        return $observacion;
    }

    public function updateObservacion(ServicioClienteCasoObservacion $observacion, string $texto, int $userId): void
    {
        $before = ['texto' => $observacion->texto];
        $texto = UppercaseFormText::uppercaseString($texto);

        DB::transaction(function () use ($observacion, $texto, $before, $userId): void {
            $observacion->update(['texto' => $texto]);
            $this->audit->logModelChange(
                eventType: 'fo_gi_22_observacion',
                action: 'update',
                model: $observacion,
                before: $before,
                after: ['texto' => $observacion->texto],
                metadata: [
                    'caso_id' => $observacion->caso_id,
                    'observacion_id' => $observacion->id,
                ],
                userId: $userId,
            );
        });
    }

    public function deleteObservacion(ServicioClienteCasoObservacion $observacion, int $userId): void
    {
        $before = $observacion->only(['id', 'caso_id', 'texto', 'created_by']);

        DB::transaction(function () use ($observacion, $before, $userId): void {
            $this->audit->logModelChange(
                eventType: 'fo_gi_22_observacion',
                action: 'delete',
                model: $observacion,
                before: $before,
                after: null,
                reason: 'Eliminación de la observación aceptada por el usuario',
                metadata: [
                    'caso_id' => $observacion->caso_id,
                    'observacion_id' => $observacion->id,
                ],
                userId: $userId,
            );
            $observacion->delete();
        });
    }

    public function assertCanRemoveAttachment(ServicioClienteCaso $caso): void
    {
        if ($caso->eficacia === ServicioClienteCaso::EFICACIA_CERRADO && $caso->adjuntos()->count() <= 1) {
            throw ValidationException::withMessages([
                'attachments' => 'Un caso cerrado debe conservar al menos un archivo de evidencia.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    private function assertEvidence(string $eficacia, int $existingCount, array $files): void
    {
        if ($eficacia !== ServicioClienteCaso::EFICACIA_CERRADO) {
            return;
        }

        if ($existingCount + count($files) < 1) {
            throw ValidationException::withMessages([
                'attachments' => 'No se puede cerrar el caso sin evidencia (PDF, JPG o PNG).',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCatalogsActive(array $data, ?ServicioClienteCaso $current = null): void
    {
        $checks = [
            'tipologia_id' => [ServicioClienteTipologia::class, $current?->tipologia_id],
            'fuente_id' => [ServicioClienteFuente::class, $current?->fuente_id],
            'categoria_id' => [ServicioClienteCategoria::class, $current?->categoria_id],
            'responsable_id' => [ServicioClienteResponsable::class, $current?->responsable_id],
        ];

        $errors = [];

        foreach ($checks as $key => [$class, $previous]) {
            $id = (int) ($data[$key] ?? 0);
            $item = $class::query()->find($id);

            if ($item === null || (! $item->is_active && (int) $previous !== $id)) {
                $errors[$key] = 'Seleccione un valor activo del catálogo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        $recepcion = Carbon::parse((string) $data['fecha_recepcion'])->startOfDay();
        $verificacion = filled($data['fecha_verificacion'] ?? null)
            ? Carbon::parse((string) $data['fecha_verificacion'])->startOfDay()
            : null;
        $plazo = $this->businessDays->addBusinessDays($recepcion, (int) config('servicio_cliente.plazo_dias_habiles', 10));

        if (($data['tipo_cliente'] ?? '') === ServicioClienteCaso::TIPO_INTERNO) {
            $clientId = null;
            $nombre = (string) config('servicio_cliente.cliente_interno_nombre', 'SJSEGURIDAD');
        } else {
            $client = CommercialClient::query()->findOrFail($data['commercial_client_id']);
            $clientId = $client->id;
            $nombre = UppercaseFormText::uppercaseString((string) $client->name);
        }

        return [
            'fecha_recepcion' => $recepcion->toDateString(),
            'fecha_ejecucion' => $recepcion->toDateString(),
            'fecha_verificacion' => $verificacion?->toDateString(),
            'fecha_plazo_respuesta' => $plazo->toDateString(),
            'tipo_cliente' => $data['tipo_cliente'],
            'tipologia_id' => (int) $data['tipologia_id'],
            'fuente_id' => (int) $data['fuente_id'],
            'commercial_client_id' => $clientId,
            'cliente_nombre' => $nombre,
            'city_id' => (int) $data['city_id'],
            'descripcion' => UppercaseFormText::uppercaseString((string) $data['descripcion']),
            'categoria_id' => (int) $data['categoria_id'],
            'correccion' => UppercaseFormText::uppercaseString((string) $data['correccion']),
            'accion_realizar' => UppercaseFormText::uppercaseString((string) $data['accion_realizar']),
            'responsable_id' => (int) $data['responsable_id'],
            'dias_habiles' => $this->businessDays->countInclusive($recepcion, $verificacion),
            'responsable_seguimiento' => filled($data['responsable_seguimiento'] ?? null)
                ? UppercaseFormText::uppercaseString((string) $data['responsable_seguimiento'])
                : null,
            'eficacia' => $data['eficacia'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(ServicioClienteCaso $caso): array
    {
        return [
            'id' => $caso->id,
            'consecutivo' => $caso->consecutivo,
            'fecha_recepcion' => optional($caso->fecha_recepcion)?->toDateString(),
            'fecha_ejecucion' => optional($caso->fecha_ejecucion)?->toDateString(),
            'fecha_verificacion' => optional($caso->fecha_verificacion)?->toDateString(),
            'fecha_plazo_respuesta' => optional($caso->fecha_plazo_respuesta)?->toDateString(),
            'tipo_cliente' => $caso->tipo_cliente,
            'cliente_nombre' => $caso->cliente_nombre,
            'commercial_client_id' => $caso->commercial_client_id,
            'city_id' => $caso->city_id,
            'tipologia_id' => $caso->tipologia_id,
            'fuente_id' => $caso->fuente_id,
            'categoria_id' => $caso->categoria_id,
            'responsable_id' => $caso->responsable_id,
            'descripcion' => $caso->descripcion,
            'correccion' => $caso->correccion,
            'accion_realizar' => $caso->accion_realizar,
            'dias_habiles' => $caso->dias_habiles,
            'responsable_seguimiento' => $caso->responsable_seguimiento,
            'eficacia' => $caso->eficacia,
        ];
    }
}
