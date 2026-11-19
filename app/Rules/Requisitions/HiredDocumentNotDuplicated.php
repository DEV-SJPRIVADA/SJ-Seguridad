<?php

namespace App\Rules\Requisitions;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HiredDocumentNotDuplicated implements ValidationRule
{
    public const PREFIX_DUPLICATE = 'DUPLICATE_HIRED_DOCUMENT:';

    public const PREFIX_REHIRE_NOT_ALLOWED = 'REHIRE_NOT_ALLOWED:';

    public const PREFIX_REHIRE_UNTRACKED = 'REHIRE_UNTRACKED:';

    public function __construct(
        private readonly ?int $requisitionId,
        private readonly bool $confirmed,
        private readonly ?string $confirmedDocument,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = trim((string) $value);

        if ($document === '') {
            return;
        }

        // Incluye entradas huérfanas (personal_requisition_id NULL, p. ej. import masivo):
        // en SQL `NULL != id` no es verdadero y antes se escapaban del chequeo → 500 por unique.
        $conflict = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->when($this->requisitionId !== null, function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNull('personal_requisition_id')
                        ->orWhere('personal_requisition_id', '!=', $this->requisitionId);
                });
            })
            ->with(['requisition:id,code', 'profile'])
            ->first();

        if ($conflict === null) {
            return;
        }

        $periodService = app(EmployeeFichaEmploymentPeriodService::class);
        $desvinculado = $this->isDesvinculadoInFicha($conflict);

        // No recontratable explícito: no se puede confirmar ni saltar.
        if ($desvinculado && $periodService->isExplicitlyNotRehireable($conflict)) {
            $fail(self::PREFIX_REHIRE_NOT_ALLOWED.' Esta cédula corresponde a un empleado desvinculado no recontratable.');

            return;
        }

        if ($this->confirmed && $document === trim((string) $this->confirmedDocument)) {
            return;
        }

        // Desvinculado sin Seguimientos: alerta informativa; el usuario debe confirmar el reingreso.
        if ($desvinculado && $periodService->isUntrackedDesvinculado($conflict)) {
            $fail(self::PREFIX_REHIRE_UNTRACKED.' Esta cédula corresponde a un empleado desvinculado que no está en Seguimientos. No hay decisión formal de recontratabilidad. Confirme para registrarlo como reingreso.');

            return;
        }

        if ($desvinculado && $periodService->isRehireable($conflict)) {
            return;
        }

        if ($desvinculado && ! $periodService->isRehireable($conflict)) {
            $fail(self::PREFIX_REHIRE_NOT_ALLOWED.' Esta cédula corresponde a un empleado desvinculado no recontratable.');

            return;
        }

        if ($conflict->personal_requisition_id === null) {
            $fail(self::PREFIX_DUPLICATE.' Esta cédula ya está registrada en Ficha empleados (sin requisición vinculada). Confirme para vincularla a esta RQ.');

            return;
        }

        $code = $conflict->requisition?->code ?? ('#'.$conflict->personal_requisition_id);

        $fail(self::PREFIX_DUPLICATE." Esta cédula ya está registrada en otra requisición ({$code}). Confirme para continuar.");
    }

    private function isDesvinculadoInFicha(PersonalRequisitionFichaEntry $conflict): bool
    {
        return $conflict->moved_to_ficha_at !== null
            && $conflict->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO;
    }
}
