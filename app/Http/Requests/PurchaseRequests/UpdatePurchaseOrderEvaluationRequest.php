<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseOrder;
use App\Services\Access\PurchaseAccessService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderEvaluationRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * La evaluación la guarda quien edita proveedores, y solo sobre una OC emitida.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $order = $this->route('purchase_order');

        return $user !== null
            && $order instanceof PurchaseOrder
            && $order->estado === 'emitida'
            && app(PurchaseAccessService::class)->canEditProviders($user);
    }

    /**
     * La fecha puede guardarse sola. Si hay calificación, van las tres.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha_recibido' => ['required', 'date'],
            'calif_referencias_cantidades' => [
                'nullable',
                'integer',
                'between:1,5',
                'required_with:calif_oportunidad_entrega,calif_calidad_producto',
            ],
            'calif_oportunidad_entrega' => [
                'nullable',
                'integer',
                'between:1,5',
                'required_with:calif_referencias_cantidades,calif_calidad_producto',
            ],
            'calif_calidad_producto' => [
                'nullable',
                'integer',
                'between:1,5',
                'required_with:calif_referencias_cantidades,calif_oportunidad_entrega',
            ],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_recibido.required' => 'Indique la fecha de recibido.',
            'fecha_recibido.date' => 'La fecha de recibido no es válida.',
            'calif_referencias_cantidades.required_with' => 'Si califica la entrega, complete las tres calificaciones.',
            'calif_oportunidad_entrega.required_with' => 'Si califica la entrega, complete las tres calificaciones.',
            'calif_calidad_producto.required_with' => 'Si califica la entrega, complete las tres calificaciones.',
            'calif_referencias_cantidades.between' => 'La calificación de referencias y cantidades debe estar entre 1 y 5.',
            'calif_oportunidad_entrega.between' => 'La calificación de oportunidad de entrega debe estar entre 1 y 5.',
            'calif_calidad_producto.between' => 'La calificación de calidad del producto debe estar entre 1 y 5.',
            'calif_referencias_cantidades.integer' => 'La calificación de referencias y cantidades debe ser un número entero.',
            'calif_oportunidad_entrega.integer' => 'La calificación de oportunidad de entrega debe ser un número entero.',
            'calif_calidad_producto.integer' => 'La calificación de calidad del producto debe ser un número entero.',
            'observaciones.max' => 'Las observaciones no pueden superar los 5000 caracteres.',
        ];
    }

    /**
     * La fecha y las calificaciones no se pasan a mayúsculas.
     *
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return [
            'fecha_recibido',
            'calif_referencias_cantidades',
            'calif_oportunidad_entrega',
            'calif_calidad_producto',
        ];
    }
}
