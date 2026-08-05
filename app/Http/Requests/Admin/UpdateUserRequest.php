<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage.users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'area_key' => ['nullable', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'sede_id' => ['nullable', 'integer', Rule::exists('supply_sites', 'id')],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El correo electronico no tiene un formato valido.',
            'email.unique' => 'Ese correo electronico ya esta registrado por otro usuario.',
            'name.required' => 'El nombre completo es obligatorio.',
            'role.required' => 'Debes seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no es valido.',
            'sede_id.exists' => 'La sede seleccionada ya no existe. Elige otra sede o deja el campo vacio.',
            'area_key.in' => 'El area base seleccionada no es valida.',
            'password' => 'La nueva contrasena no cumple los requisitos minimos de seguridad.',
            'permissions.*.exists' => 'Uno de los permisos enviados no es valido. Recarga la pagina e intenta de nuevo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'email' => 'correo electronico',
            'password' => 'nueva contrasena',
            'role' => 'rol',
            'sede_id' => 'sede fisica',
            'area_key' => 'area base',
        ];
    }

    protected function prepareForValidation(): void
    {
        $password = $this->input('password');

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => Str::lower(trim((string) $this->input('email', ''))),
            'area_key' => blank($this->input('area_key')) ? null : $this->string('area_key')->toString(),
            'sede_id' => blank($this->input('sede_id')) ? null : $this->integer('sede_id'),
            'is_active' => $this->boolean('is_active'),
            'must_change_password' => $this->boolean('must_change_password'),
            'password' => is_string($password) && trim($password) === '' ? null : $password,
            'permissions' => collect($this->input('permissions', []))
                ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
                ->values()
                ->all(),
        ]);
    }
}
