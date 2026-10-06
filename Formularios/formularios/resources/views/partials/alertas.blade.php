@if (session('success'))
    <div class="alert alert-success" role="status" data-alerta>
        <x-icono nombre="check" />
        <div class="alert-text">
            <p>{{ session('success') }}</p>
            @if (! empty($enlaces))
                <p class="alert-links">
                    @foreach ($enlaces as $texto => $url)
                        <a href="{{ $url }}">{{ $texto }}</a>
                    @endforeach
                </p>
            @endif
        </div>
        <button type="button" class="alert-close" aria-label="Cerrar mensaje" data-alerta-cerrar>
            <x-icono nombre="cerrar" :tamano="18" />
        </button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error" role="alert" data-alerta>
        <x-icono nombre="alerta-circulo" />
        <p class="alert-text">{{ session('error') }}</p>
        <button type="button" class="alert-close" aria-label="Cerrar mensaje" data-alerta-cerrar>
            <x-icono nombre="cerrar" :tamano="18" />
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <x-icono nombre="alerta-circulo" />
        <p class="alert-text">
            Revisa el formulario: {{ $errors->count() === 1 ? 'hay 1 campo' : 'hay '.$errors->count().' campos' }} por corregir.
        </p>
    </div>
@endif
