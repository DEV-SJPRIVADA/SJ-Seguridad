<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * Solo Compras (bandeja) puede emitir una OC.
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
            'provider_id' => [
                'required',
                'integer',
                Rule::exists('purchase_providers', 'id')->where('estado', 'activo'),
            ],
            'fecha' => ['required', 'date'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', $this->atLeastOneLineWithQuantity()],
            'items.*.cantidad' => [
                'required',
                'numeric',
                'min:0',
                'max:999999',
                $this->doesNotExceedPendingQuantity(),
            ],
            'items.*.articulo' => ['nullable', 'string', 'max:255'],
            'items.*.descripcion' => ['required', 'string', 'max:500'],
            'items.*.valor_unidad' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'items.*.iva' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider_id.exists' => 'Seleccione un proveedor activo.',
            'items.required' => 'La orden de compra debe tener al menos una línea.',
            'items.*.cantidad.required' => 'Cada línea debe tener cantidad.',
            'items.*.descripcion.required' => 'Cada línea debe tener descripción.',
            'items.*.valor_unidad.required' => 'Cada línea debe tener valor unitario.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fecha' => $this->input('fecha', now()->toDateString()),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['provider_id', 'fecha', 'ciudad'];
    }

    /**
     * Impide ordenar más unidades de las que quedan pendientes en el origen.
     */
    protected function doesNotExceedPendingQuantity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $line = $this->sourceLineForAttribute($attribute);

            if ($line === null) {
                if ($this->sourceLines() !== []) {
                    $fail('La línea no corresponde a la solicitud de origen.');
                }

                return;
            }

            $pendiente = (float) $line['cantidad_pendiente'];

            if ((float) $value - $pendiente > 0.001) {
                $fail(sprintf(
                    'La cantidad supera el saldo pendiente (%s). Ya se ordenaron %s de %s unidades.',
                    $this->formatQuantity($pendiente),
                    $this->formatQuantity((float) $line['cantidad_ordenada']),
                    $this->formatQuantity((float) $line['cantidad_solicitada']),
                ));
            }
        };
    }

    /**
     * Exige al menos una línea con cantidad mayor a cero.
     */
    protected function atLeastOneLineWithQuantity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $items = is_array($value) ? $value : [];

            foreach ($items as $item) {
                if (is_array($item) && (float) ($item['cantidad'] ?? 0) > 0) {
                    return;
                }
            }

            $fail('La orden de compra debe tener al menos una línea con cantidad mayor a cero.');
        };
    }

    /**
     * @return array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}|null
     */
    private function sourceLineForAttribute(string $attribute): ?array
    {
        if (! preg_match('/^items\.(\d+)\./', $attribute, $matches)) {
            return null;
        }

        $index = (int) $matches[1];

        foreach ($this->sourceLines() as $line) {
            if ($line['index'] === $index) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    private function sourceLines(): array
    {
        $service = app(PurchaseOrderService::class);

        $purchaseRequest = $this->route('purchase_request');

        if ($purchaseRequest instanceof PurchaseRequest) {
            return $service->sourceLines(PurchaseOrderService::SOURCE_PURCHASE, $purchaseRequest);
        }

        $supplyRequest = $this->route('supply_request');

        if ($supplyRequest instanceof SupplyRequest) {
            return $service->sourceLines(PurchaseOrderService::SOURCE_SUPPLY, $supplyRequest);
        }

        return [];
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }
}
