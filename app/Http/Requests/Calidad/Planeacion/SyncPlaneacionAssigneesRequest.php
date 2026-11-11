<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPlaneacionAssigneesRequest extends FormRequest
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
        $areaKeys = array_keys(config('access.areas', []));

        return [
            'assignees' => ['nullable', 'array'],
            'assignees.*.user_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists($usersTable, 'id')->where('is_active', true),
            ],
            'assignees.*.can_consult' => ['sometimes', 'boolean'],
            'area_assignees' => ['nullable', 'array'],
            'area_assignees.*.area_key' => ['required', 'string', 'distinct', Rule::in($areaKeys)],
            'area_assignees.*.can_consult' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignees.*.user_id.exists' => 'Seleccione un usuario activo.',
            'assignees.*.user_id.distinct' => 'El mismo usuario no puede asignarse dos veces.',
            'area_assignees.*.area_key.in' => 'Seleccione un área válida.',
            'area_assignees.*.area_key.distinct' => 'La misma área no puede asignarse dos veces.',
        ];
    }

    /**
     * @return list<array{user_id: int, can_consult: bool}>
     */
    public function assigneesPayload(): array
    {
        $rows = [];
        $assignees = $this->validated('assignees') ?? [];

        foreach ($assignees as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'user_id' => (int) $row['user_id'],
                'can_consult' => (bool) ($row['can_consult'] ?? false),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{area_key: string, can_consult: bool}>
     */
    public function areaAssigneesPayload(): array
    {
        $rows = [];
        $assignees = $this->validated('area_assignees') ?? [];

        foreach ($assignees as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'area_key' => (string) $row['area_key'],
                'can_consult' => (bool) ($row['can_consult'] ?? false),
            ];
        }

        return $rows;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('assignees') || $this->input('assignees') === null) {
            $this->merge(['assignees' => []]);
        }

        if (! $this->has('area_assignees') || $this->input('area_assignees') === null) {
            $this->merge(['area_assignees' => []]);
        }

        $assignees = $this->input('assignees', []);
        if (! is_array($assignees)) {
            $this->merge(['assignees' => []]);

            return;
        }

        foreach ($assignees as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $assignees[$index]['can_consult'] = $this->boolean('assignees.'.$index.'.can_consult');
        }

        $this->merge(['assignees' => array_values($assignees)]);

        $areaAssignees = $this->input('area_assignees', []);
        if (! is_array($areaAssignees)) {
            $this->merge(['area_assignees' => []]);

            return;
        }

        foreach ($areaAssignees as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $areaAssignees[$index]['can_consult'] = $this->boolean('area_assignees.'.$index.'.can_consult');
        }

        $this->merge(['area_assignees' => array_values($areaAssignees)]);
    }
}
