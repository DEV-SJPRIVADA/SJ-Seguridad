<?php

namespace App\Rules\Requisitions;

use App\Services\Requisitions\RequisitionSelectionOfficerAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRequisitionRecruiterUser implements ValidationRule
{
    public function __construct(private readonly ?int $existingRecruiterId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $userId = (int) $value;
        $service = app(RequisitionSelectionOfficerAccessService::class);

        if (! $service->isAllowedRecruiterId($userId, $this->existingRecruiterId)) {
            $fail('El reclutador seleccionado no esta habilitado como encargado de seleccion.');
        }
    }
}
