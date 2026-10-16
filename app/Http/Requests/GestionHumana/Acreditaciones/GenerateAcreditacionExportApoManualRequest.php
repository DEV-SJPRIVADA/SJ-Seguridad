<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Exports\AcreditacionExportApoXlsExport;
use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAcreditacionExportApoManualRequest extends FormRequest
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
            'export_mode' => [
                'required',
                'string',
                Rule::in([
                    AcreditacionExportApoXlsExport::MODE_ACREDITAR,
                    AcreditacionExportApoXlsExport::MODE_RETIRAR,
                ]),
            ],
            'rows' => ['required', 'array', 'min:1', 'max:'.$max],
            'rows.*.document_number' => ['nullable', 'string', 'max:50'],
            'rows.*.nombre1' => ['nullable', 'string', 'max:255'],
            'rows.*.nombre2' => ['nullable', 'string', 'max:255'],
            'rows.*.apellido1' => ['nullable', 'string', 'max:255'],
            'rows.*.apellido2' => ['nullable', 'string', 'max:255'],
            'rows.*.fecha_nacimiento' => ['nullable', 'string', 'max:20'],
            'rows.*.genero' => ['nullable', 'string', 'max:10'],
            'rows.*.cargo' => ['nullable', 'string', 'max:255'],
            'rows.*.fecha_vinculacion' => ['nullable', 'string', 'max:20'],
            'rows.*.codigo_curso' => ['nullable', 'string', 'max:50'],
            'rows.*.nit_escuela' => ['nullable', 'string', 'max:30'],
            'rows.*.nro' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rows.required' => 'Agregue al menos una fila.',
            'rows.min' => 'Agregue al menos una fila.',
            'export_mode.required' => 'Indique si desea acreditar o retirar.',
            'export_mode.in' => 'Modo de exportación no válido.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->validated('rows');

        return array_values($rows);
    }

    public function exportMode(): string
    {
        return (string) $this->validated('export_mode');
    }
}
