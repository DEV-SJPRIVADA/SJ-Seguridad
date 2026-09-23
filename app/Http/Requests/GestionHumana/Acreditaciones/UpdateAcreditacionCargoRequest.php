<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Models\AcreditacionCargo;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionCargoCatalogService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcreditacionCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(AcreditacionesAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AcreditacionCargo $acreditacionCargo */
        $acreditacionCargo = $this->route('acreditacionCargo');

        return [
            'cargo_manager' => [
                'required',
                'string',
                'max:255',
                Rule::unique('acreditacion_cargos', 'cargo_manager')
                    ->where(fn ($query) => $query->where('cargo_apo', $this->input('cargo_apo')))
                    ->ignore($acreditacionCargo->id),
            ],
            'cargo_apo' => ['required', 'string', 'max:255'],
            'cargo_informe' => ['required', 'string', 'max:255'],
            'cargo_acreditacion' => ['required', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cargo_manager.required' => 'El CARGO MANAGER es obligatorio.',
            'cargo_manager.unique' => 'Ya existe un cargo con ese CARGO MANAGER y CARGO APO.',
            'cargo_apo.required' => 'El CARGO APO es obligatorio.',
            'cargo_informe.required' => 'El CARGO INFORME es obligatorio.',
            'cargo_acreditacion.required' => 'El CARGO ACREDITACIÓN es obligatorio.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var AcreditacionCargo $acreditacionCargo */
                $acreditacionCargo = $this->route('acreditacionCargo');
                $catalogService = app(AcreditacionCargoCatalogService::class);
                $newApo = (string) $this->input('cargo_apo');

                if (! $catalogService->canRenameCargoApo($acreditacionCargo, $newApo)) {
                    $validator->errors()->add('cargo_apo', $catalogService->renameBlockReason());
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cargo_manager' => trim((string) $this->input('cargo_manager')),
            'cargo_apo' => trim((string) $this->input('cargo_apo')),
            'cargo_informe' => trim((string) $this->input('cargo_informe')),
            'cargo_acreditacion' => trim((string) $this->input('cargo_acreditacion')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
        ]);
    }
}
