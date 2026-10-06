/* El Fogón · Interacciones de la interfaz */
(function () {
    'use strict';

    var LIMITE_IMAGEN = 2 * 1024 * 1024; // 2 MB, igual que la regla max:2048 del servidor

    /* ------------------------------------------------------------------
     * Confirmación antes de enviar formularios con data-confirm (eliminar).
     * Usa la ventana <dialog> del layout; si el navegador no la soporta,
     * se recurre a window.confirm().
     * ------------------------------------------------------------------ */
    var dialogo = document.getElementById('dialogo-confirmacion');
    var resolverPendiente = null;

    function responder(aceptado) {
        if (resolverPendiente) {
            var resolver = resolverPendiente;
            resolverPendiente = null;
            resolver(aceptado);
        }
    }

    function confirmar(mensaje) {
        if (!dialogo || typeof dialogo.showModal !== 'function') {
            return Promise.resolve(window.confirm(mensaje));
        }

        dialogo.querySelector('#dialogo-mensaje').textContent = mensaje;
        dialogo.returnValue = '';
        dialogo.showModal();

        return new Promise(function (resolver) {
            resolverPendiente = resolver;
        });
    }

    if (dialogo) {
        // Se responde al pulsar un botón (evento síncrono); "close" queda como respaldo.
        dialogo.querySelector('form').addEventListener('submit', function (evento) {
            if (evento.submitter) {
                responder(evento.submitter.value === 'confirmar');
            }
        });

        dialogo.addEventListener('close', function () {
            responder(dialogo.returnValue === 'confirmar');
        });

        // Tecla Esc = cancelar.
        dialogo.addEventListener('cancel', function () {
            responder(false);
        });

        // Clic fuera de la ventana = cancelar.
        dialogo.addEventListener('click', function (evento) {
            if (evento.target === dialogo) {
                dialogo.close('cancelar');
                responder(false);
            }
        });
    }

    document.addEventListener('submit', function (evento) {
        var formulario = evento.target;

        if (!(formulario instanceof HTMLFormElement) || !formulario.dataset.confirm) {
            return;
        }

        evento.preventDefault();

        confirmar(formulario.dataset.confirm).then(function (aceptado) {
            if (aceptado) {
                formulario.querySelectorAll('[type="submit"]').forEach(function (boton) {
                    boton.disabled = true;
                });
                // submit() no vuelve a disparar el evento, así que no se pide confirmación dos veces.
                formulario.submit();
            }
        });
    });

    /* ------------------------------------------------------------------
     * Evita el doble envío de formularios marcados con data-un-envio.
     * ------------------------------------------------------------------ */
    document.addEventListener('submit', function (evento) {
        var formulario = evento.target;

        if (evento.defaultPrevented || !(formulario instanceof HTMLFormElement) || !formulario.hasAttribute('data-un-envio')) {
            return;
        }

        var boton = formulario.querySelector('[type="submit"]');

        if (boton) {
            boton.disabled = true;
            boton.dataset.textoOriginal = boton.innerHTML;
            boton.textContent = 'Enviando…';
        }
    });

    // Al volver con el botón "atrás" el navegador puede restaurar la página con el botón deshabilitado.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-texto-original]').forEach(function (boton) {
            boton.disabled = false;
            boton.innerHTML = boton.dataset.textoOriginal;
            delete boton.dataset.textoOriginal;
        });
    });

    /* ------------------------------------------------------------------
     * Vista previa de la imagen seleccionada (o arrastrada) en cada dropzone.
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-dropzone]').forEach(function (zona) {
        var input = zona.querySelector('input[type="file"]');
        var vistaPrevia = zona.querySelector('[data-dropzone-preview]');
        var campo = zona.closest('.field');
        var info = campo ? campo.querySelector('[data-dropzone-info]') : null;
        var infoOriginal = info ? info.innerHTML : '';
        var imagenActual = zona.closest('form') ? zona.closest('form').querySelector('[data-imagen-actual]') : null;

        if (!input || !vistaPrevia) {
            return;
        }

        input.addEventListener('change', function () {
            var archivo = input.files && input.files[0];

            if (vistaPrevia.src && vistaPrevia.src.indexOf('blob:') === 0) {
                URL.revokeObjectURL(vistaPrevia.src);
            }

            if (!archivo) {
                vistaPrevia.hidden = true;
                vistaPrevia.removeAttribute('src');
                zona.classList.remove('has-file', 'is-too-big');
                if (info) { info.innerHTML = infoOriginal; }
                if (imagenActual) { imagenActual.classList.remove('is-replaced'); }
                return;
            }

            var muyGrande = archivo.size > LIMITE_IMAGEN;
            var megas = (archivo.size / 1024 / 1024).toFixed(2).replace('.', ',');

            if (archivo.type.indexOf('image/') === 0) {
                vistaPrevia.src = URL.createObjectURL(archivo);
                vistaPrevia.hidden = false;
            } else {
                vistaPrevia.hidden = true;
            }

            zona.classList.add('has-file');
            zona.classList.toggle('is-too-big', muyGrande);

            if (info) {
                info.textContent = archivo.name + ' · ' + megas + ' MB' + (muyGrande ? ' — supera el máximo de 2 MB' : '');
            }

            if (imagenActual) {
                imagenActual.classList.add('is-replaced');
            }
        });

        ['dragenter', 'dragover'].forEach(function (tipo) {
            zona.addEventListener(tipo, function () {
                zona.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach(function (tipo) {
            zona.addEventListener(tipo, function () {
                zona.classList.remove('is-dragover');
            });
        });
    });

    /* ------------------------------------------------------------------
     * Cerrar alertas.
     * ------------------------------------------------------------------ */
    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-alerta-cerrar]');

        if (boton) {
            var alerta = boton.closest('[data-alerta]');
            if (alerta) { alerta.remove(); }
        }
    });
})();
