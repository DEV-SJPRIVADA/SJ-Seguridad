<?php

namespace App\Http\Requests\Requisitions;

use App\Http\Requests\Requisitions\Concerns\ResolvesCommercialClient;
use App\Http\Requests\Requisitions\Concerns\ResolvesReplacementPersonFields;
use App\Models\PersonalRequisition;
use App\Rules\Requisitions\ValidRequisitionRecruiterUser;
use App\Services\Access\RequisitionAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalRequisitionRequest extends FormRequest
{
    use ResolvesCommercialClient;
    use ResolvesReplacementPersonFields;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $module = (string) $this->route('module');
        $requisition = $this->route('requisition');
        $access = app(RequisitionAccessService::class);

        if ($user === null || ! $access->canAccessTab($user, $module, 'gestion')) {
            return false;
        }

        if ($requisition instanceof PersonalRequisition) {
            if ($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA) {
                return false;
            }

            return $access->canAccessRequisitionRecord($user, $module, $requisition->requesting_area_key);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isHired = $this->input('status') === PersonalRequisition::STATUS_CONTRATADO;
        $requisition = $this->route('requisition');
        $existingRecruiterId = $requisition instanceof PersonalRequisition ? $requisition->recruiter_id : null;

        return [
            'position_id' => ['required', 'integer', Rule::exists('requisition_positions', 'id')],
            'sex' => ['required', 'string', Rule::in(['masculino', 'femenino', 'indiferente'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'replacement_document' => $this->replacementDocumentRules(),
            'replacement_name' => $this->replacementNameRules(),
            'operating_area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'request_reason_id' => ['required', 'integer', Rule::exists('requisition_request_reasons', 'id')],
            ...$this->commercialClientRules(),
            'city_id' => ['required', 'integer', Rule::exists('requisition_cities', 'id')],
            'client_type_id' => ['required', 'integer', Rule::exists('requisition_client_types', 'id')],
            'programming_type_id' => ['required', 'integer', Rule::exists('requisition_programming_types', 'id')],
            'required_profile' => ['required', 'string'],
            'uniform_id' => ['required', 'integer', Rule::exists('requisition_uniforms', 'id')],
            'service_structure' => ['required', 'string'],

            // Campos de GH condicionales: Solo obligatorios si el estado es 'contratado'
            'contract_type_id' => [$isHired ? 'required' : 'nullable', 'integer', Rule::exists('requisition_contract_types', 'id')],
            'contract_duration' => [$isHired ? 'required' : 'nullable', 'string', 'max:255'],
            'base_salary' => [$isHired ? 'required' : 'nullable', 'numeric', 'min:0'],
            'transport_allowance' => [$isHired ? 'required' : 'nullable', 'numeric', 'min:0'],
            'mobility_allowance' => ['nullable', 'numeric', 'min:0'],
            'statutory_bonus' => [$isHired ? 'required' : 'nullable', 'numeric', 'min:0'],
            'non_statutory_bonus' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'string', 'max:500'],
            'leasing_contract' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['required', 'string', 'max:255'],

            'requester_observation' => ['nullable', 'string'],
            'human_resources_observation' => ['nullable', 'string'],
            'recruiter_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                new ValidRequisitionRecruiterUser($existingRecruiterId !== null ? (int) $existingRecruiterId : null),
            ],
            'hiring_date' => [$isHired ? 'required' : 'nullable', 'date'],
            'status' => ['required', 'string', Rule::in(array_keys(PersonalRequisition::statuses()))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $requisition = $this->route('requisition');

            if (! $requisition instanceof PersonalRequisition) {
                return;
            }

            if ($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA) {
                $validator->errors()->add('status', 'La requisicion debe ser autorizada por gerencia antes de gestionarla.');
            }

            if ($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA
                && $this->input('status') === PersonalRequisition::STATUS_EN_GESTION) {
                $validator->errors()->add('status', 'No puede pasar a en gestion sin autorizacion de gerencia.');
            }
        });
    }
}
