<?php

namespace App\Http\Requests\GestionHumana\ClienteInterno;

use App\Services\Access\ClienteInternoAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteInternoSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ClienteInternoAccessService::class)->canEditSolicitudes($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha_solicitud' => ['required', 'date'],
            'nombre_apellidos' => ['required', 'string', 'max:255'],
            'cedula' => ['required', 'string', 'max:50'],
            'correo_electronico' => ['nullable', 'email', 'max:150'],
            'tipo_solicitud_id' => [
                'required',
                'integer',
                Rule::exists('cliente_interno_tipos_solicitud', 'id')->where(
                    fn ($q) => $q->where('is_active', true)
                ),
            ],
            'fecha_respuesta' => ['nullable', 'date', 'after_or_equal:fecha_solicitud'],
            'estado_id' => [
                'nullable',
                'integer',
                Rule::exists('cliente_interno_estados', 'id')->where(
                    fn ($q) => $q->where('is_active', true)
                ),
            ],
            'novedad' => ['nullable', 'string', 'max:5000'],
            'dias_respuesta' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'dias_respuesta_touched' => ['nullable', 'boolean'],
            'recalcular_dias' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_solicitud.required' => 'La fecha de solicitud es obligatoria.',
            'nombre_apellidos.required' => 'El nombre y apellidos son obligatorios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'correo_electronico.email' => 'El correo electrónico no es válido.',
            'tipo_solicitud_id.required' => 'El tipo de solicitud es obligatorio.',
            'tipo_solicitud_id.exists' => 'El tipo de solicitud no existe o no está activo.',
            'fecha_respuesta.after_or_equal' => 'La fecha de respuesta no puede ser anterior a la de solicitud.',
            'estado_id.exists' => 'El estado no existe o no está activo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $correo = trim((string) $this->input('correo_electronico', ''));
        $novedad = trim((string) $this->input('novedad', ''));
        $diasRaw = $this->input('dias_respuesta');
        $dias = is_string($diasRaw) ? trim($diasRaw) : $diasRaw;
        $estadoRaw = $this->input('estado_id');
        $estado = is_string($estadoRaw) ? trim($estadoRaw) : $estadoRaw;

        $this->merge([
            'nombre_apellidos' => trim((string) $this->input('nombre_apellidos')),
            'cedula' => trim((string) $this->input('cedula')),
            'correo_electronico' => $correo === '' ? null : $correo,
            'novedad' => $novedad === '' ? null : $novedad,
            'fecha_respuesta' => trim((string) $this->input('fecha_respuesta', '')) ?: null,
            'estado_id' => $estado === '' || $estado === null ? null : $estado,
            'dias_respuesta' => $dias === '' || $dias === null ? null : $dias,
            'dias_respuesta_touched' => $this->boolean('dias_respuesta_touched'),
            'recalcular_dias' => $this->boolean('recalcular_dias'),
        ]);
    }
}
