<?php

namespace App\Http\Requests\Requisitions\Concerns;

use App\Models\RequisitionRequestReason;
use Illuminate\Validation\Rule;

trait ResolvesReplacementPersonFields
{
    /**
     * Motivos que exigen cedula y nombre de la persona involucrada.
     *
     * @var list<string>
     */
    public const REPLACEMENT_REASON_NAMES = [
        'reemplazo',
        'movimiento interno',
    ];

    /**
     * @return list<int>
     */
    protected function replacementReasonIds(): array
    {
        return RequisitionRequestReason::query()
            ->get(['id', 'name'])
            ->filter(fn (RequisitionRequestReason $reason): bool => in_array(
                strtolower(trim((string) $reason->name)),
                self::REPLACEMENT_REASON_NAMES,
                true
            ))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    protected function replacementDocumentRules(): array
    {
        return [
            Rule::requiredIf(fn (): bool => $this->requiresReplacementPerson()),
            'nullable',
            'string',
            'max:50',
        ];
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    protected function replacementNameRules(): array
    {
        return [
            Rule::requiredIf(fn (): bool => $this->requiresReplacementPerson()),
            'nullable',
            'string',
            'max:255',
        ];
    }

    protected function requiresReplacementPerson(): bool
    {
        $reasonId = (int) $this->input('request_reason_id');

        return $reasonId > 0 && in_array($reasonId, $this->replacementReasonIds(), true);
    }
}
