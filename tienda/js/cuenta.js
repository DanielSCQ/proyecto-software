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
        // ABRIR VISTA DESDE LA URL
        // =========================================

        const parametros =
            new URLSearchParams(
                window.location.search
            );

        const vistaUrl =
            parametros.get("vista");


        if (vistaUrl) {

            mostrarVista(vistaUrl);

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

    /* =========================================
    EDITAR DATOS DE MI CUENTA
    ========================================= */

    document.addEventListener(
        "DOMContentLoaded",
        () => {

            const botonEditar =
                document.getElementById(
                    "btnEditarDatos"
                );

            const formulario =
                document.getElementById(
                    "formEditarDatos"
                );

            const listaDatos =
                document.querySelector(
                    '[data-cuenta-contenido="datos"] .cuenta-datos-lista'
                );

            const accionesDatos =
                document.querySelector(
                    '[data-cuenta-contenido="datos"] .cuenta-vista-acciones'
                );

            const botonCancelar =
                document.getElementById(
                    "btnCancelarEdicion"
                );

            const botonCorreo =
                document.getElementById(
                    "btnCorreoBloqueado"
                );

            const avisoCorreo =
                document.getElementById(
                    "avisoCorreo"
                );


            if (
                !botonEditar ||
                !formulario ||
                !listaDatos ||
                !accionesDatos
            ) {
                return;
            }


            // =====================================
            // ABRIR EDICIÓN
            // =====================================
            botonEditar.addEventListener(
                "click",
                () => {

                    listaDatos.hidden = true;

                    accionesDatos.hidden = true;

                    formulario.hidden = false;


                    const nombre =
                        document.getElementById(
                            "editarNombre"
                        );

                    if (nombre) {
                        nombre.focus();
                    }
                }
            );


            // =====================================
            // CANCELAR EDICIÓN
            // =====================================
            if (botonCancelar) {

                botonCancelar.addEventListener(
                    "click",
                    () => {

                        formulario.hidden = true;

                        listaDatos.hidden = false;

                        accionesDatos.hidden = false;


                        if (avisoCorreo) {
                            avisoCorreo.hidden = true;
                        }


                        // Restaurar valores originales
                        formulario.reset();


                        // Actualizar nuevamente
                        // los contadores
                        formulario
                            .querySelectorAll(
                                "[data-contador]"
                            )
                            .forEach((campo) => {

                                campo.dispatchEvent(
                                    new Event("input")
                                );

                            });

                    }
                );
            }


            // =====================================
            // AVISO DEL CORREO
            // =====================================
            if (
                botonCorreo &&
                avisoCorreo
            ) {

                botonCorreo.addEventListener(
                    "click",
                    () => {

                        avisoCorreo.hidden =
                            !avisoCorreo.hidden;

                    }
                );
            }


            // =====================================
            // EVITAR DOBLE ENVÍO
            // =====================================
            formulario.addEventListener(
                "submit",
                () => {

                    const botonGuardar =
                        document.getElementById(
                            "btnGuardarDatos"
                        );


                    if (botonGuardar) {

                        botonGuardar.disabled = true;

                        botonGuardar.textContent =
                            "Guardando...";
                    }

                }
            );

        }
    );

    // =========================================
    // FORMULARIO AGREGAR DIRECCIÓN
    // =========================================

    const botonesAgregarDireccion =
        document.querySelectorAll(
            "#btnAgregarDireccion"
        );

    const formularioDireccion =
        document.querySelector(
            ".cuenta-direccion-formulario"
        );


    if (
        botonesAgregarDireccion.length > 0 &&
        formularioDireccion
    ) {

        botonesAgregarDireccion.forEach(
            (boton) => {

                boton.addEventListener(
                    "click",
                    () => {

                        const estaOculto =
                            formularioDireccion.hasAttribute(
                                "hidden"
                            );

                        if (estaOculto) {

                            formularioDireccion.removeAttribute(
                                "hidden"
                            );

                            formularioDireccion.scrollIntoView({
                                behavior: "smooth",
                                block: "start"
                            });

                        } else {

                            formularioDireccion.setAttribute(
                                "hidden",
                                ""
                            );
                        }

                    }
                );

            }
        );
    }

// =========================================
// EDITAR DIRECCIÓN
// =========================================

const botonesEditarDireccion =
    document.querySelectorAll(
        "[data-editar-direccion]"
    );

const formDireccion =
    document.querySelector(
        "#formDireccion"
    );

const contenedorFormularioDireccion =
    document.querySelector(
        ".cuenta-direccion-formulario"
    );

const tituloFormularioDireccion =
    document.querySelector(
        "#tituloFormularioDireccion"
    );

const btnGuardarDireccion =
    document.querySelector(
        "#btnGuardarDireccion"
    );

const btnCancelarDireccion =
    document.querySelector(
        "#btnCancelarDireccion"
    );


if (
    botonesEditarDireccion.length > 0 &&
    formDireccion &&
    contenedorFormularioDireccion
) {

    botonesEditarDireccion.forEach(
        (boton) => {

            boton.addEventListener(
                "click",
                () => {

                    const idDireccion =
                        boton.dataset.direccionId ?? "";

                    const nombre =
                        boton.dataset.nombre ?? "";

                    const receptor =
                        boton.dataset.receptor ?? "";

                    const telefono =
                        boton.dataset.telefono ?? "";

                    const direccion =
                        boton.dataset.direccion ?? "";

                    const barrio =
                        boton.dataset.barrio ?? "";

                    const municipio =
                        boton.dataset.municipio ?? "";

                    const departamento =
                        boton.dataset.departamento ?? "";

                    const referencia =
                        boton.dataset.referencia ?? "";

                    const principal =
                        boton.dataset.principal === "1";


                    // =================================
                    // CAMBIAR A MODO EDICIÓN
                    // =================================

                    formDireccion.action =
                        "actualizar_direccion.php";


                    // =================================
                    // CARGAR ID
                    // =================================

                    const campoId =
                        document.querySelector(
                            "#direccion_id"
                        );

                    if (campoId) {

                        campoId.value =
                            idDireccion;
                    }


                    // =================================
                    // CARGAR CAMPOS
                    // =================================

                    const campoNombre =
                        document.querySelector(
                            "#direccion_nombre"
                        );

                    const campoReceptor =
                        document.querySelector(
                            "#direccion_receptor"
                        );

                    const campoTelefono =
                        document.querySelector(
                            "#direccion_telefono"
                        );

                    const campoDireccion =
                        document.querySelector(
                            "#direccion_direccion"
                        );

                    const campoBarrio =
                        document.querySelector(
                            "#direccion_barrio"
                        );

                    const campoMunicipio =
                        document.querySelector(
                            "#direccion_municipio"
                        );

                    const campoDepartamento =
                        document.querySelector(
                            "#direccion_departamento"
                        );

                    const campoReferencia =
                        document.querySelector(
                            "#direccion_referencia"
                        );

                    const campoPrincipal =
                        formDireccion.querySelector(
                            'input[name="principal"]'
                        );


                    if (campoNombre) {
                        campoNombre.value = nombre;
                    }

                    if (campoReceptor) {
                        campoReceptor.value = receptor;
                    }

                    if (campoTelefono) {
                        campoTelefono.value = telefono;
                    }

                    if (campoDireccion) {
                        campoDireccion.value = direccion;
                    }

                    if (campoBarrio) {
                        campoBarrio.value = barrio;
                    }

                    if (campoMunicipio) {
                        campoMunicipio.value = municipio;
                    }

                    if (campoDepartamento) {
                        campoDepartamento.value =
                            departamento;
                    }

                    if (campoReferencia) {
                        campoReferencia.value =
                            referencia;
                    }

                    if (campoPrincipal) {
                        campoPrincipal.checked =
                            principal;
                    }


                    // =================================
                    // CAMBIAR TEXTOS
                    // =================================

                    if (tituloFormularioDireccion) {

                        tituloFormularioDireccion.textContent =
                            "Editar dirección";
                    }

                    if (btnGuardarDireccion) {

                        btnGuardarDireccion.textContent =
                            "Guardar cambios";
                    }


                    // =================================
                    // MOSTRAR CANCELAR
                    // =================================

                    if (btnCancelarDireccion) {

                        btnCancelarDireccion.hidden =
                            false;
                    }


                    // =================================
                    // MOSTRAR FORMULARIO
                    // =================================

                    contenedorFormularioDireccion.hidden =
                        false;


                    contenedorFormularioDireccion.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }
            );

        }
    );
}    

// =========================================
// CANCELAR EDICIÓN DE DIRECCIÓN
// =========================================

if (
    btnCancelarDireccion &&
    formDireccion &&
    contenedorFormularioDireccion
) {

    btnCancelarDireccion.addEventListener(
        "click",
        () => {

            // =================================
            // VOLVER A MODO AGREGAR
            // =================================

            formDireccion.action =
                "guardar_direccion.php";


            // =================================
            // LIMPIAR ID
            // =================================

            const campoId =
                document.querySelector(
                    "#direccion_id"
                );

            if (campoId) {
                campoId.value = "";
            }


            // =================================
            // LIMPIAR CAMPOS
            // =================================

            formDireccion.reset();


            // =================================
            // CAMBIAR TEXTOS
            // =================================

            if (tituloFormularioDireccion) {

                tituloFormularioDireccion.textContent =
                    "Agregar nueva dirección";
            }

            if (btnGuardarDireccion) {

                btnGuardarDireccion.textContent =
                    "Guardar dirección";
            }


            // =================================
            // OCULTAR CANCELAR
            // =================================

            btnCancelarDireccion.hidden =
                true;


            // =================================
            // OCULTAR FORMULARIO
            // =================================

            contenedorFormularioDireccion.hidden =
                true;

        }
    );
}

// =========================================
// ELIMINAR DIRECCIÓN
// =========================================

const botonesEliminarDireccion =
    document.querySelectorAll(
        "[data-eliminar-direccion]"
    );


if (botonesEliminarDireccion.length > 0) {

    botonesEliminarDireccion.forEach(
        (boton) => {

            boton.addEventListener(
                "click",
                () => {

                    const idDireccion =
                        boton.dataset.direccionId ?? "";

                    if (idDireccion === "") {
                        return;
                    }


                    // =================================
                    // CONFIRMACIÓN
                    // =================================

                    const confirmar =
                        window.confirm(
                            "¿Seguro que deseas eliminar esta dirección?"
                        );


                    if (!confirmar) {
                        return;
                    }


                    // =================================
                    // CREAR FORMULARIO TEMPORAL
                    // =================================

                    const formulario =
                        document.createElement(
                            "form"
                        );

                    formulario.method =
                        "POST";

                    formulario.action =
                        "eliminar_direccion.php";


                    // =================================
                    // ID DIRECCIÓN
                    // =================================

                    const campoId =
                        document.createElement(
                            "input"
                        );

                    campoId.type =
                        "hidden";

                    campoId.name =
                        "id_direccion";

                    campoId.value =
                        idDireccion;


                    // =================================
                    // CSRF
                    // =================================

                    const campoCsrf =
                        document.createElement(
                            "input"
                        );

                    campoCsrf.type =
                        "hidden";

                    campoCsrf.name =
                        "csrf";

                    campoCsrf.value =
                        document.querySelector(
                            '#formDireccion input[name="csrf"]'
                        )?.value ?? "";


                    // =================================
                    // ENVIAR
                    // =================================

                    formulario.appendChild(
                        campoId
                    );

                    formulario.appendChild(
                        campoCsrf
                    );

                    document.body.appendChild(
                        formulario
                    );

                    formulario.submit();

                }
            );

        }
    );
}

// =========================================
// HACER DIRECCIÓN PRINCIPAL
// =========================================

const botonesPrincipalDireccion =
    document.querySelectorAll(
        "[data-principal-direccion]"
    );


if (botonesPrincipalDireccion.length > 0) {

    botonesPrincipalDireccion.forEach(
        (boton) => {

            boton.addEventListener(
                "click",
                () => {

                    const idDireccion =
                        boton.dataset.direccionId ?? "";

                    if (idDireccion === "") {
                        return;
                    }


                    const confirmar =
                        window.confirm(
                            "¿Deseas usar esta dirección como principal?"
                        );


                    if (!confirmar) {
                        return;
                    }


                    const formulario =
                        document.createElement(
                            "form"
                        );

                    formulario.method =
                        "POST";

                    formulario.action =
                        "hacer_principal_direccion.php";


                    const campoId =
                        document.createElement(
                            "input"
                        );

                    campoId.type =
                        "hidden";

                    campoId.name =
                        "id_direccion";

                    campoId.value =
                        idDireccion;


                    const campoCsrf =
                        document.createElement(
                            "input"
                        );

                    campoCsrf.type =
                        "hidden";

                    campoCsrf.name =
                        "csrf";

                    campoCsrf.value =
                        document.querySelector(
                            '#formDireccion input[name="csrf"]'
                        )?.value ?? "";


                    formulario.appendChild(
                        campoId
                    );

                    formulario.appendChild(
                        campoCsrf
                    );

                    document.body.appendChild(
                        formulario
                    );

                    formulario.submit();

                }
            );

        }
    );
}