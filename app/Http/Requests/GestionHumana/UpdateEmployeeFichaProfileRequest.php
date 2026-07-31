<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\GestionHumana\Concerns\EmployeeFichaProfileFieldRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeFichaProfileRequest extends FormRequest
{
    use EmployeeFichaProfileFieldRules;

    public function authorize(): bool
    {
        return $this->user()?->can('ficha_empleados.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->employeeFichaProfileFieldRules();
    }
}
