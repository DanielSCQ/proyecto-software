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
                    window.location.href =
                        datos.redirect;


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