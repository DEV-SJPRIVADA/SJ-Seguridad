<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;

class PreviewPlaneacionFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlaneacionEstrategicaAccessService::class)->canEditIndicadores($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'formula' => ['required', 'string', 'max:2000'],
            'values' => ['required', 'array'],
            'values.*' => ['numeric'],
        ];
    }

    /**
     * @return array<string, float>
     */
    public function sampleValues(): array
    {
        $values = [];

        foreach ($this->validated('values') as $key => $value) {
            $values[(string) $key] = (float) $value;
        }

        return $values;
    }
}
