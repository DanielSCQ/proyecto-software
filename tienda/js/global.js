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

});