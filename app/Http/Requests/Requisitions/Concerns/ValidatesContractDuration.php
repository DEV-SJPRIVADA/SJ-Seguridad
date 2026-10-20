<?php

namespace App\Http\Requests\Requisitions\Concerns;

use App\Models\RequisitionContractType;
use Illuminate\Validation\Validator;

/**
 * Duración del contrato según tipo: obligatoria si hay tipo; Fijo solo admite 6 o 12 (meses).
 * Otros tipos: texto libre (la UI lo prellena con el nombre del tipo).
 */
trait ValidatesContractDuration
{
    protected function validateContractDuration(Validator $validator): void
    {
        $typeId = $this->input('contract_type_id');

        if ($typeId === null || $typeId === '') {
            return;
        }

        $duration = trim((string) $this->input('contract_duration', ''));

        if ($duration === '') {
            $validator->errors()->add('contract_duration', 'La duración del contrato es obligatoria.');

            return;
        }

        $type = RequisitionContractType::query()->find((int) $typeId);

        if ($type?->isFixedTerm() && ! array_key_exists($duration, RequisitionContractType::FIXED_TERM_DURATIONS)) {
            $validator->errors()->add('contract_duration', 'Para contrato Fijo la duración debe ser 6 o 12 meses.');
        }
    }
}
