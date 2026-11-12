<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\PurchaseAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseQuotationRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * Solo Compras (bandeja) carga cotizaciones.
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
        $maxPerBatch = max(1, (int) config('purchase-requests.quotations.max_per_batch', 3));

        return [
            'lote' => ['nullable', 'string', 'max:36'],
            'lote_label' => ['nullable', 'string', 'max:120', 'required_without:lote'],
            'files' => ['required', 'array', 'min:1', 'max:'.$maxPerBatch],
            'files.*' => [
                'required',
                'file',
                'mimes:'.implode(',', (array) config('purchase-requests.quotations.mimes', ['pdf', 'jpg', 'jpeg', 'png'])),
                'max:'.(int) config('purchase-requests.quotations.max_kilobytes', 10240),
            ],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lote_label.required_without' => 'Indique una referencia para el grupo de cotizaciones (por ejemplo, OC 1 · Asociado A).',
            'files.required' => 'Seleccione al menos un archivo de cotización.',
            'files.max' => 'Cada grupo admite hasta 3 cotizaciones.',
            'files.*.mimes' => 'La cotización debe ser PDF, JPG o PNG.',
            'files.*.max' => 'La cotización no puede superar los 10 MB.',
            'descriptions.*.max' => 'La descripción no puede superar los 500 caracteres.',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['lote', 'files'];
    }
}
