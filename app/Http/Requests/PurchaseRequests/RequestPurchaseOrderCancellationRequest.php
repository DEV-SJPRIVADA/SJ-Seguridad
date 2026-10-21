<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\PurchaseAccessService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Compras pide anular una OC: elige el director que aprueba y escribe el motivo.
 */
class RequestPurchaseOrderCancellationRequest extends FormRequest
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
            'anulacion_aprobador_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! app(PurchaseAccessService::class)->isValidApprover($value)) {
                        $fail('Seleccione un director activo con permiso para autorizar.');
                    }
                },
            ],
            'motivo_anulacion' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'anulacion_aprobador_id' => 'director que aprueba',
            'motivo_anulacion' => 'motivo de la anulación',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['anulacion_aprobador_id'];
    }
}
