<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StorePlaneacionStrategyRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsible' => ['nullable', 'string', 'max:160'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Escriba la estrategia.',
            'ends_on.after_or_equal' => 'La fecha final debe ser igual o posterior al inicio.',
            'progress.min' => 'El avance debe estar entre 0 y 100.',
            'progress.max' => 'El avance debe estar entre 0 y 100.',
        ];
    }
}
