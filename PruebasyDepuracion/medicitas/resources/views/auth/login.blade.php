@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('content')
    <div class="auth">
        <div class="page-head">
            <div>
                <h1>Iniciar sesión</h1>
                <p>Ingresa como paciente para agendar tus citas.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('login') }}" class="formulario" novalidate>
                    @csrf

                    <div @class(['campo', 'con-error' => $errors->has('email')])>
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus @error('email') aria-invalid="true" aria-describedby="error-email" @enderror>
                        @error('email')<span class="error" id="error-email">{{ $message }}</span>@enderror
                    </div>

                    <div @class(['campo', 'con-error' => $errors->has('password')])>
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="error-password" @enderror>
                        @error('password')<span class="error" id="error-password">{{ $message }}</span>@enderror
                    </div>

                    <label class="check">
                        <input type="checkbox" name="recordarme" value="1" @checked(old('recordarme'))> Mantener la sesión iniciada
                    </label>

                    <button type="submit" class="btn btn-primario">
                        <x-icono nombre="entrar" :tamano="18" /> Ingresar
                    </button>
                </form>

                @if (app()->isLocal())
                    <div class="alerta alerta-info cuentas">
                        <x-icono nombre="info" />
                        <p>
                            Cuentas de prueba creadas por los seeders (contraseña <code>password</code>):
                            paciente <code>jlizcano471@unab.edu.co</code> ·
                            médico <code>andres.rojas@medicitas.test</code>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
