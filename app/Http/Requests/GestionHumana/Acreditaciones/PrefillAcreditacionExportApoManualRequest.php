<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrefillAcreditacionExportApoManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(AcreditacionesAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $max = (int) config('acreditaciones.limits.bulk_max_ids', 500);

        return [
            'document_number' => ['nullable', 'string', 'max:50'],
            'document_numbers' => ['nullable', 'array', 'max:'.$max],
            'document_numbers.*' => ['string', 'max:50'],
            // Cargo elegido en la grilla (número de cargo de acreditación): solo re-busca el curso.
            'cargo' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Número de cargo para re-buscar solo el curso; null = prefill completo.
     */
    public function cargo(): ?string
    {
        $cargo = trim((string) $this->input('cargo', ''));

        return $cargo !== '' ? $cargo : null;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $single = trim((string) $this->input('document_number', ''));
            $many = $this->input('document_numbers');
            $hasMany = is_array($many) && collect($many)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty();

            if ($single === '' && ! $hasMany) {
                $validator->errors()->add('document_number', 'Indique al menos una cédula.');
            }
        });
    }

    /**
     * @return list<string>
     */
    public function documents(): array
    {
        $many = $this->input('document_numbers');
        if (is_array($many) && $many !== []) {
            return array_values(array_filter(array_map(
                fn ($v): string => trim((string) $v),
                $many,
            ), fn (string $v): bool => $v !== ''));
        }

        $single = trim((string) $this->input('document_number', ''));

        return $single !== '' ? [$single] : [];
    }
}
