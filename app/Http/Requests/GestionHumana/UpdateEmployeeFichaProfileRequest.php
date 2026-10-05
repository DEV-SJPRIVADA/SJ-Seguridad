<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\GestionHumana\Concerns\EmployeeFichaProfileFieldRules;
use App\Services\Access\FichaEmpleadosAccessService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeFichaProfileRequest extends FormRequest
{
    use EmployeeFichaProfileFieldRules;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(FichaEmpleadosAccessService::class)->canManage($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->employeeFichaProfileFieldRules(requireCore: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->employeeFichaProfileFieldAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->employeeFichaProfileFieldMessages();
    }
}
