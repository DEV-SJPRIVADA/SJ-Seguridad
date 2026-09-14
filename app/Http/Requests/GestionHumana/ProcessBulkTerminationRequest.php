<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcessBulkTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DesvinculacionesAccessService::class)->canMasivos($user);
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('rows');
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('is_rehireable', $row) && ($row['is_rehireable'] === '' || $row['is_rehireable'] === null)) {
                $rows[$index]['is_rehireable'] = null;
            }

            if (array_key_exists('termination_cause_code', $row) && trim((string) $row['termination_cause_code']) === '') {
                $rows[$index]['termination_cause_code'] = null;
            }

            if (array_key_exists('termination_notes', $row) && trim((string) ($row['termination_notes'] ?? '')) === '') {
                $rows[$index]['termination_notes'] = null;
            }
        }

        $this->merge(['rows' => $rows]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.document_number' => ['required', 'string', 'max:50'],
            'rows.*.termination_date' => ['required', 'date'],
            'rows.*.template_id' => ['required', 'integer', 'exists:termination_letter_document_templates,id'],
            'rows.*.signatory_id' => [
                'required',
                'integer',
                Rule::exists('payroll_catalog_items', 'id')->where('catalog_type', 'firmas'),
            ],
            'rows.*.termination_cause_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where('catalog_type', 'termination_cause'),
            ],
            'rows.*.is_rehireable' => ['nullable', 'boolean'],
            'rows.*.termination_notes' => ['nullable', 'string', 'max:1000'],
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
                    $doc = trim((string) ($row['document_number'] ?? ''));
                    if ($doc === '') {
                        continue;
                    }

                    $key = mb_strtolower($doc);
                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "rows.{$index}.document_number",
                            'La cedula esta duplicada en el lote (fila '.($seen[$key] + 1).').',
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
            'rows.*.document_number' => 'cedula',
            'rows.*.termination_date' => 'fecha de desvinculacion',
            'rows.*.template_id' => 'tipo de carta',
            'rows.*.signatory_id' => 'firma',
            'rows.*.termination_cause_code' => 'causal',
            'rows.*.is_rehireable' => 'recontratable',
            'rows.*.termination_notes' => 'observaciones',
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
}
