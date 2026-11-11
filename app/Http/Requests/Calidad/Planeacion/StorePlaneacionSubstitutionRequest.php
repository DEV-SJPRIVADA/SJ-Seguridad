<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de suplencia temporal de un indicador (titular + suplente + rango).
 */
class StorePlaneacionSubstitutionRequest extends FormRequest
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
        $usersTable = (new User)->getTable();

        return [
            'holder_user_id' => [
                'required',
                'integer',
                Rule::exists($usersTable, 'id')->where('is_active', true),
            ],
            'substitute_user_id' => [
                'required',
                'integer',
                'different:holder_user_id',
                Rule::exists($usersTable, 'id')->where('is_active', true),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'holder_user_id.required' => 'Seleccione el titular.',
            'substitute_user_id.required' => 'Seleccione el suplente.',
            'substitute_user_id.different' => 'El suplente debe ser una persona distinta al titular.',
            'ends_on.after_or_equal' => 'La fecha final debe ser igual o posterior al inicio.',
        ];
    }

    /**
     * El titular debe estar asignado al indicador (directo, por área o con suplencia previa).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $indicator = $this->route('indicator');
                $holderId = (int) $this->input('holder_user_id');

                if ($indicator === null || $holderId < 1) {
                    return;
                }

                $holder = User::query()->find($holderId);
                if (! $holder instanceof User) {
                    return;
                }

                $isAssigned = $indicator->assignees()->where('user_id', $holder->id)->exists()
                    || (
                        $holder->hasAssignedArea()
                        && $indicator->areaAssignees()->where('area_key', $holder->area_key)->exists()
                    );

                if (! $isAssigned) {
                    $validator->errors()->add(
                        'holder_user_id',
                        'El titular debe estar asignado al indicador (usuario o su área).',
                    );
                }
            },
        ];
    }

    /**
     * @return array{holder_user_id: int, substitute_user_id: int, starts_on: string, ends_on: string}
     */
    public function substitutionPayload(): array
    {
        return [
            'holder_user_id' => (int) $this->validated('holder_user_id'),
            'substitute_user_id' => (int) $this->validated('substitute_user_id'),
            'starts_on' => (string) $this->validated('starts_on'),
            'ends_on' => (string) $this->validated('ends_on'),
        ];
    }
}
