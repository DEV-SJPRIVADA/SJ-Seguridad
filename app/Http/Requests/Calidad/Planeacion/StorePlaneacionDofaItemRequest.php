<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningDofaItem;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de una línea de cuadrante DOFA desde la matriz (botón + de cada sección).
 */
class StorePlaneacionDofaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlaneacionEstrategicaAccessService::class)->canEditEstrategia($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quadrant' => ['required', Rule::in(array_keys(QualityPlanningDofaItem::QUADRANTS))],
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quadrant.required' => 'Seleccione el cuadrante del DOFA.',
            'quadrant.in' => 'El cuadrante del DOFA no es válido.',
            'description.required' => 'Escriba la línea del cuadrante.',
        ];
    }
}
