@extends('layouts.app')

@section('titulo', $estado ? 'Citas '.mb_strtolower($estado->plural()) : 'Citas médicas')

@section('content')
    <div class="page-head">
        <div>
            <h1>Citas médicas</h1>
            <p>Citas agendadas en la clínica, ordenadas por fecha.</p>
        </div>
        @if (! auth()->user()?->esMedico())
            <a href="{{ route('citas.create') }}" class="btn btn-primario">
                <x-icono nombre="mas" :tamano="18" /> Agendar cita
            </a>
        @endif
    </div>

    <nav class="tabs" aria-label="Filtrar por estado">
        <a href="{{ route('citas.index') }}" @class(['tab', 'is-active' => ! $estado]) @if (! $estado) aria-current="page" @endif>
            Todas <span class="tab-total">{{ array_sum($totales) }}</span>
        </a>
        @foreach (\App\Enums\EstadoCita::cases() as $opcion)
            <a href="{{ route('citas.index', ['estado' => $opcion->value]) }}" @class(['tab', 'is-active' => $estado === $opcion]) @if ($estado === $opcion) aria-current="page" @endif>
                {{ $opcion->plural() }} <span class="tab-total">{{ $totales[$opcion->value] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="card">
        @if ($citas->isEmpty())
            <div class="vacio">
                <strong>No hay citas {{ $estado ? mb_strtolower($estado->plural()) : 'agendadas' }}</strong>
                Cuando un paciente agende una cita aparecerá aquí.
            </div>
        @else
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Paciente</th>
                        <th scope="col">Médico</th>
                        <th scope="col">Especialidad</th>
                        <th scope="col">Motivo</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($citas as $cita)
                        <tr>
                            <td class="fecha" data-label="Fecha y hora">
                                <strong>{{ ucfirst($cita->fecha_hora->translatedFormat('D j M Y')) }}</strong>
                                <span>{{ $cita->fecha_hora->translatedFormat('g:i a') }}</span>
                            </td>
                            <td data-label="Paciente">
                                {{ $cita->paciente->name }}
                                @if (auth()->id() === $cita->patient_id)<span class="etiqueta-tu">Tú</span>@endif
                            </td>
                            <td data-label="Médico">
                                {{ $cita->medico->name }}
                                @if (auth()->id() === $cita->doctor_id)<span class="etiqueta-tu">Tú</span>@endif
                            </td>
                            <td data-label="Especialidad">{{ $cita->especialidad->value }}</td>
                            <td class="motivo secundario" data-label="Motivo">{{ $cita->motivo }}</td>
                            <td data-label="Estado">
                                <span class="estado estado-{{ $cita->estado->value }}">{{ $cita->estado->etiqueta() }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
