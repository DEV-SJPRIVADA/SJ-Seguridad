<?php

namespace App\Http\Requests\Supplies;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\SupplyRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Calidad aprueba o rechaza un suministro pendiente. Las observaciones se guardan en mayúsculas.
 */
class ResolveSupplyQualityRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $this->route('supply_request') instanceof SupplyRequest
            && $user->canAccessSupplyTab((string) $this->route('module'), 'quality');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'quality_observations' => ['nullable', 'string', 'max:5000'],
            'items' => ['required_if:action,approve', 'array'],
            'items.*.approved_quantity' => ['required_if:action,approve', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'quality_observations' => 'observaciones de aprobación',
            'items.*.approved_quantity' => 'cantidad autorizada',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['action'];
    }
}
