<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionExportApoRowResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewAcreditacionExportApoRequest extends FormRequest
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
        $maxIds = (int) config('acreditaciones.limits.bulk_max_ids', 500);

        return [
            'ids' => ['sometimes', 'nullable', 'array', 'max:'.$maxIds],
            'ids.*' => ['integer', 'distinct', 'min:1'],
            'vigencia_policy' => [
                'required',
                'string',
                Rule::in([
                    AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                    AcreditacionExportApoRowResolver::POLICY_VIGENTE_ACTUALIZAR,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.max' => 'Demasiadas filas seleccionadas.',
            'vigencia_policy.required' => 'La política de vigencia es obligatoria.',
            'vigencia_policy.in' => 'Política de vigencia no válida.',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int|string>|null $ids */
        $ids = $this->validated('ids');

        if (! is_array($ids) || $ids === []) {
            return [];
        }

        return array_values(array_map('intval', $ids));
    }

    public function vigenciaPolicy(): string
    {
        return (string) $this->validated('vigencia_policy');
    }

    public function previewAll(): bool
    {
        return $this->ids() === [];
    }
}
