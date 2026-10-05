<?php

namespace App\Http\Requests\GestionHumana;

use App\Rules\GestionHumana\PayrollCatalogCode;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Support\ColombianCurrencyParser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateQuickContratacionLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(FichaEmpleadosAccessService::class)->canManage($user);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('salary')) {
            $this->merge([
                'salary' => ColombianCurrencyParser::parse($this->input('salary')),
            ]);
        }

        if ($this->has('document_number')) {
            $digits = preg_replace('/\D+/', '', (string) $this->input('document_number'));
            $this->merge([
                'document_number' => $digits !== '' ? $digits : $this->input('document_number'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:30'],
            'birth_place' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'residence_city_code' => ['required', 'string', 'max:20', new PayrollCatalogCode('city')],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:100'],
            'birth_date' => ['required', 'date'],
            'salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'hire_date' => ['required', 'date'],
            'position_name' => ['required', 'string', 'max:150'],
            'position_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('position')],
            'template_ids' => ['required', 'array', 'min:1'],
            'template_ids.*' => ['required', 'integer', 'distinct', 'exists:termination_letter_document_templates,id'],
            'signatory_id' => ['required', 'integer', 'exists:payroll_catalog_items,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre completo es obligatorio.',
            'document_number.required' => 'El documento es obligatorio.',
            'birth_place.required' => 'El lugar de nacimiento es obligatorio.',
            'address.required' => 'La dirección es obligatoria.',
            'residence_city_code.required' => 'La ciudad de residencia es obligatoria.',
            'phone.required' => 'El teléfono es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'salary.required' => 'El salario es obligatorio.',
            'hire_date.required' => 'La fecha de ingreso es obligatoria.',
            'position_name.required' => 'El cargo es obligatorio.',
            'template_ids.required' => 'Debe seleccionar al menos una plantilla.',
            'template_ids.min' => 'Debe seleccionar al menos una plantilla.',
            'signatory_id.required' => 'Debe seleccionar un firmante.',
        ];
    }

    /**
     * @return array{
     *     full_name: string,
     *     document_number: string,
     *     birth_place: string,
     *     address: string,
     *     residence_city_code: string,
     *     phone: string,
     *     email: string,
     *     birth_date: string,
     *     salary: float|int|string,
     *     hire_date: string,
     *     position_name: string,
     *     position_code?: string|null,
     *     template_ids: list<int>,
     *     signatory_id: int
     * }
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        /** @var list<int|string> $templateIds */
        $templateIds = $validated['template_ids'];

        return [
            'full_name' => (string) $validated['full_name'],
            'document_number' => (string) $validated['document_number'],
            'birth_place' => (string) $validated['birth_place'],
            'address' => (string) $validated['address'],
            'residence_city_code' => (string) $validated['residence_city_code'],
            'phone' => (string) $validated['phone'],
            'email' => (string) $validated['email'],
            'birth_date' => (string) $validated['birth_date'],
            'salary' => $validated['salary'],
            'hire_date' => (string) $validated['hire_date'],
            'position_name' => (string) $validated['position_name'],
            'position_code' => isset($validated['position_code']) && $validated['position_code'] !== ''
                ? (string) $validated['position_code']
                : null,
            'template_ids' => array_values(array_map(
                static fn (int|string $id): int => (int) $id,
                $templateIds,
            )),
            'signatory_id' => (int) $validated['signatory_id'],
        ];
    }
}
