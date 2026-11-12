<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Services\Access\PurchaseAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestPurchaseQuotationApprovalRequest extends FormRequest
{
    /**
     * Compras elige el director o administrador que decide.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PurchaseAccessService::class)->canEmitOrder($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'aprobador_id' => ['required', 'integer', $this->validApprover()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aprobador_id.required' => 'Seleccione el director que aprobará la cotización.',
        ];
    }

    private function validApprover(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! app(PurchaseAccessService::class)->isValidApprover($value)) {
                $fail('Seleccione un director o administrador activo con permiso para autorizar.');
            }
        };
    }
}
