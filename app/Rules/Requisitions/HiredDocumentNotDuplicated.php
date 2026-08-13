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

        $conflict = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->when($this->requisitionId !== null, fn ($query) => $query->where('personal_requisition_id', '!=', $this->requisitionId))
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
            $fail('REHIRE_NOT_ALLOWED: Esta cedula corresponde a un empleado desvinculado no recontratable.');

            return;
        }

        $code = $conflict->requisition?->code ?? ('#'.$conflict->personal_requisition_id);

        $fail("DUPLICATE_HIRED_DOCUMENT: Esta cedula ya esta registrada en otra requisicion ({$code}). Confirme para continuar.");
    }
}
