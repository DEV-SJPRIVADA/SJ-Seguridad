<?php

namespace App\Http\Requests\GestionHumana\Inasistencias;

use App\Services\Access\InasistenciasAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateInasistenciasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(InasistenciasAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('inasistencias.max_rows', 500);

        return [
            'rows' => ['required', 'array', 'min:1', 'max:'.$max],
            'rows.*.cedula' => ['required', 'string', 'max:50'],
            'rows.*.nombre_completo' => ['required', 'string', 'max:255'],
            'rows.*.cargo' => ['nullable', 'string', 'max:255'],
            'rows.*.ciudad' => ['nullable', 'string', 'max:255'],
            'rows.*.fecha_ini_aus' => ['required', 'date'],
            'rows.*.signatory_id' => [
                'required',
                'integer',
                Rule::exists('payroll_catalog_items', 'id')
                    ->where('catalog_type', 'firmas')
                    ->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var list<array<string, mixed>>|null $rows */
                $rows = $this->input('rows');
                if (! is_array($rows)) {
                    return;
                }

                $seen = [];
                foreach ($rows as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $cedula = trim((string) ($row['cedula'] ?? ''));
                    if ($cedula === '') {
                        continue;
                    }

                    $key = mb_strtolower($cedula);
                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "rows.{$index}.cedula",
                            'La cédula está duplicada en el lote (fila '.($seen[$key] + 1).').',
                        );
                    } else {
                        $seen[$key] = $index;
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rows' => 'filas',
            'rows.*.cedula' => 'cédula',
            'rows.*.nombre_completo' => 'nombre',
            'rows.*.cargo' => 'cargo',
            'rows.*.ciudad' => 'ciudad',
            'rows.*.fecha_ini_aus' => 'fecha inicio ausencia',
            'rows.*.signatory_id' => 'firma',
        ];
    }

    /**
     * @return list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     cargo: string,
     *     ciudad: string,
     *     fecha_ini_aus: string,
     *     signatory_id: int
     * }>
     */
    public function rows(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->validated('rows');

        return array_values(array_map(static function (array $row): array {
            return [
                'cedula' => trim((string) $row['cedula']),
                'nombre_completo' => trim((string) $row['nombre_completo']),
                'cargo' => trim((string) ($row['cargo'] ?? '')),
                'ciudad' => trim((string) ($row['ciudad'] ?? '')),
                'fecha_ini_aus' => (string) $row['fecha_ini_aus'],
                'signatory_id' => (int) $row['signatory_id'],
            ];
        }, $rows));
    }
}
