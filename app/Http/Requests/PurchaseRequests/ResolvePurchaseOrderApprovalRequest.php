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
                && $order->esAprobadorAsignado($directorId);
        }

        $user = $this->user();

        if ($user === null || ! $order->esAprobadorAsignado((int) $user->id)) {
            return false;
        }

        if ($user->can('purchase.tab.approval')) {
            return true;
        }

        return $this->routeIs('supplies.approval.order-approval.*')
            && $order->esAprobadorDeSolicitud((int) $user->id)
            && $user->can('supply.tab.quality');
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
