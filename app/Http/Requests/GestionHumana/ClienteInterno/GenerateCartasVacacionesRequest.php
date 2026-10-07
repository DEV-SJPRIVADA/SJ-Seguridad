<?php

namespace App\Http\Requests\GestionHumana\ClienteInterno;

use App\Services\Access\ClienteInternoAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateCartasVacacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ClienteInternoAccessService::class)->canEditCartasVacaciones($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('cliente_interno.cartas_vacaciones.max_rows', 500);

        return [
            'rows' => ['required', 'array', 'min:1', 'max:'.$max],
            'rows.*.cedula' => ['required', 'string', 'max:50'],
            'rows.*.nombre_completo' => ['required', 'string', 'max:255'],
            'rows.*.fecha_inicio' => ['required', 'date'],
            'rows.*.fecha_fin' => ['required', 'date', 'after_or_equal:rows.*.fecha_inicio'],
            'rows.*.fecha_reintegro' => ['required', 'date', 'after_or_equal:rows.*.fecha_fin'],
            'rows.*.periodos' => ['required', 'string', 'max:500'],
            'rows.*.dias_disfrutados' => ['required', 'string', 'max:50'],
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
                    if ($cedula !== '') {
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

                    // Refuerzo de fechas por fila (after_or_equal con comodines a veces es ambiguo).
                    $inicio = $row['fecha_inicio'] ?? null;
                    $fin = $row['fecha_fin'] ?? null;
                    $reintegro = $row['fecha_reintegro'] ?? null;

                    if ($inicio && $fin) {
                        try {
                            if (Carbon::parse((string) $fin)->lt(Carbon::parse((string) $inicio))) {
                                $validator->errors()->add(
                                    "rows.{$index}.fecha_fin",
                                    'La fecha fin debe ser mayor o igual a la fecha de inicio.',
                                );
                            }
                        } catch (\Throwable) {
                            // Las reglas date ya reportan formato inválido.
                        }
                    }

                    if ($fin && $reintegro) {
                        try {
                            if (Carbon::parse((string) $reintegro)->lt(Carbon::parse((string) $fin))) {
                                $validator->errors()->add(
                                    "rows.{$index}.fecha_reintegro",
                                    'La fecha de reintegro debe ser mayor o igual a la fecha fin.',
                                );
                            }
                        } catch (\Throwable) {
                            // Las reglas date ya reportan formato inválido.
                        }
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
            'rows.*.nombre_completo' => 'nombre completo',
            'rows.*.fecha_inicio' => 'fecha de inicio',
            'rows.*.fecha_fin' => 'fecha fin',
            'rows.*.fecha_reintegro' => 'fecha de reintegro',
            'rows.*.periodos' => 'periodos',
            'rows.*.dias_disfrutados' => 'días disfrutados',
            'rows.*.signatory_id' => 'firma',
        ];
    }

    /**
     * @return list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     fecha_inicio: string,
     *     fecha_fin: string,
     *     fecha_reintegro: string,
     *     periodos: string,
     *     dias_disfrutados: string,
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
                'fecha_inicio' => (string) $row['fecha_inicio'],
                'fecha_fin' => (string) $row['fecha_fin'],
                'fecha_reintegro' => (string) $row['fecha_reintegro'],
                'periodos' => trim((string) $row['periodos']),
                'dias_disfrutados' => trim((string) $row['dias_disfrutados']),
                'signatory_id' => (int) $row['signatory_id'],
            ];
        }, $rows));
    }
}
