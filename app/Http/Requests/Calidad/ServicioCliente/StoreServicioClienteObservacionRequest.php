<?php

namespace App\Http\Requests\Calidad\ServicioCliente;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\ServicioClienteAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicioClienteObservacionRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ServicioClienteAccessService::class)->canEditFoGi22($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'texto' => ['required', 'string', 'max:5000'],
        ];
    }
}
