<?php

namespace App\Http\Requests\Requisitions;

use App\Services\Access\RequisitionAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequisitionSelectionOfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $module = (string) $this->route('module');

        if ($user === null) {
            return false;
        }

        return app(RequisitionAccessService::class)->canAccessTab($user, $module, 'parametros');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
