<?php

namespace App\Http\Requests\GestionHumana;

use App\Models\SeleccionIngreso;
use App\Services\Access\SeleccionAccessService;
use App\Services\Requisitions\RequisitionSelectionOfficerAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeleccionIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(SeleccionAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var SeleccionIngreso|null $ingreso */
        $ingreso = $this->route('seleccionIngreso');
        $existingResponsableId = $ingreso?->responsable_user_id;

        $officerIds = app(RequisitionSelectionOfficerAccessService::class)
            ->recruitersForSelect($existingResponsableId)
            ->pluck('id')
            ->all();

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:40'],
            'city_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'city')->where('is_active', true)
                ),
            ],
            'position_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'position')->where('is_active', true)
                ),
            ],
            'commercial_client_id' => ['required', 'integer', Rule::exists('commercial_clients', 'id')],
            'shirt_size' => ['required', 'string', 'max:40'],
            'pants_size' => ['required', 'string', 'max:40'],
            'shoes_size' => ['required', 'string', 'max:40'],
            'requisition_uniform_id' => [
                'required',
                'integer',
                Rule::exists('requisition_uniforms', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_asistencia' => ['nullable', 'date'],
            'estado_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'ingreso_estado')->where('is_active', true)
                ),
            ],
            'blood_type_code' => [
                'required',
                'string',
                'max:20',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'blood_type')->where('is_active', true)
                ),
            ],
            'reemplaza_a' => ['required', 'string', 'max:255'],
            'responsable_user_id' => ['required', 'integer', Rule::in($officerIds)],
            'referido' => ['required', 'string', 'max:255'],
            'jefe_ope_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'jefe_operativo')->where('is_active', true)
                ),
            ],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'La cédula es obligatoria.',
            'full_name.required' => 'Los apellidos y nombre son obligatorios.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'phone.required' => 'El teléfono es obligatorio.',
            'city_code.required' => 'La ciudad es obligatoria.',
            'city_code.exists' => 'La ciudad no existe o no está activa.',
            'position_code.required' => 'El cargo es obligatorio.',
            'position_code.exists' => 'El cargo no existe o no está activo.',
            'commercial_client_id.required' => 'El cliente es obligatorio.',
            'commercial_client_id.exists' => 'El cliente no existe.',
            'shirt_size.required' => 'La talla de camisa es obligatoria.',
            'pants_size.required' => 'La talla de pantalón es obligatoria.',
            'shoes_size.required' => 'La talla de zapatos es obligatoria.',
            'requisition_uniform_id.required' => 'El tipo de dotación es obligatorio.',
            'requisition_uniform_id.exists' => 'El tipo de dotación no existe o no está activo.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_asistencia.date' => 'La fecha de asistencia no es válida.',
            'estado_code.required' => 'El estado es obligatorio.',
            'estado_code.exists' => 'El estado no existe o no está activo.',
            'blood_type_code.required' => 'El RH es obligatorio.',
            'blood_type_code.exists' => 'El RH no existe o no está activo.',
            'reemplaza_a.required' => 'El campo reemplaza a es obligatorio.',
            'responsable_user_id.required' => 'El responsable es obligatorio.',
            'responsable_user_id.in' => 'El responsable no es un reclutador activo válido.',
            'referido.required' => 'El campo referido es obligatorio.',
            'jefe_ope_code.required' => 'El jefe operativo es obligatorio.',
            'jefe_ope_code.exists' => 'El jefe operativo no existe o no está activo.',
            'observaciones.max' => 'Las observaciones no pueden superar 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'full_name' => trim((string) $this->input('full_name')),
            'email' => trim((string) $this->input('email')),
            'phone' => trim((string) $this->input('phone')),
            'shirt_size' => trim((string) $this->input('shirt_size')),
            'pants_size' => trim((string) $this->input('pants_size')),
            'shoes_size' => trim((string) $this->input('shoes_size')),
            'reemplaza_a' => trim((string) $this->input('reemplaza_a')),
            'referido' => trim((string) $this->input('referido')),
            'jefe_ope_code' => trim((string) $this->input('jefe_ope_code')),
            'observaciones' => trim((string) $this->input('observaciones')),
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
    }
}
