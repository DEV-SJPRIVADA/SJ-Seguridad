<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Decisión del aprobador sobre la OC (plataforma o enlace firmado del correo).
 * Solo decide el aprobador elegido; el comentario es obligatorio al rechazar.
 */
class ResolvePurchaseOrderApprovalRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $order = $this->route('purchase_order');

        if (! $order instanceof PurchaseOrder) {
            return false;
        }

        if ($this->routeIs('purchase-requests.order-approval-email.*')) {
            $directorId = (int) $this->query('director', $this->input('director'));

            return $this->hasValidSignature()
                && $directorId > 0
                && (int) $order->aprobacion_aprobador_id === $directorId;
        }

        $user = $this->user();

        return $user !== null
            && $user->can('purchase.tab.approval')
            && (int) $order->aprobacion_aprobador_id === (int) $user->id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:aprobar,rechazar'],
            'comentario_aprobacion' => ['nullable', 'required_if:decision,rechazar', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comentario_aprobacion.required_if' => 'Indique el motivo del rechazo.',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['decision'];
    }
}
