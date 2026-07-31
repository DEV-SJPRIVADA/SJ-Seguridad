<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase.tab.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'fecha_solicitud' => ['required', 'date', 'before_or_equal:today'],
            'archivo_pedido' => ['nullable', 'file', 'max:10240'],
            'solicitud_para' => ['required', 'in:Interno,Cliente'],
            'urgente' => ['required', 'boolean'],
            'aprobador_id' => ['required', 'exists:users,id'],
            'proyecto_nuevo' => ['nullable', 'boolean'],
            'razon_social' => ['nullable', 'string', 'max:255', 'required_if:solicitud_para,Cliente'],
            'asume_cliente' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.descripcion' => ['required', 'string', 'max:5000'],
            'items.*.referencia' => ['required', 'string', 'max:255'],
            'items.*.utilizacion' => ['required', 'string', 'max:1000'],
            'items.*.ubicacion' => ['required', 'string', 'max:255'],
            'items.*.foto' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
