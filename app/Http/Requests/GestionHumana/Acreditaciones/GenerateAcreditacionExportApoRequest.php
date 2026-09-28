<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionExportApoRowResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAcreditacionExportApoRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:'.$maxIds],
            'ids.*' => ['integer', 'distinct', 'min:1'],
            'vigencia_policy' => [
                'required',
                'string',
                Rule::in([
                    AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                    AcreditacionExportApoRowResolver::POLICY_VIGENTE_ACTUALIZAR,
                ]),
            ],
            'include_novedades' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Seleccione al menos un candidato.',
            'ids.min' => 'Seleccione al menos un candidato.',
            'ids.max' => 'Demasiadas filas seleccionadas.',
            'vigencia_policy.required' => 'La política de vigencia es obligatoria.',
            'vigencia_policy.in' => 'Política de vigencia no válida.',
            'include_novedades.required' => 'Indique si desea incluir filas con novedad.',
            'include_novedades.boolean' => 'El valor de incluir novedades no es válido.',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('ids');

        return array_values(array_map('intval', $ids));
    }

    public function vigenciaPolicy(): string
    {
        return (string) $this->validated('vigencia_policy');
    }

    public function includeNovedades(): bool
    {
        return (bool) $this->validated('include_novedades');
    }
}
