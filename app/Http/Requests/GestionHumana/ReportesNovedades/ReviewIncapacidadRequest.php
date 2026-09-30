<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canReview($user, 'incapacidades');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'observacion_nomina' => ['nullable', 'string', 'max:255'],
            'dias_entrega' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('observacion_nomina') && $this->input('observacion_nomina') !== null) {
            $value = trim((string) $this->input('observacion_nomina'));
            $this->merge([
                'observacion_nomina' => $value === '' ? null : $value,
            ]);
        }

        if ($this->exists('dias_entrega') && $this->input('dias_entrega') === '') {
            $this->merge(['dias_entrega' => null]);
        }
    }
}
