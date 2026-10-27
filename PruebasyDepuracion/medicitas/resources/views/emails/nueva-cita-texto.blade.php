Hola, {{ $cita->medico->name }}:

Tienes una nueva cita de {{ $cita->especialidad->value }} agendada por un paciente.

Paciente: {{ $cita->paciente->name }}
Fecha y hora: {{ ucfirst($cita->fecha_hora->translatedFormat('l j \d\e F \d\e Y, g:i a')) }}
Especialidad: {{ $cita->especialidad->value }}
Motivo: {{ $cita->motivo }}
Estado: {{ $cita->estado->etiqueta() }}

Ver citas: {{ route('citas.index') }}

Correo automático de la clínica MediCitas (cita #{{ $cita->id }}).
