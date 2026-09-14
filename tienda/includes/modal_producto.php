    <!-- =================================
     MODAL DETALLE DEL PRODUCTO
     ================================= -->

    <div
        id="modalProducto"
        class="modal-producto"
        aria-hidden="true"

        data-detalle-url="<?= htmlspecialchars(
            $base_url . "productos/detalle.php",
            ENT_QUOTES,
            "UTF-8"
        ) ?>"

        data-favorito-url="<?= htmlspecialchars(
            $base_url . "productos/favorito.php",
            ENT_QUOTES,
            "UTF-8"
        ) ?>"

        data-carrito-url="<?= htmlspecialchars(
            $base_url . "carrito/agregar.php",
            ENT_QUOTES,
            "UTF-8"
        ) ?>"
    >

        <div class="modal-producto-overlay"></div>


        <div
            class="modal-producto-contenedor"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modalProductoNombre"
        >

            <!-- BOTÓN CERRAR -->

            <button
                type="button"
                class="modal-producto-cerrar"
                id="cerrarModalProducto"
                aria-label="Cerrar detalle del producto"
            >
                ×
            </button>


            <!-- CONTENIDO -->

            <div class="modal-producto-contenido">

                <!-- IMAGEN -->

                <div class="modal-producto-imagen">

                    <img
                        id="modalProductoImagen"
                        src=""
                        alt=""
                    >

                    <div
                        id="modalProductoSinImagen"
                        class="modal-producto-sin-imagen"
                        hidden
                    >
                        Sin imagen
                    </div>

                </div>


                <!-- INFORMACIÓN -->

                <div class="modal-producto-info">

                    <span
                        id="modalProductoCategoria"
                        class="modal-producto-categoria"
                    ></span>


                    <h2 id="modalProductoNombre">
                        Producto
                    </h2>


                    <span
                        id="modalProductoCodigo"
                        class="modal-producto-codigo"
                    ></span>


                    <p
                        id="modalProductoDescripcion"
                        class="modal-producto-descripcion"
                    ></p>


                    <!-- CARACTERÍSTICAS -->

                    <div
                        id="modalProductoCaracteristicas"
                        class="modal-producto-caracteristicas"
                        hidden
                    >

                        <h3>
                            Características
                        </h3>

                        <div
                            id="listaCaracteristicas"
                            class="lista-caracteristicas"
                        ></div>

                    </div>


                    <!-- DATOS -->

                    <div class="modal-producto-datos">

                        <div class="modal-dato">

                            <span>
                                Marca
                            </span>

                            <strong id="modalProductoMarca">
                                —
                            </strong>

                        </div>


                        <div class="modal-dato">

                            <span>
                                Peso
                            </span>

                            <strong id="modalProductoPeso">
                                —
                            </strong>

                        </div>


                        <div class="modal-dato">

                            <span>
                                Disponibilidad
                            </span>

                            <strong id="modalProductoStock">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- PRECIO -->

                    <div class="modal-producto-precio">

                        <span>
                            Precio
                        </span>

                        <strong id="modalProductoPrecio">
                            $0
                        </strong>

                    </div>


                    <!-- ACCIONES -->

                    <div class="modal-producto-acciones">

                        <button
                            type="button"
                            class="modal-btn-carrito"
                            id="modalBtnCarrito"
                            data-csrf="<?= htmlspecialchars(
                                $csrfCarrito,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            disabled
                        >
                            Agregar al carrito
                        </button>

                        <button
                            type="button"
                            class="modal-btn-favorito"
                            id="modalBtnFavorito"
                            data-cliente-logueado="<?= $clienteLogueado ? "1" : "0" ?>"
                            data-csrf="<?= htmlspecialchars(
                                $csrfFavoritos,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            aria-label="Agregar a favoritos"
                            aria-pressed="false"
                        >
                            ♡
                        </button>

                    </div>


                    <!-- ESTADO -->

                    <p
                        id="modalProductoMensaje"
                        class="modal-producto-mensaje"
                        aria-live="polite"
                    ></p>

                </div>

            </div>

        </div>

    </div>
