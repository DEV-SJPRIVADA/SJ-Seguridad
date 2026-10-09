<?php

namespace App\Http\Requests\GestionHumana\MtSt04;

use App\Models\MtSt04Registro;
use App\Services\Access\MtSt04AccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMtSt04RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtSt04AccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var MtSt04Registro $registro */
        $registro = $this->route('registro');
        $siNo = array_keys(config('mt_st_04.si_no', ['SI' => 'SI', 'NO' => 'NO']));

        return [
            'document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mt_st_04_registros', 'document_number')->ignore($registro->id),
            ],
            'arma' => ['nullable', 'string', Rule::in($siNo)],
            'fecha_examen_1' => ['nullable', 'date'],
            'apto' => ['nullable', 'string', Rule::in($siNo)],
            'observaciones_1' => ['nullable', 'string', 'max:5000'],
            'fecha_examen_2' => ['nullable', 'date'],
            'observaciones_2' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'La cédula es obligatoria.',
            'document_number.unique' => 'Ya existe un registro en la matriz con esa cédula.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'arma' => $this->nullableSiNo('arma'),
            'apto' => $this->nullableSiNo('apto'),
            'fecha_examen_1' => $this->nullableDate('fecha_examen_1'),
            'fecha_examen_2' => $this->nullableDate('fecha_examen_2'),
            'observaciones_1' => $this->nullableTrim('observaciones_1'),
            'observaciones_2' => $this->nullableTrim('observaciones_2'),
        ]);
    }

    private function nullableTrim(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function nullableDate(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function nullableSiNo(string $key): ?string
    {
        $value = mb_strtoupper(trim((string) $this->input($key)), 'UTF-8');

        return $value === '' ? null : $value;
    }
}
