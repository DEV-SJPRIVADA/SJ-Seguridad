<?php

namespace App\Http\Requests\Requisitions;

use App\Models\PersonalRequisition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonalRequisitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position_id' => ['required', 'integer', Rule::exists('requisition_positions', 'id')],
            'sex' => ['required', 'string', Rule::in(['masculino', 'femenino', 'indiferente'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'replacement_document' => ['required_if:request_reason_id,2', 'nullable', 'string', 'max:50'],
            'replacement_name' => ['required_if:request_reason_id,2', 'nullable', 'string', 'max:255'],
            'operating_area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'request_reason_id' => ['required', 'integer', Rule::exists('requisition_request_reasons', 'id')],
            'client_id' => ['required', 'integer', Rule::exists('requisition_clients', 'id')],
            'city_id' => ['required', 'integer', Rule::exists('requisition_cities', 'id')],
            'client_type_id' => ['required', 'integer', Rule::exists('requisition_client_types', 'id')],
            'programming_type_id' => ['required', 'integer', Rule::exists('requisition_programming_types', 'id')],
            'required_profile' => ['required', 'string'],
            'uniform_id' => ['required', 'integer', Rule::exists('requisition_uniforms', 'id')],
            'contract_type_id' => ['nullable', 'integer', Rule::exists('requisition_contract_types', 'id')],
            'contract_duration' => ['nullable', 'string', 'max:255'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'mobility_allowance' => ['nullable', 'numeric', 'min:0'],
            'statutory_bonus' => ['nullable', 'numeric', 'min:0'],
            'non_statutory_bonus' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'string', 'max:500'],
            'leasing_contract' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['required', 'string', 'max:255'],
            'requester_observation' => ['nullable', 'string'],
            'human_resources_observation' => ['nullable', 'string'],
            'recruiter_id' => ['nullable', 'integer', Rule::exists('requisition_recruiters', 'id')],
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'hiring_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(PersonalRequisition::statuses()))],
        ];
    }
}
