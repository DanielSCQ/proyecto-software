/* =========================================
   MODAL DETALLE DEL PRODUCTO
   ========================================= */
console.log("GLOBAL.JS CARGADO");
document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("modalProducto");

    if (!modal) {
        return;
    }


    const overlay =
        modal.querySelector(".modal-producto-overlay");

    const contenedor =
        modal.querySelector(".modal-producto-contenedor");

    const btnCerrar =
        document.getElementById("cerrarModalProducto");


    const imagen =
        document.getElementById("modalProductoImagen");

    const sinImagen =
        document.getElementById("modalProductoSinImagen");


    const categoria =
        document.getElementById("modalProductoCategoria");

    const nombre =
        document.getElementById("modalProductoNombre");

    const codigo =
        document.getElementById("modalProductoCodigo");

    const descripcion =
        document.getElementById("modalProductoDescripcion");

    const marca =
        document.getElementById("modalProductoMarca");

    const peso =
        document.getElementById("modalProductoPeso");

    const stock =
        document.getElementById("modalProductoStock");

    const precio =
        document.getElementById("modalProductoPrecio");


    const bloqueCaracteristicas =
        document.getElementById(
            "modalProductoCaracteristicas"
        );

    const listaCaracteristicas =
        document.getElementById(
            "listaCaracteristicas"
        );


    const mensaje =
        document.getElementById(
            "modalProductoMensaje"
        );

    const btnFavorito =
        document.getElementById(
            "modalBtnFavorito"
        );

    const btnCarrito =
        document.getElementById(
            "modalBtnCarrito"
        );    

    let productoActualId = null;    

    /* =========================================
       ABRIR MODAL
       ========================================= */

    function abrirModal() {

        modal.classList.add("modal-activo");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-abierto"
        );

        btnCerrar.focus();
    }


    /* =========================================
       CERRAR MODAL
       ========================================= */

    function cerrarModal() {

        modal.classList.remove(
            "modal-activo"
        );

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-abierto"
        );
    }


    /* =========================================
       LIMPIAR MODAL
       ========================================= */

    function limpiarModal() {

        imagen.removeAttribute("src");

        imagen.alt = "";

        imagen.hidden = false;

        sinImagen.hidden = true;


        categoria.textContent = "";

        nombre.textContent = "Cargando...";

        codigo.textContent = "";

        descripcion.textContent = "";

        marca.textContent = "—";

        peso.textContent = "—";

        stock.textContent = "—";

        precio.textContent = "$0";


        listaCaracteristicas.replaceChildren();

        bloqueCaracteristicas.hidden = true;

        mensaje.textContent = "";

        productoActualId = null;

        if (btnCarrito) {

            btnCarrito.disabled = true;

            btnCarrito.textContent =
                "Agregar al carrito";
        }

        if (btnFavorito) {

            btnFavorito.textContent = "♡";

            btnFavorito.classList.remove(
                "favorito-activo"
            );

            btnFavorito.setAttribute(
                "aria-pressed",
                "false"
            );

            btnFavorito.setAttribute(
                "aria-label",
                "Agregar a favoritos"
            );
        }
    }


    /* =========================================
       FORMATEAR PRECIO
       ========================================= */

    function formatearPrecio(valor) {

        return "$" + Number(valor).toLocaleString(
            "es-CO",
            {
                maximumFractionDigits: 0
            }
        );
    }


    /* =========================================
       CARGAR PRODUCTO
       ========================================= */

    async function cargarProducto(idProducto) {

        limpiarModal();

        productoActualId = idProducto;

        abrirModal();


        mensaje.textContent =
            "Cargando información del producto...";


        try {

            const respuesta = await fetch(
                `detalle.php?id=${encodeURIComponent(idProducto)}`,
                {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


            if (!respuesta.ok) {

                throw new Error(
                    "No fue posible consultar el producto."
                );
            }


            const datos =
                await respuesta.json();


            if (
                !datos.success ||
                !datos.producto
            ) {

                throw new Error(
                    datos.message ||
                    "No fue posible cargar el producto."
                );
            }


            mostrarProducto(
                datos.producto
            );

            consultarFavorito(idProducto);


        } catch (error) {

            nombre.textContent =
                "No fue posible cargar el producto.";

            mensaje.textContent =
                error.message ||
                "Ocurrió un error inesperado.";

        }

    }


    /* =========================================
       MOSTRAR PRODUCTO
       ========================================= */

    function mostrarProducto(producto) {

        nombre.textContent =
            producto.nombre || "Producto";


        codigo.textContent =
            producto.codigo_producto
                ? `Código: ${producto.codigo_producto}`
                : "";


        categoria.textContent =
            producto.categoria || "";


        descripcion.textContent =
            producto.descripcion ||
            "Este producto no tiene descripción disponible.";


        marca.textContent =
            producto.marca || "No especificada";


        if (producto.peso !== null) {

            peso.textContent =
                `${producto.peso} kg`;

        } else {

            peso.textContent = "No especificado";
        }


        stock.textContent =
            `${producto.stock} disponible${producto.stock === 1 ? "" : "s"}`;


        if (producto.stock > 0) {

            stock.classList.add(
                "stock-disponible"
            );

        } else {

            stock.classList.remove(
                "stock-disponible"
            );
        }


        precio.textContent =
            formatearPrecio(
                producto.precio
            );


        /* =====================================
           IMAGEN
           ===================================== */

        if (producto.imagen) {

            imagen.src =
                `../../${producto.imagen}`;

            imagen.alt =
                producto.nombre || "Producto";

            imagen.hidden = false;

            sinImagen.hidden = true;

        } else {

            imagen.removeAttribute("src");

            imagen.alt = "";

            imagen.hidden = true;

            sinImagen.hidden = false;
        }


        /* =====================================
           CARACTERÍSTICAS
           ===================================== */

        listaCaracteristicas.replaceChildren();


        if (
            Array.isArray(producto.caracteristicas) &&
            producto.caracteristicas.length > 0
        ) {

            producto.caracteristicas.forEach(
                (caracteristica) => {

                    const elemento =
                        document.createElement("div");

                    elemento.className =
                        "caracteristica-item";


                    const etiqueta =
                        document.createElement("span");

                    etiqueta.textContent =
                        caracteristica.nombre;


                    const valor =
                        document.createElement("strong");

                    valor.textContent =
                        caracteristica.valor;


                    elemento.appendChild(
                        etiqueta
                    );

                    elemento.appendChild(
                        valor
                    );


                    listaCaracteristicas.appendChild(
                        elemento
                    );

                }
            );


            bloqueCaracteristicas.hidden = false;

        } else {

            bloqueCaracteristicas.hidden = true;
        }

        if (
            btnCarrito &&
            Number(producto.stock) > 0
        ) {

            btnCarrito.disabled = false;
        }

        mensaje.textContent = "";

    }

    /* =========================================
    FAVORITOS
    ========================================= */

    function actualizarCorazon(esFavorito) {

        if (!btnFavorito) {
            return;
        }

        btnFavorito.textContent =
            esFavorito
                ? "♥"
                : "♡";

        btnFavorito.classList.toggle(
            "favorito-activo",
            esFavorito
        );

        btnFavorito.setAttribute(
            "aria-pressed",
            esFavorito
                ? "true"
                : "false"
        );

        btnFavorito.setAttribute(
            "aria-label",
            esFavorito
                ? "Quitar de favoritos"
                : "Agregar a favoritos"
        );
    }


    /* =========================================
    CONSULTAR SI YA ES FAVORITO
    ========================================= */

    async function consultarFavorito(idProducto) {

        if (!btnFavorito) {
            return;
        }


        const clienteLogueado =
            btnFavorito.dataset.clienteLogueado === "1";


        // Para invitados simplemente mostramos
        // el corazón vacío.
        if (!clienteLogueado) {

            actualizarCorazon(false);

            return;
        }


        try {

            const respuesta = await fetch(
                `favorito.php?producto=${encodeURIComponent(idProducto)}`,
                {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


            const datos =
                await respuesta.json();


            if (
                respuesta.ok &&
                datos.success
            ) {

                actualizarCorazon(
                    Boolean(datos.favorito)
                );
            }

        } catch (error) {

            console.error(
                "No fue posible consultar el favorito.",
                error
            );
        }
    }


    /* =========================================
    AGREGAR / QUITAR FAVORITO
    ========================================= */

    async function cambiarFavorito() {

        if (
            !btnFavorito ||
            !Number.isInteger(productoActualId) ||
            productoActualId < 1
        ) {
            return;
        }


        const clienteLogueado =
            btnFavorito.dataset.clienteLogueado === "1";


        // =====================================
        // INVITADO
        // =====================================

        if (!clienteLogueado) {

            mensaje.textContent =
                "Inicia sesión o crea una cuenta para guardar productos en favoritos.";


            // Cerrar detalle para que el modal
            // de cuenta quede visible.
            cerrarModal();


            const botonCuenta =
                document.getElementById(
                    "abrirModalCuenta"
                );


            if (botonCuenta) {

                botonCuenta.click();
            }

            return;
        }


        const csrf =
            btnFavorito.dataset.csrf || "";


        const datosFormulario =
            new FormData();

        datosFormulario.append(
            "producto",
            String(productoActualId)
        );

        datosFormulario.append(
            "csrf",
            csrf
        );


        btnFavorito.disabled = true;


        try {

            const respuesta = await fetch(
                "favorito.php",
                {
                    method: "POST",
                    body: datosFormulario,
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With":
                            "XMLHttpRequest"
                    }
                }
            );


            const datos =
                await respuesta.json();


            if (
                !respuesta.ok ||
                !datos.success
            ) {

                // Por ejemplo, sesión expirada.
                if (datos.requiere_login) {

                    cerrarModal();

                    document
                        .getElementById(
                            "abrirModalCuenta"
                        )
                        ?.click();

                    return;
                }


                mensaje.textContent =
                    datos.message ||
                    "No fue posible actualizar favoritos.";

                return;
            }


            actualizarCorazon(
                Boolean(datos.favorito)
            );


            mensaje.textContent =
                datos.message || "";


        } catch (error) {

            mensaje.textContent =
                "No fue posible conectar con el servidor.";

        } finally {

            btnFavorito.disabled = false;
        }
    }

    /* =========================================
    AGREGAR AL CARRITO
    ========================================= */

    async function agregarAlCarrito() {

        if (
            !btnCarrito ||
            !Number.isInteger(productoActualId) ||
            productoActualId < 1
        ) {
            return;
        }


        const csrf =
            btnCarrito.dataset.csrf || "";


        const datosFormulario =
            new FormData();


        datosFormulario.append(
            "producto",
            String(productoActualId)
        );


        datosFormulario.append(
            "csrf",
            csrf
        );


        btnCarrito.disabled = true;

        btnCarrito.textContent =
            "Agregando...";


        mensaje.textContent = "";


        try {

            const respuesta =
                await fetch(
                    "../carrito/agregar.php",
                    {
                        method: "POST",
                        body: datosFormulario,
                        credentials: "same-origin",
                        headers: {
                            "Accept":
                                "application/json",
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );


            const datos =
                await respuesta.json();


            if (
                !respuesta.ok ||
                !datos.success
            ) {

                mensaje.textContent =
                    datos.message ||
                    "No fue posible agregar el producto al carrito.";

                return;
            }


            mensaje.textContent =
                datos.message ||
                "Producto agregado al carrito.";

            const contadorCarrito =
                document.getElementById(
                    "contadorCarrito"
                );

            const textoContadorCarrito =
                document.getElementById(
                    "textoContadorCarrito"
                );


            if (
                Number.isInteger(datos.cantidad_carrito)
            ) {

                if (contadorCarrito) {

                    contadorCarrito.textContent =
                        String(datos.cantidad_carrito);
                }


                if (textoContadorCarrito) {

                    textoContadorCarrito.textContent =
                        `${datos.cantidad_carrito} producto${
                            datos.cantidad_carrito === 1
                                ? ""
                                : "s"
                        }`;
                }
            }    


        } catch (error) {

            mensaje.textContent =
                "No fue posible conectar con el servidor.";

        } finally {

            btnCarrito.disabled = false;

            btnCarrito.textContent =
                "Agregar al carrito";
        }
    }

    btnCarrito?.addEventListener(
        "click",
        agregarAlCarrito
    );

    /* =========================================
    CLIC EN EL CORAZÓN
    ========================================= */

    btnFavorito?.addEventListener(
        "click",
        cambiarFavorito
    );

    /* =========================================
       CLIC EN "VER PRODUCTO"
       ========================================= */

    document.addEventListener(
        "click",
        (evento) => {

            const enlace =
                evento.target.closest(
                    ".producto-enlace-detalle"
                );


            if (!enlace) {
                return;
            }


            const idProducto =
                Number(
                    enlace.dataset.productoId
                );


            if (
                !Number.isInteger(idProducto) ||
                idProducto < 1
            ) {

                return;
            }


            evento.preventDefault();

            cargarProducto(idProducto);

        }
    );


    /* =========================================
       CERRAR CON X
       ========================================= */

    btnCerrar.addEventListener(
        "click",
        cerrarModal
    );


    /* =========================================
       CERRAR HACIENDO CLIC AFUERA
       ========================================= */

    overlay.addEventListener(
        "click",
        cerrarModal
    );


    /* =========================================
       EVITAR CIERRE AL CLIC DENTRO
       ========================================= */

    contenedor.addEventListener(
        "click",
        (evento) => {

            evento.stopPropagation();

        }
    );


    /* =========================================
       CERRAR CON ESCAPE
       ========================================= */

    document.addEventListener(
        "keydown",
        (evento) => {

            if (
                evento.key === "Escape" &&
                modal.classList.contains(
                    "modal-activo"
                )
            ) {

                cerrarModal();

            }

        }
    );


    /* =========================================
       CONTROL DEL MODAL
       ========================================= */
});

document.addEventListener("DOMContentLoaded", () => {

    // =========================================
    // LOGIN DESDE EL MODAL
    // =========================================

    const formLoginModal =
        document.getElementById("formLoginModal");

    const mensajeLoginModal =
        document.getElementById("mensajeLoginModal");


    if (formLoginModal) {

        formLoginModal.addEventListener(
            "submit",
            async (event) => {

                // Evita que el navegador abandone
                // la página actual.
                event.preventDefault();


                // Limpiar mensaje anterior
                if (mensajeLoginModal) {

                    mensajeLoginModal.hidden = true;
                    mensajeLoginModal.textContent = "";
                }


                const formData =
                    new FormData(formLoginModal);


                try {

                    const respuesta =
                        await fetch(
                            formLoginModal.action,
                            {
                                method: "POST",
                                body: formData,
                                credentials: "same-origin",
                                headers: {
                                    "X-Requested-With":
                                        "XMLHttpRequest"
                                }
                            }
                        );


                    const datos =
                        await respuesta.json();


                    // =================================
                    // LOGIN INCORRECTO
                    // =================================
                    if (
                        !respuesta.ok ||
                        !datos.success
                    ) {

                        if (mensajeLoginModal) {

                            mensajeLoginModal.textContent =
                                datos.message ||
                                "No fue posible iniciar sesión.";

                            mensajeLoginModal.hidden =
                                false;
                        }

                        return;
                    }

                // =================================
                // LOGIN CORRECTO
                // =================================

                const destinoLogin =
                    document.body.dataset.destinoLogin
                    || "";


                if (destinoLogin !== "") {

                    window.location.href =
                        destinoLogin;

                } else {

                    window.location.href =
                        datos.redirect;
                }
                    

                } catch (error) {

                    if (mensajeLoginModal) {

                        mensajeLoginModal.textContent =
                            "No fue posible conectar con el servidor.";

                        mensajeLoginModal.hidden =
                            false;
                    }

                }

            }
        );
    }

    // =========================================
    // REGISTRO DESDE EL MODAL
    // =========================================

    const formRegistroModal =
        document.getElementById("formRegistroModal");

    const mensajeRegistroModal =
        document.getElementById("mensajeRegistroModal");


    if (formRegistroModal) {

        formRegistroModal.addEventListener(
            "submit",
            async (event) => {

                // Evita salir de la página actual
                event.preventDefault();


                // =================================
                // LIMPIAR MENSAJE ANTERIOR
                // =================================
                if (mensajeRegistroModal) {

                    mensajeRegistroModal.hidden = true;
                    mensajeRegistroModal.textContent = "";

                    mensajeRegistroModal.classList.remove(
                        "cuenta-mensaje-exito"
                    );

                    mensajeRegistroModal.classList.add(
                        "cuenta-mensaje-error"
                    );
                }


                // =================================
                // BOTÓN SUBMIT
                // =================================
                const botonSubmit =
                    formRegistroModal.querySelector(
                        'button[type="submit"]'
                    );

                if (botonSubmit) {

                    botonSubmit.disabled = true;
                    botonSubmit.textContent =
                        "Creando cuenta...";
                }


                // =================================
                // DATOS DEL FORMULARIO
                // =================================
                const formData =
                    new FormData(formRegistroModal);


                try {

                    const respuesta =
                        await fetch(
                            formRegistroModal.action,
                            {
                                method: "POST",
                                body: formData,
                                credentials: "same-origin",
                                headers: {
                                    "X-Requested-With":
                                        "XMLHttpRequest"
                                }
                            }
                        );


                    const datos =
                        await respuesta.json();


                    // =================================
                    // REGISTRO INCORRECTO
                    // =================================
                    if (
                        !respuesta.ok ||
                        !datos.success
                    ) {

                        if (mensajeRegistroModal) {

                            mensajeRegistroModal.textContent = "";


                            if (
                                Array.isArray(datos.errors) &&
                                datos.errors.length > 0
                            ) {

                                const lista =
                                    document.createElement("ul");


                                datos.errors.forEach(
                                    (error) => {

                                        const item =
                                            document.createElement("li");

                                        item.textContent =
                                            error;

                                        lista.appendChild(
                                            item
                                        );

                                    }
                                );


                                mensajeRegistroModal.appendChild(
                                    lista
                                );

                            } else {

                                mensajeRegistroModal.textContent =
                                    datos.message ||
                                    "Revisa los datos del formulario.";
                            }


                            mensajeRegistroModal.hidden =
                                false;
                        }


                        // =================================
                        // LIMPIAR SOLO CONTRASEÑAS
                        // =================================
                        const clave =
                            formRegistroModal.querySelector(
                                '[name="clave"]'
                            );

                        const confirmarClave =
                            formRegistroModal.querySelector(
                                '[name="confirmar_clave"]'
                            );


                        if (clave) {
                            clave.value = "";
                        }

                        if (confirmarClave) {
                            confirmarClave.value = "";
                        }


                        return;
                    }


                    // =================================
                    // REGISTRO CORRECTO
                    // =================================

                    formRegistroModal.reset();


                    // Mostrar panel de login
                    panelRegistro?.classList.remove(
                        "cuenta-panel-activo"
                    );

                    panelLogin?.classList.add(
                        "cuenta-panel-activo"
                    );


                    // Mostrar mensaje en login
                    if (mensajeLoginModal) {

                        mensajeLoginModal.classList.remove(
                            "cuenta-mensaje-error"
                        );

                        mensajeLoginModal.classList.add(
                            "cuenta-mensaje-exito"
                        );

                        mensajeLoginModal.textContent =
                            datos.message ||
                            "La cuenta fue creada. Revisa tu correo electrónico y verifícala antes de iniciar sesión.";

                        mensajeLoginModal.hidden = false;
                    }


                    // Opcional: pasar el correo registrado al login
                    const correoRegistro =
                        formData.get("correo");

                    const correoLogin =
                        document.getElementById(
                            "modalCorreoLogin"
                        );

                    if (
                        correoLogin &&
                        typeof correoRegistro === "string"
                    ) {
                        correoLogin.value =
                            correoRegistro;
                    }

                } catch (error) {

                    if (mensajeRegistroModal) {

                        mensajeRegistroModal.classList.remove(
                            "cuenta-mensaje-exito"
                        );

                        mensajeRegistroModal.classList.add(
                            "cuenta-mensaje-error"
                        );

                        mensajeRegistroModal.textContent =
                            "No fue posible conectar con el servidor.";

                        mensajeRegistroModal.hidden =
                            false;
                    }


                } finally {

                    if (botonSubmit) {

                        botonSubmit.disabled = false;

                        botonSubmit.textContent =
                            "Crear cuenta";
                    }
                }

            }
        );
    }


    const modalCuenta =
        document.getElementById("modalCuenta");

    const abrirModalCuenta =
        document.getElementById("abrirModalCuenta");

    const cerrarModalCuenta =
        document.getElementById("cerrarModalCuenta");

    if (!modalCuenta || !abrirModalCuenta) {
        return;
    }

    const overlay =
        modalCuenta.querySelector(
            "[data-cerrar-modal-cuenta]"
        );

    const panelLogin =
        document.getElementById("panelLogin");

    const panelRegistro =
        document.getElementById("panelRegistro");

    const botonesRegistro =
        modalCuenta.querySelectorAll(
            "[data-mostrar-registro]"
        );

    const botonesLogin =
        modalCuenta.querySelectorAll(
            "[data-mostrar-login]"
        );


    // =========================================
    // ABRIR
    // =========================================
    function abrirCuenta() {

        modalCuenta.classList.add(
            "modal-activo"
        );

        modalCuenta.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-cuenta-abierto"
        );
    }


    // =========================================
    // CERRAR
    // =========================================
    function cerrarCuenta() {

        modalCuenta.classList.remove(
            "modal-activo"
        );

        modalCuenta.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-cuenta-abierto"
        );
    }


    abrirModalCuenta.addEventListener(
        "click",
        (event) => {

            event.preventDefault();

            abrirCuenta();

        }
    );

    cerrarModalCuenta?.addEventListener(
        "click",
        cerrarCuenta
    );

    overlay?.addEventListener(
        "click",
        cerrarCuenta
    );


    // =========================================
    // ESCAPE
    // =========================================
    document.addEventListener(
        "keydown",
        (event) => {

            if (
                event.key === "Escape" &&
                modalCuenta.classList.contains(
                    "modal-activo"
                )
            ) {
                cerrarCuenta();
            }

        }
    );


    // =========================================
    // MOSTRAR REGISTRO
    // =========================================
    botonesRegistro.forEach((boton) => {

        boton.addEventListener("click", () => {

            panelLogin?.classList.remove(
                "cuenta-panel-activo"
            );

            panelRegistro?.classList.add(
                "cuenta-panel-activo"
            );

        });

    });


    // =========================================
    // MOSTRAR LOGIN
    // =========================================
    botonesLogin.forEach((boton) => {

        boton.addEventListener("click", () => {

            panelRegistro?.classList.remove(
                "cuenta-panel-activo"
            );

            panelLogin?.classList.add(
                "cuenta-panel-activo"
            );

        });

    });


    // =========================================
    // MOSTRAR / OCULTAR CONTRASEÑA
    // =========================================
    const botonesPassword =
        modalCuenta.querySelectorAll(
            "[data-password-target]"
        );

    botonesPassword.forEach((boton) => {

        boton.addEventListener("click", () => {

            const idCampo =
                boton.dataset.passwordTarget;

            const campo =
                document.getElementById(idCampo);

            if (!campo) {
                return;
            }

            const visible =
                campo.type === "text";

            campo.type =
                visible
                    ? "password"
                    : "text";

            boton.setAttribute(
                "aria-label",
                visible
                    ? "Mostrar contraseña"
                    : "Ocultar contraseña"
            );

        });

    });

});

/* =========================================
   CONTINUAR COMPRA DESDE EL CARRITO
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const btnContinuarCompra =
            document.getElementById(
                "btnContinuarCompra"
            );


        if (!btnContinuarCompra) {
            return;
        }


        btnContinuarCompra.addEventListener(
            "click",
            () => {

                const clienteLogueado =
                    btnContinuarCompra.dataset
                        .clienteLogueado === "1";


                const urlCheckout =
                    btnContinuarCompra.dataset
                        .checkout || "";


                // =================================
                // CLIENTE YA LOGUEADO
                // =================================

                if (clienteLogueado) {

                    if (urlCheckout !== "") {

                        window.location.href =
                            urlCheckout;
                    }

                    return;
                }


                // =================================
                // INVITADO
                // =================================
                //
                // Guardamos temporalmente el destino
                // para que, después del login AJAX,
                // vaya al checkout.
                //

                document.body.dataset.destinoLogin =
                    urlCheckout;


                const abrirModalCuenta =
                    document.getElementById(
                        "abrirModalCuenta"
                    );


                if (!abrirModalCuenta) {
                    return;
                }


                abrirModalCuenta.click();


                // =================================
                // ASEGURAR PANEL LOGIN
                // =================================

                const panelLogin =
                    document.getElementById(
                        "panelLogin"
                    );

                const panelRegistro =
                    document.getElementById(
                        "panelRegistro"
                    );


                panelRegistro?.classList.remove(
                    "cuenta-panel-activo"
                );

                panelLogin?.classList.add(
                    "cuenta-panel-activo"
                );


                // =================================
                // MENSAJE AL CLIENTE
                // =================================

                const mensajeLogin =
                    document.getElementById(
                        "mensajeLoginModal"
                    );


                if (mensajeLogin) {

                    mensajeLogin.textContent =
                        "¡Ya casi terminamos! Inicia sesión o crea una cuenta para continuar con tu compra y poder asociar tu pedido.";

                    mensajeLogin.hidden = false;
                }

            }
        );

    }
);

/* =========================================
   CONTADORES DEL CHECKOUT
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const campos =
            document.querySelectorAll(
                "[data-contador-checkout]"
            );


        campos.forEach((campo) => {

            const contenedor =
                campo.closest(
                    ".checkout-campo"
                );


            if (!contenedor) {
                return;
            }


            const contador =
                contenedor.querySelector(
                    ".checkout-contador"
                );


            const numero =
                contador?.querySelector(
                    "span"
                );


            const maximo =
                Number(
                    campo.getAttribute(
                        "maxlength"
                    )
                );


            if (
                !contador ||
                !numero ||
                !Number.isInteger(maximo) ||
                maximo < 1
            ) {
                return;
            }


            function actualizar() {

                const cantidad =
                    campo.value.length;


                numero.textContent =
                    String(cantidad);


                contador.classList.toggle(
                    "checkout-contador-limite",
                    cantidad >= maximo
                );
            }


            actualizar();


            campo.addEventListener(
                "input",
                actualizar
            );

        });

    }
);

/* =========================================
   EVITAR DOBLE ENVÍO DEL CHECKOUT
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const formCheckout =
            document.getElementById(
                "formCheckout"
            );

        const btnConfirmar =
            document.querySelector(
                '.checkout-confirmar[form="formCheckout"]'
            );

        if (
            !formCheckout ||
            !btnConfirmar
        ) {
            return;
        }

        let procesandoPedido = false;

        formCheckout.addEventListener(
            "submit",
            (evento) => {

                if (procesandoPedido) {

                    evento.preventDefault();

                    return;
                }

                procesandoPedido = true;

                btnConfirmar.disabled = true;

                btnConfirmar.textContent =
                    "Procesando pedido...";
            }
        );

    }
);