<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseQuotation;
use App\Services\Access\PurchaseAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ResolvePurchaseQuotationApprovalRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * Decide el aprobador elegido, en la plataforma o desde el enlace firmado del correo.
     * La decisión es por lote: al aprobar debe elegir una cotización ganadora.
     */
    public function authorize(): bool
    {
        $quotations = $this->batchQuotations();

        if ($quotations->isEmpty()) {
            return false;
        }

        if ($this->routeIs('purchase-requests.quotation-email.*')) {
            $directorId = (int) $this->query('director', $this->input('director'));

            return $this->hasValidSignature()
                && $directorId > 0
                && $quotations->every(fn (PurchaseQuotation $quotation): bool => (int) $quotation->aprobador_id === $directorId);
        }

        $user = $this->user();

        return $user !== null
            && ($user->can('purchase.tab.approval') || app(PurchaseAccessService::class)->isAdminBypass($user))
            && $quotations->every(fn (PurchaseQuotation $quotation): bool => (int) $quotation->aprobador_id === (int) $user->id);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'purchase_quotation_id' => ['nullable', 'integer', 'required_if:decision,aprobar', $this->selectedQuotationBelongsToBatch()],
            'comentario' => ['nullable', 'string', 'max:5000', 'required_if:decision,rechazar'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Indique si aprueba o rechaza el grupo de cotizaciones.',
            'purchase_quotation_id.required_if' => 'Seleccione la cotización ganadora de este grupo.',
            'comentario.required_if' => 'Indique el motivo del rechazo.',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['decision', 'purchase_quotation_id'];
    }

    /**
     * La cotización ganadora debe pertenecer al lote enviado a aprobación.
     */
    private function selectedQuotationBelongsToBatch(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $belongs = $this->batchQuotations()
                ->contains(fn (PurchaseQuotation $quotation): bool => (int) $quotation->getKey() === (int) $value);

            if (! $belongs) {
                $fail('Seleccione una cotización de este grupo.');
            }
        };
    }

    /**
     * @return Collection<int, PurchaseQuotation>
     */
    private function batchQuotations(): Collection
    {
        $lote = (string) $this->route('lote');

        if ($lote === '') {
            return collect();
        }

        return PurchaseQuotation::query()
            ->where('lote', $lote)
            ->orderBy('lote_orden')
            ->orderBy('id')
            ->get();
    }
}
