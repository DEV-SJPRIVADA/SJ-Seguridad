<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeTerminationFollowup;
use App\Models\ReportesNovedadesRetiro;
use Illuminate\Support\Facades\DB;

class ReportesNovedadesRetiroSyncService
{
    public function __construct(
        private readonly ReportesNovedadesAuditLogService $auditLogService,
        private readonly ReportesNovedadesFichaLookupService $fichaLookup,
    ) {}

    /**
     * Alta idempotente de Retiros al crear seguimiento de desvinculación.
     */
    public function ensureFromFollowup(EmployeeTerminationFollowup $followup, ?int $userId = null): ReportesNovedadesRetiro
    {
        $existing = ReportesNovedadesRetiro::query()
            ->where('employee_termination_followup_id', $followup->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $followup->loadMissing(['fichaEntry.profile', 'fichaEntry.requisition.client', 'employmentPeriod']);

        $lookup = $this->fichaLookup->lookupByDocument((string) $followup->document_number);
        $destino = $lookup['found'] ? ($lookup['destino'] ?? null) : null;
        $fechaIngreso = $lookup['found'] ? ($lookup['fecha_ingreso'] ?? null) : null;
        $tipo = $lookup['found'] ? ($lookup['tipo'] ?? null) : null;

        if ($fechaIngreso === null && $followup->employmentPeriod?->hire_date !== null) {
            $fechaIngreso = optional($followup->employmentPeriod->hire_date)->toDateString();
        }

        $row = ReportesNovedadesRetiro::query()->create([
            'document_number' => (string) $followup->document_number,
            'employee_name' => (string) $followup->full_name,
            'fecha_ingreso' => $fechaIngreso,
            'tipo' => $tipo,
            'cargo' => $followup->position_name,
            'destino' => $destino,
            'novedad' => (string) config('reportes_novedades.retiros_novedad_default', 'RETIRO'),
            'fecha_retiro' => optional($followup->termination_date)?->toDateString(),
            'motivo_retiro' => $this->mapMotivoRetiro(
                $followup->termination_cause_code,
                $followup->termination_cause_name,
            ),
            'observaciones' => $followup->termination_notes,
            'employee_termination_followup_id' => $followup->id,
            'personal_requisition_ficha_entry_id' => $followup->personal_requisition_ficha_entry_id,
            'observacion_nomina' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->auditLogService->logEvent(
            eventType: 'retiro_auto',
            action: 'create',
            metadata: [
                'sheet' => 'retiros',
                'id' => $row->id,
                'document_number' => $row->document_number,
                'employee_termination_followup_id' => $followup->id,
            ],
            model: $row,
            userId: $userId,
        );

        return $row;
    }

    /**
     * Soft-delete de la fila Retiros vinculada al followup (antes de borrar el followup).
     */
    public function annulFromFollowup(EmployeeTerminationFollowup $followup, ?int $userId = null): ?ReportesNovedadesRetiro
    {
        $row = ReportesNovedadesRetiro::query()
            ->where('employee_termination_followup_id', $followup->id)
            ->first();

        if ($row === null) {
            return null;
        }

        return DB::transaction(function () use ($row, $followup, $userId): ReportesNovedadesRetiro {
            $metadata = [
                'sheet' => 'retiros',
                'id' => $row->id,
                'document_number' => $row->document_number,
                'employee_termination_followup_id' => $followup->id,
            ];

            $row->delete();

            $this->auditLogService->logEvent(
                eventType: 'retiro_auto',
                action: 'annul',
                metadata: $metadata,
                userId: $userId,
            );

            return $row;
        });
    }

    private function mapMotivoRetiro(?string $code, ?string $name): ?string
    {
        /** @var list<string> $catalog */
        $catalog = config('reportes_novedades.catalogos.retiros_motivo', []);

        $name = $name !== null ? trim($name) : null;
        $code = $code !== null ? trim($code) : null;

        if ($name !== null && $name !== '' && in_array($name, $catalog, true)) {
            return $name;
        }

        if ($code !== null && $code !== '' && in_array($code, $catalog, true)) {
            return $code;
        }

        $aliases = [
            'RENUNCIA' => 'RENUNCIA',
            'PERIODO_PRUEBA' => 'PERIODO DE PRUEBA',
            'FIN_CONTRATO' => 'TERMINACION DE CONTRATO',
        ];

        if ($code !== null && isset($aliases[$code]) && in_array($aliases[$code], $catalog, true)) {
            return $aliases[$code];
        }

        return null;
    }
}
