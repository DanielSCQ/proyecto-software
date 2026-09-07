document.addEventListener("DOMContentLoaded", () => {

    // =========================================
    // CONTADORES DE CARACTERES
    // =========================================
    const camposContador =
        document.querySelectorAll("[data-contador]");

    camposContador.forEach((campo) => {

        const contenedor =
            campo.closest(".campo");

        if (!contenedor) {
            return;
        }

        const contadorActual =
            contenedor.querySelector(".contador-actual");

        const contador =
            contenedor.querySelector(".contador");

        if (!contadorActual || !contador) {
            return;
        }

        const maximo =
            Number(campo.getAttribute("maxlength"));

        function actualizarContador() {

            const cantidad =
                campo.value.length;

            contadorActual.textContent =
                cantidad;

            if (cantidad >= maximo) {

                contador.classList.add(
                    "contador-limite"
                );

            } else {

                contador.classList.remove(
                    "contador-limite"
                );
            }
        }

        actualizarContador();

        campo.addEventListener(
            "input",
            actualizarContador
        );
    });


    // =========================================
    // MOSTRAR / OCULTAR CONTRASEÑA
    // =========================================
    const botonesClave =
        document.querySelectorAll(
            "[data-password-target]"
        );

    botonesClave.forEach((boton) => {

        boton.addEventListener("click", () => {

            const idCampo =
                boton.dataset.passwordTarget;

            const campo =
                document.getElementById(idCampo);

            if (!campo) {
                return;
            }

            const mostrando =
                campo.type === "text";

            campo.type =
                mostrando
                    ? "password"
                    : "text";

            boton.textContent =
                mostrando
                    ? "Mostrar"
                    : "Ocultar";

            boton.setAttribute(
                "aria-label",
                mostrando
                    ? "Mostrar contraseña"
                    : "Ocultar contraseña"
            );
        });
    });

});

document.addEventListener(
    "DOMContentLoaded",
    () => {

        // =========================================
        // NAVEGACIÓN DE MI CUENTA
        // =========================================

        const opciones =
            document.querySelectorAll(
                "[data-cuenta-vista]"
            );

        const vistas =
            document.querySelectorAll(
                "[data-cuenta-contenido]"
            );


        if (
            opciones.length === 0 ||
            vistas.length === 0
        ) {
            return;
        }


        // =========================================
        // CAMBIAR VISTA
        // =========================================

        function mostrarVista(nombre) {

            const vistaSeleccionada =
                document.querySelector(
                    `[data-cuenta-contenido="${nombre}"]`
                );

            const opcionSeleccionada =
                document.querySelector(
                    `[data-cuenta-vista="${nombre}"]`
                );


            if (
                !vistaSeleccionada ||
                !opcionSeleccionada
            ) {
                return;
            }


            // -------------------------------------
            // OCULTAR TODAS LAS VISTAS
            // -------------------------------------

            vistas.forEach((vista) => {

                vista.hidden = true;

                vista.classList.remove(
                    "cuenta-vista-activa"
                );

            });


            // -------------------------------------
            // DESACTIVAR MENÚ
            // -------------------------------------

            opciones.forEach((opcion) => {

                opcion.classList.remove(
                    "cuenta-menu-opcion-activa"
                );

                opcion.setAttribute(
                    "aria-selected",
                    "false"
                );

            });


            // -------------------------------------
            // MOSTRAR SELECCIONADA
            // -------------------------------------

            vistaSeleccionada.hidden = false;

            vistaSeleccionada.classList.add(
                "cuenta-vista-activa"
            );


            opcionSeleccionada.classList.add(
                "cuenta-menu-opcion-activa"
            );

            opcionSeleccionada.setAttribute(
                "aria-selected",
                "true"
            );

        }


        // =========================================
        // EVENTOS
        // =========================================

        opciones.forEach((opcion) => {

            opcion.addEventListener(
                "click",
                () => {

                    const nombre =
                        opcion.dataset.cuentaVista;

                    mostrarVista(nombre);

                }
            );

        });

    }
);

/* =========================================
   QUITAR FAVORITOS DESDE MI CUENTA
========================================= */

document.addEventListener("DOMContentLoaded", () => {

    const contadorFavoritos =
    document.querySelector(
        '[data-cuenta-vista="favoritos"] .cuenta-menu-contador'
    );


    document.addEventListener(
        "click",
        async (evento) => {

            const boton =
                evento.target.closest(
                    "[data-quitar-favorito]"
                );


            if (!boton) {
                return;
            }


            const idProducto =
                Number(
                    boton.dataset.productoId
                );

            const csrf =
                boton.dataset.csrf || "";


            if (
                !Number.isInteger(idProducto) ||
                idProducto < 1 ||
                csrf === ""
            ) {
                return;
            }


            boton.disabled = true;
            boton.textContent = "Quitando...";


            const formulario =
                new FormData();

            formulario.append(
                "producto",
                String(idProducto)
            );

            formulario.append(
                "csrf",
                csrf
            );


            try {

                const respuesta =
                    await fetch(
                        "../productos/favorito.php",
                        {
                            method: "POST",

                            body: formulario,

                            credentials:
                                "same-origin",

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

                    throw new Error(
                        datos.message ||
                        "No fue posible quitar el producto."
                    );
                }


                // =================================
                // QUITAR TARJETA VISUALMENTE
                // =================================

                const tarjeta =
                    boton.closest(
                        "[data-favorito-card]"
                    );


                if (tarjeta) {

                    tarjeta.remove();
                }


                // =================================
                // ACTUALIZAR CONTADOR
                // =================================

                const tarjetasRestantes =
                    document.querySelectorAll(
                        "[data-favorito-card]"
                    );


                const total =
                    tarjetasRestantes.length;


                if (contadorFavoritos) {

                    contadorFavoritos.textContent =
                        String(total);
                }


                // =================================
                // SI YA NO QUEDA NINGUNO
                // =================================

                if (total === 0) {

                    const lista =
                        document.querySelector(
                            ".cuenta-favoritos-lista"
                        );


                    if (lista) {

                        const vacio =
                            document.createElement(
                                "div"
                            );

                        vacio.className =
                            "cuenta-estado-vacio";


                        const titulo =
                            document.createElement(
                                "strong"
                            );

                        titulo.textContent =
                            "Aún no tienes favoritos.";


                        const texto =
                            document.createElement(
                                "p"
                            );

                        texto.textContent =
                            "Guarda productos para encontrarlos fácilmente después.";


                        lista.replaceWith(
                            vacio
                        );

                        vacio.appendChild(
                            titulo
                        );

                        vacio.appendChild(
                            texto
                        );
                    }
                }


            } catch (error) {

                alert(
                    error.message ||
                    "No fue posible quitar el favorito."
                );


                boton.disabled = false;
                boton.textContent = "Quitar";

            }

        }
    );

});