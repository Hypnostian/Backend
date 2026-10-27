@extends('layouts.app')

@section('titulo', 'Agendar cita')

@section('content')
    <div class="page-head">
        <div>
            <h1>Agendar cita médica</h1>
            <p>Elige la especialidad, el médico y el horario de tu consulta.</p>
        </div>
        <a href="{{ route('citas.index') }}" class="btn btn-secundario">
            <x-icono nombre="volver" :tamano="18" /> Volver al listado
        </a>
    </div>

    <div class="layout-form">
        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alerta alerta-error" role="alert">
                        <x-icono nombre="alerta" />
                        <p>Revisa los campos marcados para poder agendar la cita.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('citas.store') }}" class="formulario" novalidate>
                    @csrf

                    <div class="paciente-actual">
                        <x-icono nombre="usuario" />
                        <div>
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>Paciente · {{ auth()->user()->email }}</span>
                        </div>
                    </div>

                    <div class="fila-2">
                        <div @class(['campo', 'con-error' => $errors->has('especialidad')])>
                            <label for="especialidad">Especialidad</label>
                            <select id="especialidad" name="especialidad" required @error('especialidad') aria-invalid="true" aria-describedby="error-especialidad" @enderror>
                                <option value="">Selecciona una especialidad</option>
                                @foreach ($especialidades as $especialidad)
                                    <option value="{{ $especialidad->value }}" @selected(old('especialidad') === $especialidad->value)>{{ $especialidad->value }}</option>
                                @endforeach
                            </select>
                            @error('especialidad')<span class="error" id="error-especialidad">{{ $message }}</span>@enderror
                        </div>

                        <div @class(['campo', 'con-error' => $errors->has('doctor_id')])>
                            <label for="doctor_id">Médico</label>
                            <select id="doctor_id" name="doctor_id" required @error('doctor_id') aria-invalid="true" aria-describedby="error-doctor_id" @enderror>
                                <option value="">Selecciona un médico</option>
                                @foreach ($medicos as $especialidad => $grupo)
                                    <optgroup label="{{ $especialidad }}" data-especialidad="{{ $especialidad }}">
                                        @foreach ($grupo as $medico)
                                            <option value="{{ $medico->id }}" data-especialidad="{{ $especialidad }}" @selected((string) old('doctor_id') === (string) $medico->id)>{{ $medico->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('doctor_id')<span class="error" id="error-doctor_id">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div @class(['campo', 'con-error' => $errors->has('fecha_hora')])>
                        <label for="fecha_hora">Fecha y hora</label>
                        <input type="datetime-local" id="fecha_hora" name="fecha_hora" value="{{ old('fecha_hora') }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required @error('fecha_hora') aria-invalid="true" aria-describedby="error-fecha_hora" @enderror>
                        @error('fecha_hora')<span class="error" id="error-fecha_hora">{{ $message }}</span>@enderror
                    </div>

                    <div @class(['campo', 'con-error' => $errors->has('motivo')])>
                        <label for="motivo">Motivo de la consulta</label>
                        <textarea id="motivo" name="motivo" minlength="10" maxlength="1000" required placeholder="Describe brevemente tus síntomas o el motivo de la cita" @error('motivo') aria-invalid="true" aria-describedby="error-motivo" @enderror>{{ old('motivo') }}</textarea>
                        @error('motivo')<span class="error" id="error-motivo">{{ $message }}</span>@enderror
                    </div>

                    <div class="acciones">
                        <a href="{{ route('citas.index') }}" class="btn btn-secundario">Cancelar</a>
                        <button type="submit" class="btn btn-primario">
                            <x-icono nombre="calendario" :tamano="18" /> Agendar cita
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="card">
            <div class="card-body">
                <h2 style="margin-top: 0; font-size: 17px;">¿Qué pasa al agendar?</h2>
                <ul class="lista-info">
                    <li>La cita queda en estado <span class="estado estado-pendiente">Pendiente</span> hasta que la clínica la confirme.</li>
                    <li>El médico asignado recibe un correo con la fecha, la hora y el motivo de la consulta.</li>
                    <li>Un médico no puede tener dos citas a la misma hora.</li>
                </ul>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        // Muestra solo los médicos de la especialidad elegida (el servidor vuelve a validarlo)
        (() => {
            const especialidad = document.getElementById('especialidad');
            const medico = document.getElementById('doctor_id');

            const filtrar = () => {
                medico.querySelectorAll('optgroup').forEach((grupo) => {
                    const visible = !especialidad.value || grupo.dataset.especialidad === especialidad.value;
                    grupo.hidden = !visible;
                    grupo.disabled = !visible;
                });

                const elegido = medico.selectedOptions[0];
                if (elegido && elegido.dataset.especialidad && especialidad.value && elegido.dataset.especialidad !== especialidad.value) {
                    medico.value = '';
                }
            };

            especialidad.addEventListener('change', filtrar);
            medico.addEventListener('change', () => {
                const elegido = medico.selectedOptions[0];
                if (elegido && elegido.dataset.especialidad && !especialidad.value) {
                    especialidad.value = elegido.dataset.especialidad;
                    filtrar();
                }
            });

            filtrar();
        })();
    </script>
@endpush
