@extends('layouts.app')

@section('content')
    <section class="hero">
        <div>
            <span class="etiqueta-superior"><x-icono nombre="nube" :tamano="16" /> Coworking en Bucaramanga</span>
            <h1>Reserva tu espacio de trabajo y paga en línea</h1>
            <p>Puestos flexibles, oficinas privadas y salas de conferencias. Paga con tarjeta de crédito o débito en un checkout seguro y recibe la confirmación en tu correo.</p>
            <div class="hero-acciones">
                <a href="{{ route('checkout.create') }}" class="btn btn-claro">
                    <x-icono nombre="tarjeta" :tamano="18" /> Ir al checkout
                </a>
                <a href="#espacios" class="btn btn-contorno-claro">Ver espacios</a>
            </div>
        </div>

        <div class="hero-panel" aria-label="Cómo funciona el pago">
            <div class="hero-dato">
                <x-icono nombre="candado" :tamano="22" />
                <div>
                    <strong>Pago con Stripe</strong>
                    <span>Los datos de la tarjeta van directo a Stripe, nunca a nuestro servidor.</span>
                </div>
            </div>
            <div class="hero-dato">
                <x-icono nombre="rayo" :tamano="22" />
                <div>
                    <strong>Confirmación automática</strong>
                    <span>Stripe avisa por webhook y el pago queda registrado al instante.</span>
                </div>
            </div>
            <div class="hero-dato">
                <x-icono nombre="correo" :tamano="22" />
                <div>
                    <strong>Recibo por correo</strong>
                    <span>Enviamos la confirmación con SendGrid apenas se aprueba el pago.</span>
                </div>
            </div>
        </div>
    </section>

    <section id="espacios" aria-labelledby="titulo-espacios">
        <div class="seccion-titulo">
            <h2 id="titulo-espacios">Espacios disponibles</h2>
            <a href="{{ route('checkout.create') }}" class="btn-enlace">Pagar otro monto <x-icono nombre="flecha" :tamano="16" /></a>
        </div>

        <div class="grid-espacios">
            @foreach ($espacios as $espacio)
                <article class="tarjeta espacio">
                    <span class="icono-espacio"><x-icono :nombre="$espacio['icono']" :tamano="24" /></span>
                    <h3>{{ $espacio['nombre'] }}</h3>
                    <p>{{ $espacio['descripcion'] }}</p>
                    <div class="espacio-precio">
                        {{ \App\Support\Dinero::formatear($espacio['precio'], $espacio['moneda']) }}
                        <small>{{ $espacio['unidad'] }}</small>
                    </div>
                    <a href="{{ route('checkout.create', ['concepto' => $espacio['concepto'], 'monto' => $espacio['precio'], 'moneda' => $espacio['moneda']]) }}" class="btn btn-primario btn-bloque">
                        Reservar y pagar <x-icono nombre="flecha" :tamano="18" />
                    </a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid-modulos" aria-label="Módulos de la plataforma">
        <a href="{{ route('checkout.create') }}" class="tarjeta modulo">
            <span class="icono-modulo"><x-icono nombre="tarjeta" :tamano="22" /></span>
            <h3>Checkout con Stripe Elements</h3>
            <p>Formulario de monto y moneda, intento de pago (PaymentIntent) creado en el servidor y confirmación de la tarjeta en el navegador.</p>
        </a>
        <a href="{{ route('pagos.index') }}" class="tarjeta modulo">
            <span class="icono-modulo"><x-icono nombre="base-datos" :tamano="22" /></span>
            <h3>Webhook y registro de pagos</h3>
            <p>Stripe notifica cada pago a <code>/stripe/webhook</code>; se verifica la firma y el pago queda guardado en MySQL.</p>
        </a>
        <a href="{{ route('email.create') }}" class="tarjeta modulo">
            <span class="icono-modulo"><x-icono nombre="enviar" :tamano="22" /></span>
            <h3>Correos con SendGrid</h3>
            <p>Confirmaciones de pago automáticas y un módulo para enviar correos de prueba con la API REST v3.</p>
        </a>
    </section>
@endsection
