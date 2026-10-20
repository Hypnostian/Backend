@extends('layouts.app')

@section('titulo', 'Checkout')
@section('descripcion', 'Paga tu reserva en CloudSpace Solutions con tarjeta de crédito o débito.')

@section('content')
    <div class="encabezado-pagina">
        <span class="etiqueta-superior"><x-icono nombre="candado" :tamano="16" /> Pago seguro con Stripe</span>
        <h1>Checkout</h1>
        <p class="lead">Ingresa el monto y la moneda, continúa al pago y completa los datos de tu tarjeta. La confirmación llega a tu correo.</p>
    </div>

    @if (blank($stripeKey))
        <div class="alerta alerta-aviso" role="alert">
            <x-icono nombre="alerta" />
            <div>
                <strong>Los pagos no están habilitados.</strong>
                Falta la llave publicable de Stripe: configura <code>STRIPE_KEY</code> y <code>STRIPE_SECRET</code> en el archivo <code>.env</code>.
            </div>
        </div>
    @endif

    <div class="grid-checkout">
        <section class="tarjeta" aria-label="Pago de la reserva">
            <ol class="pasos" id="pasos">
                <li class="is-activo">Datos del pago</li>
                <li>Tarjeta</li>
                <li>Confirmación</li>
            </ol>

            <div class="alerta alerta-error" id="alerta-error" role="alert" hidden>
                <x-icono nombre="error" />
                <div id="alerta-error-texto"></div>
            </div>

            {{-- Paso 1 (RF 1.1): monto y moneda. Se envían por fetch a POST /create-payment-intent --}}
            <form id="form-pago" action="{{ route('checkout.payment-intent') }}" method="POST" novalidate>
                @csrf

                <h2 class="tarjeta-titulo">
                    <span class="icono-caja"><x-icono nombre="recibo" /></span> Datos del pago
                </h2>

                <div class="fila-campos">
                    <div class="campo">
                        <label for="monto">Monto a pagar</label>
                        <input class="control" type="number" id="monto" name="monto" min="0.01" step="0.01" inputmode="decimal" required value="{{ $valores['monto'] }}" placeholder="45000">
                        <p class="ayuda">Sin separador de miles; usa punto para los decimales.</p>
                        <p class="error-campo" data-error="monto" hidden></p>
                    </div>

                    <div class="campo">
                        <label for="moneda">Moneda</label>
                        <select class="control" id="moneda" name="moneda" required>
                            @foreach ($monedas as $codigo => $moneda)
                                <option value="{{ $codigo }}" @selected($valores['moneda'] === $codigo)>{{ strtoupper($codigo) }} · {{ $moneda['nombre'] }}</option>
                            @endforeach
                        </select>
                        <p class="error-campo" data-error="moneda" hidden></p>
                    </div>
                </div>

                <div class="campo">
                    <label for="concepto">Concepto <span class="opcional">(opcional)</span></label>
                    <input class="control" type="text" id="concepto" name="concepto" maxlength="150" value="{{ $valores['concepto'] }}" placeholder="Ej.: Sala de conferencias (2 horas)">
                    <p class="error-campo" data-error="concepto" hidden></p>
                </div>

                <div class="fila-campos">
                    <div class="campo">
                        <label for="nombre">Nombre del titular</label>
                        <input class="control" type="text" id="nombre" name="nombre" maxlength="100" autocomplete="name" required>
                        <p class="error-campo" data-error="nombre" hidden></p>
                    </div>

                    <div class="campo">
                        <label for="email">Correo electrónico</label>
                        <input class="control" type="email" id="email" name="email" maxlength="150" autocomplete="email" required>
                        <p class="ayuda">Aquí llega la confirmación del pago.</p>
                        <p class="error-campo" data-error="email" hidden></p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primario btn-bloque" id="btn-continuar">
                    <span class="spinner" hidden></span>
                    <span>Continuar al pago</span>
                    <x-icono nombre="flecha" :tamano="18" />
                </button>
            </form>

            {{-- Paso 2 (RF 1.3): con el clientSecret se inicializa el componente de tarjeta de Stripe Elements --}}
            <div id="paso-tarjeta" hidden>
                <div class="paso-tarjeta-encabezado">
                    <h2 class="tarjeta-titulo sin-margen">
                        <span class="icono-caja"><x-icono nombre="tarjeta" /></span> Datos de la tarjeta
                    </h2>
                    <button type="button" class="btn-enlace" id="btn-modificar">
                        <x-icono nombre="volver" :tamano="16" /> Modificar datos
                    </button>
                </div>

                <form id="form-tarjeta" novalidate>
                    <div class="campo">
                        <p class="ayuda ayuda-arriba">Tarjeta de crédito o débito. Los datos se escriben en un formulario seguro de Stripe.</p>
                        <div id="payment-element" class="stripe-contenedor"></div>
                        <p class="error-campo" id="errores-pago" role="alert"></p>
                    </div>

                    <button type="submit" class="btn btn-primario btn-bloque" id="btn-pagar">
                        <span class="spinner" hidden></span>
                        <x-icono nombre="candado" :tamano="18" />
                        <span>Pagar <span id="texto-total"></span></span>
                    </button>
                </form>
            </div>

            {{-- Paso 3: Stripe aprobó el pago; se redirige a la página de resultado --}}
            <div id="paso-confirmado" class="alerta alerta-exito" role="status" hidden>
                <x-icono nombre="check" />
                <div><strong>¡Pago aprobado!</strong> Estamos preparando tu comprobante...</div>
            </div>
        </section>

        <aside>
            <section class="tarjeta" aria-labelledby="titulo-resumen">
                <h2 class="tarjeta-titulo" id="titulo-resumen">
                    <span class="icono-caja"><x-icono nombre="recibo" /></span> Resumen
                </h2>
                <dl class="resumen">
                    <div><dt>Concepto</dt><dd id="resumen-concepto">Reserva de espacio</dd></div>
                    <div><dt>Moneda</dt><dd id="resumen-moneda">{{ strtoupper($valores['moneda']) }}</dd></div>
                    <div class="total"><dt>Total</dt><dd id="resumen-total">—</dd></div>
                </dl>
                <p class="pago-seguro">
                    <x-icono nombre="escudo" :tamano="18" /> Procesado por Stripe: los datos de la tarjeta nunca pasan por nuestro servidor.
                </p>
            </section>

            @if (str_starts_with((string) $stripeKey, 'pk_test_'))
                <section class="tarjeta alerta-aviso" aria-labelledby="titulo-prueba">
                    <h2 class="tarjeta-titulo titulo-pequeno" id="titulo-prueba">
                        <x-icono nombre="info" :tamano="18" /> Modo de prueba
                    </h2>
                    <ul class="tarjetas-prueba">
                        <li><span>Pago aprobado</span><code>4242 4242 4242 4242</code></li>
                        <li><span>Con 3D Secure</span><code>4000 0025 0000 3155</code></li>
                        <li><span>Fondos insuficientes</span><code>4000 0000 0000 9995</code></li>
                    </ul>
                    <p class="ayuda">Cualquier fecha futura y cualquier CVC de 3 dígitos.</p>
                </section>
            @endif
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (() => {
            const config = {
                llavePublica: @json($stripeKey),
                urlIntento: @json(route('checkout.payment-intent')),
                urlResultado: @json(route('checkout.resultado')),
                csrf: document.querySelector('meta[name="csrf-token"]').content,
            };

            const $ = (id) => document.getElementById(id);
            const formPago = $('form-pago');
            const pasoTarjeta = $('paso-tarjeta');
            const btnContinuar = $('btn-continuar');
            const btnPagar = $('btn-pagar');
            const erroresPago = $('errores-pago');

            const stripe = config.llavePublica && window.Stripe ? Stripe(config.llavePublica, { locale: 'es-419' }) : null;
            let elements = null;
            let componentePago = null;
            let clientSecret = null;

            // Mismo formato que App\Support\Dinero: "$ 45.000 COP", "US$ 15,00 USD"
            const simbolos = @json(\App\Support\Dinero::SIMBOLOS);
            const formatear = (monto, moneda) => {
                const valor = Number(monto);
                if (!monto || !Number.isFinite(valor) || valor <= 0) return '—';
                const codigo = moneda.toUpperCase();
                const decimales = codigo === 'COP' && Number.isInteger(valor) ? 0 : 2;
                const numero = new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: decimales,
                    maximumFractionDigits: decimales,
                }).format(valor);
                return `${simbolos[codigo] ?? ''} ${numero} ${codigo}`.trim();
            };

            const actualizarResumen = () => {
                const datos = new FormData(formPago);
                $('resumen-concepto').textContent = datos.get('concepto').trim() || 'Reserva de espacio';
                $('resumen-moneda').textContent = datos.get('moneda').toUpperCase();
                $('resumen-total').textContent = formatear(datos.get('monto'), datos.get('moneda'));
            };

            const marcarPaso = (numero) => {
                document.querySelectorAll('#pasos li').forEach((paso, i) => {
                    paso.classList.toggle('is-activo', i + 1 === numero);
                    paso.classList.toggle('is-hecho', i + 1 < numero);
                });
            };

            const cargando = (boton, activo) => {
                boton.disabled = activo;
                boton.setAttribute('aria-busy', activo ? 'true' : 'false');
                boton.querySelector('.spinner').hidden = !activo;
            };

            const mostrarError = (mensaje) => {
                $('alerta-error-texto').textContent = mensaje;
                $('alerta-error').hidden = false;
            };

            const limpiarErrores = () => {
                $('alerta-error').hidden = true;
                formPago.querySelectorAll('[data-error]').forEach((p) => { p.hidden = true; p.textContent = ''; });
                formPago.querySelectorAll('.control').forEach((control) => control.removeAttribute('aria-invalid'));
            };

            const mostrarErroresCampos = (errores) => {
                Object.entries(errores).forEach(([campo, mensajes]) => {
                    const p = formPago.querySelector(`[data-error="${campo}"]`);
                    if (p) { p.textContent = mensajes[0]; p.hidden = false; }
                    formPago.elements[campo]?.setAttribute('aria-invalid', 'true');
                });
            };

            // RF 1.3: Stripe Elements se inicializa con el clientSecret y monta el componente de
            // pago, que solo ofrece tarjeta porque el PaymentIntent se creó con allowed_payment_method_types: ['card']
            const montarComponentePago = () => {
                componentePago?.destroy();

                elements = stripe.elements({
                    clientSecret,
                    locale: 'es-419',
                    fonts: [{ cssSrc: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap' }],
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#2856e6',
                            colorText: '#0f1b2d',
                            colorDanger: '#be3429',
                            fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif',
                            borderRadius: '10px',
                        },
                    },
                });

                componentePago = elements.create('payment', { layout: 'tabs' });
                componentePago.mount('#payment-element');
                componentePago.on('ready', () => componentePago.focus());
            };

            formPago.addEventListener('input', actualizarResumen);
            actualizarResumen();

            // Paso 1 -> 2: el servidor valida, crea el PaymentIntent y devuelve el clientSecret
            formPago.addEventListener('submit', async (evento) => {
                evento.preventDefault();
                limpiarErrores();

                if (!stripe) {
                    mostrarError('Los pagos no están disponibles: falta configurar la llave publicable de Stripe (STRIPE_KEY).');
                    return;
                }

                cargando(btnContinuar, true);

                try {
                    const respuesta = await fetch(config.urlIntento, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify(Object.fromEntries(new FormData(formPago))),
                    });
                    const datos = await respuesta.json().catch(() => ({}));

                    if (!respuesta.ok) {
                        if (respuesta.status === 419) {
                            mostrarError('La sesión expiró. Recarga la página e intenta de nuevo.');
                        } else if (datos.errors) {
                            // 400: el detalle de cada error queda debajo de su campo
                            mostrarErroresCampos(datos.errors);
                            mostrarError('Revisa los datos marcados en el formulario.');
                        } else {
                            mostrarError(datos.message || 'No fue posible iniciar el pago. Intenta de nuevo.');
                        }
                        return;
                    }

                    clientSecret = datos.clientSecret;
                    $('texto-total').textContent = datos.monto;
                    formPago.hidden = true;
                    pasoTarjeta.hidden = false;
                    marcarPaso(2);
                    montarComponentePago();
                } catch (error) {
                    mostrarError('No hay conexión con el servidor. Revisa tu red e intenta de nuevo.');
                } finally {
                    cargando(btnContinuar, false);
                }
            });

            // Volver al paso 1: al continuar se crea un PaymentIntent nuevo con los datos corregidos
            $('btn-modificar').addEventListener('click', () => {
                componentePago?.destroy();
                componentePago = null;
                clientSecret = null;
                erroresPago.textContent = '';
                pasoTarjeta.hidden = true;
                formPago.hidden = false;
                marcarPaso(1);
            });

            // Paso 2 -> 3: Stripe.js confirma el pago con la tarjeta (incluye 3D Secure si el banco lo pide)
            $('form-tarjeta').addEventListener('submit', async (evento) => {
                evento.preventDefault();
                erroresPago.textContent = '';
                cargando(btnPagar, true);

                const datos = new FormData(formPago);
                const { error, paymentIntent } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        // Solo se usa si el método de pago exige redirigir; con tarjeta se resuelve aquí mismo
                        return_url: config.urlResultado,
                        payment_method_data: {
                            billing_details: { name: datos.get('nombre'), email: datos.get('email') },
                        },
                    },
                    redirect: 'if_required',
                });

                if (error) {
                    erroresPago.textContent = error.message;
                    cargando(btnPagar, false);
                    return;
                }

                marcarPaso(3);
                pasoTarjeta.hidden = true;
                $('paso-confirmado').hidden = false;
                window.location.href = `${config.urlResultado}?payment_intent=${encodeURIComponent(paymentIntent.id)}`;
            });
        })();
    </script>
@endpush
