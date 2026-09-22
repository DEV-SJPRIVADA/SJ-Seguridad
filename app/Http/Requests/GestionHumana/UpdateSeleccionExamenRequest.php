<?php

namespace App\Http\Requests\GestionHumana;

use App\Models\SeleccionExamenOcupacional;
use App\Services\Access\SeleccionAccessService;
use App\Services\Requisitions\RequisitionSelectionOfficerAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeleccionExamenRequest extends FormRequest
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
        /** @var SeleccionExamenOcupacional|null $examen */
        $examen = $this->route('seleccionExamenOcupacional');
        $existingResponsableId = $examen?->responsable_user_id;

        $officerIds = app(RequisitionSelectionOfficerAccessService::class)
            ->recruitersForSelect($existingResponsableId)
            ->pluck('id')
            ->all();

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'position_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'position')->where('is_active', true)
                ),
            ],
            'servicio_sector' => ['required', 'string', 'max:255'],
            'commercial_client_id' => ['required', 'integer', Rule::exists('commercial_clients', 'id')],
            'eps_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'eps')->where('is_active', true)
                ),
            ],
            'afp_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'afp')->where('is_active', true)
                ),
            ],
            'birth_date' => ['required', 'date'],
            'city_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'city')->where('is_active', true)
                ),
            ],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:40'],
            'marital_status_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'marital_status')->where('is_active', true)
                ),
            ],
            'fecha_arl' => ['required', 'date'],
            'solicitud_status_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where(
                    fn ($q) => $q->where('catalog_type', 'seleccion_solicitud_status')->where('is_active', true)
                ),
            ],
            'responsable_user_id' => ['required', 'integer', Rule::in($officerIds)],
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
            'full_name.required' => 'Los apellidos y nombres son obligatorios.',
            'position_code.required' => 'El cargo es obligatorio.',
            'position_code.exists' => 'El cargo no existe o no está activo.',
            'servicio_sector.required' => 'El servicio/sector es obligatorio.',
            'commercial_client_id.required' => 'El cliente es obligatorio.',
            'commercial_client_id.exists' => 'El cliente no existe.',
            'eps_code.required' => 'La EPS es obligatoria.',
            'eps_code.exists' => 'La EPS no existe o no está activa.',
            'afp_code.required' => 'La pensión (AFP) es obligatoria.',
            'afp_code.exists' => 'La pensión (AFP) no existe o no está activa.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'city_code.required' => 'La ciudad es obligatoria.',
            'city_code.exists' => 'La ciudad no existe o no está activa.',
            'address.required' => 'La dirección es obligatoria.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'phone.required' => 'El celular es obligatorio.',
            'marital_status_code.required' => 'El estado civil es obligatorio.',
            'marital_status_code.exists' => 'El estado civil no existe o no está activo.',
            'fecha_arl.required' => 'La fecha de ARL es obligatoria.',
            'solicitud_status_code.required' => 'El estado de solicitud es obligatorio.',
            'solicitud_status_code.exists' => 'El estado de solicitud no existe o no está activo.',
            'responsable_user_id.required' => 'El responsable es obligatorio.',
            'responsable_user_id.in' => 'El responsable no es un reclutador activo válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'full_name' => trim((string) $this->input('full_name')),
            'servicio_sector' => trim((string) $this->input('servicio_sector')),
            'address' => trim((string) $this->input('address')),
            'email' => trim((string) $this->input('email')),
            'phone' => trim((string) $this->input('phone')),
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
    }
}
