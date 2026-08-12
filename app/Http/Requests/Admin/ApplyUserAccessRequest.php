<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\Admin\UserAccessProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplyUserAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage.users') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'include_area' => ['nullable', 'boolean'],
            'include_sede' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_user_id.required' => 'Seleccione el usuario origen.',
            'source_user_id.exists' => 'El usuario origen no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_area' => $this->boolean('include_area'),
            'include_sede' => $this->boolean('include_sede'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var User|null $target */
            $target = $this->route('user');

            /** @var User|null $source */
            $source = User::query()->with('roles')->find($this->integer('source_user_id'));

            if ($target === null || $source === null || $this->user() === null) {
                return;
            }

            try {
                app(UserAccessProfileService::class)->assertCanCopy($this->user(), $source, $target);
            } catch (AuthorizationException $exception) {
                $validator->errors()->add('source_user_id', $exception->getMessage());
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }
}
