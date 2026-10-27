<?php

namespace App\Http\Requests;

use App\Enums\Especialidad;
use App\Enums\EstadoCita;
use App\Enums\Rol;
use App\Models\Cita;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Datos para agendar una cita, iguales en el formulario web y en POST /api/citas.
 * El paciente es el usuario autenticado y el estado siempre empieza en "pendiente".
 */
class AgendarCitaRequest extends FormRequest
{
    /**
     * La ruta ya exige sesión y el permiso "create" de CitaPolicy (solo pacientes).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $especialidad = $this->input('especialidad');

        return [
            'especialidad' => ['required', Rule::enum(Especialidad::class)],
            'doctor_id' => [
                'required',
                'integer',
                // Debe ser un médico que atienda la especialidad elegida
                Rule::exists('users', 'id')
                    ->where('rol', Rol::Medico->value)
                    ->where('especialidad', is_string($especialidad) ? $especialidad : null),
            ],
            'fecha_hora' => ['required', 'date', 'after:now'],
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doctor_id.exists' => 'El médico seleccionado no atiende la especialidad elegida.',
            'fecha_hora.after' => 'La cita debe ser en una fecha y hora futuras.',
        ];
    }

    /**
     * El médico no puede tener dos citas activas a la misma hora.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['doctor_id', 'fecha_hora'])) {
                    return;
                }

                $ocupado = Cita::query()
                    ->where('doctor_id', $this->integer('doctor_id'))
                    ->where('fecha_hora', Carbon::parse($this->input('fecha_hora')))
                    ->where('estado', '!=', EstadoCita::Cancelada)
                    ->exists();

                if ($ocupado) {
                    $validator->errors()->add('fecha_hora', 'El médico ya tiene una cita agendada en esa fecha y hora.');
                }
            },
        ];
    }
}
