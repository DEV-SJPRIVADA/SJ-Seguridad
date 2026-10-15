<?php

namespace App\Rules\Requisitions;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HiredDocumentNotDuplicated implements ValidationRule
{
    public function __construct(
        private readonly ?int $requisitionId,
        private readonly bool $confirmed,
        private readonly ?string $confirmedDocument,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = trim((string) $value);

        if ($this->confirmed && $document !== '' && $document === trim((string) $this->confirmedDocument)) {
            return;
        }

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

        if ($conflict->moved_to_ficha_at !== null
            && $conflict->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO
            && $periodService->isRehireable($conflict)) {
            return;
        }

        if ($conflict->moved_to_ficha_at !== null
            && $conflict->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO
            && ! $periodService->isRehireable($conflict)) {
            $fail('REHIRE_NOT_ALLOWED: Esta cédula corresponde a un empleado desvinculado no recontratable.');

            return;
        }

        if ($conflict->personal_requisition_id === null) {
            $fail('DUPLICATE_HIRED_DOCUMENT: Esta cédula ya está registrada en Ficha empleados (sin requisición vinculada). Confirme para vincularla a esta RQ.');

            return;
        }

        $code = $conflict->requisition?->code ?? ('#'.$conflict->personal_requisition_id);

        $fail("DUPLICATE_HIRED_DOCUMENT: Esta cédula ya está registrada en otra requisición ({$code}). Confirme para continuar.");
    }
}
