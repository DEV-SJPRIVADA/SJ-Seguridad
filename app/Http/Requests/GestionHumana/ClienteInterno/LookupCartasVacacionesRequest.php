<?php

namespace App\Http\Requests\GestionHumana\ClienteInterno;

use App\Services\Access\ClienteInternoAccessService;
use Illuminate\Foundation\Http\FormRequest;

class LookupCartasVacacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ClienteInternoAccessService::class)->canEditCartasVacaciones($user);
    }

    protected function prepareForValidation(): void
    {
        $numbers = $this->input('document_numbers');
        $single = $this->input('document_number');
        $cedula = $this->input('cedula');

        if (! is_array($numbers)) {
            $numbers = [];
        }

        if (is_string($single) && trim($single) !== '') {
            $numbers[] = $single;
        }

        if (is_string($cedula) && trim($cedula) !== '') {
            $numbers[] = $cedula;
        }

        $normalized = [];
        foreach ($numbers as $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        $this->merge([
            'document_numbers' => array_values($normalized),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('cliente_interno.cartas_vacaciones.max_rows', 500);

        return [
            'document_numbers' => ['required', 'array', 'min:1', 'max:'.$max],
            'document_numbers.*' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_numbers' => 'cédulas',
            'document_numbers.*' => 'cédula',
        ];
    }

    /**
     * @return list<string>
     */
    public function documentNumbers(): array
    {
        /** @var list<string> $numbers */
        $numbers = $this->validated('document_numbers');

        return array_values($numbers);
    }
}
