document.addEventListener('DOMContentLoaded', () => {
    const pagina = document.querySelector('.carrito-page[data-carrito-url]');
    if (!pagina || !window.fetch) return;

    const mensaje = pagina.querySelector('.carrito-mensaje');
    const reintentar = pagina.querySelector('.carrito-reintentar');
    const continuar = pagina.querySelector('#btnContinuarCompra');
    let pendiente = false;
    let sincronizado = true;
    const avisar = texto => { mensaje.textContent = texto; };

    function bloquear() {
        pagina.setAttribute('aria-busy', 'true');
        pagina.querySelectorAll('[data-carrito-actualizar] button, .carrito-eliminar').forEach(b => {
            b.disabled = true;
        });
        if (continuar) continuar.disabled = true;
        reintentar.disabled = true;
    }

    function representar(estado) {
        if (!estado || !Array.isArray(estado.productos)
            || !Number.isInteger(estado.cantidad_carrito)
            || typeof estado.total_formateado !== 'string'
            || typeof estado.puede_continuar !== 'boolean') {
            throw new Error('La respuesta del carrito no es válida.');
        }
        const filas = [...pagina.querySelectorAll('.carrito-item[data-producto]')];
        const ids = estado.productos.map(p => String(p.id_producto));
        if (filas.length !== ids.length || filas.some(f => !ids.includes(f.dataset.producto))) {
            throw new Error('Tu carrito cambió en otra página. Recárgalo para ver los productos actuales.');
        }
        // Los importes y las reglas de disponibilidad proceden exclusivamente de PHP.
        estado.productos.forEach(producto => {
            const fila = filas.find(f => f.dataset.producto === String(producto.id_producto));
            fila.querySelector('.carrito-cantidad-control strong').textContent = String(producto.cantidad);
            fila.querySelector('.carrito-item-precio strong').textContent = producto.precio_formateado;
            fila.querySelector('.carrito-item-subtotal strong').textContent = producto.subtotal_formateado;
            const stock = fila.querySelector('.carrito-stock');
            stock.textContent = producto.disponibilidad_texto;
            stock.classList.toggle('carrito-stock-disponible', producto.disponible);
            stock.classList.toggle('carrito-stock-agotado', !producto.disponible);
            fila.querySelector('.carrito-item-aviso').textContent = producto.mensaje;
            fila.querySelector('[name="accion"][value="sumar"]').form.querySelector('button').disabled = !producto.puede_sumar;
            fila.querySelector('[name="accion"][value="restar"]').form.querySelector('button').disabled = !producto.puede_restar;
            fila.querySelector('.carrito-eliminar').disabled = false;
        });
        const total = pagina.querySelector('.carrito-resumen-total strong');
        const unidades = pagina.querySelector('.carrito-resumen-fila:not(.carrito-resumen-total) strong');
        if (total) total.textContent = estado.total_formateado;
        if (unidades) unidades.textContent = estado.unidades_texto;
        const bloqueo = pagina.querySelector('.carrito-bloqueo');
        if (bloqueo) bloqueo.textContent = estado.bloqueo_texto;
        document.getElementById('contadorCarrito').textContent = String(estado.cantidad_carrito);
        document.getElementById('textoContadorCarrito').textContent = estado.contador_texto;
        if (continuar) continuar.disabled = !estado.puede_continuar;
        sincronizado = true;
        reintentar.hidden = true;
    }

    async function solicitar(datos) {
        const controller = new AbortController();
        const limite = setTimeout(() => controller.abort(), 15000);
        try {
            const respuesta = await fetch(pagina.dataset.carritoUrl, {
                method: 'POST', body: datos, credentials: 'same-origin',
                headers: { Accept: 'application/json' }, cache: 'no-store',
                signal: controller.signal
            });
            const resultado = await respuesta.json();
            if (!resultado.estado) throw new Error(resultado.message || 'No fue posible consultar tu carrito.');
            return resultado;
        } finally {
            clearTimeout(limite);
        }
    }

    function consulta() {
        const datos = new FormData();
        datos.set('csrf', pagina.dataset.carritoCsrf);
        datos.set('accion', 'consultar');
        return datos;
    }

    async function actualizar(datos, soloConsulta = false) {
        if (pendiente) return;
        pendiente = true;
        sincronizado = false;
        bloquear();
        avisar('Actualizando…');
        try {
            const resultado = await solicitar(datos);
            representar(resultado.estado);
            avisar(resultado.message || (soloConsulta ? 'Carrito actualizado.' : ''));
        } catch (error) {
            // Nunca repetir sumar/restar: la modificación podría haberse aplicado.
            try {
                if (soloConsulta) throw error;
                const recuperado = await solicitar(consulta());
                representar(recuperado.estado);
                avisar('No se recibió la confirmación del cambio. Se consultó el estado actual del carrito.');
            } catch (recuperacion) {
                bloquear();
                avisar('No fue posible sincronizar el carrito. Consulta su estado o recarga la página antes de continuar.');
                reintentar.hidden = false;
            }
        } finally {
            pendiente = false;
            pagina.setAttribute('aria-busy', 'false');
            reintentar.disabled = false;
        }
    }

    pagina.addEventListener('submit', evento => {
        if (evento.target.matches('[data-carrito-actualizar]')) {
            evento.preventDefault();
            if (!pendiente && sincronizado) actualizar(new FormData(evento.target));
        } else if (evento.target.matches('.carrito-eliminar-form') && (pendiente || !sincronizado)) {
            evento.preventDefault();
        }
    });
    // Captura impide que el listener global navegue con un estado incierto.
    continuar?.addEventListener('click', evento => {
        if (pendiente || !sincronizado || continuar.disabled) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
        }
    }, true);
    reintentar.addEventListener('click', () => actualizar(consulta(), true));
    window.addEventListener('pageshow', evento => {
        if (evento.persisted) actualizar(consulta(), true);
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !pendiente) actualizar(consulta(), true);
    });
});
