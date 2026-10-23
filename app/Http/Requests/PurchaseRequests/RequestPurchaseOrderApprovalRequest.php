<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\PurchaseAccessService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Compras pide aprobar una OC: elige director o administrador y puede dejar una nota.
 */
class RequestPurchaseOrderApprovalRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PurchaseAccessService::class)->canEmitOrder($user);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'aprobacion_aprobador_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! app(PurchaseAccessService::class)->isValidApprover($value)) {
                        $fail('Seleccione un director o administrador activo con permiso para autorizar.');
                    }
                },
            ],
            'aprobacion_nota' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'aprobacion_aprobador_id' => 'aprobador',
            'aprobacion_nota' => 'nota',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['aprobacion_aprobador_id'];
    }
}
