<?php

// =================================
// SESIÓN
// =================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// =================================
// CSRF DEL CARRITO
// =================================

if (
    empty($_SESSION["csrf_carrito"]) ||
    !is_string($_SESSION["csrf_carrito"])
) {
    $_SESSION["csrf_carrito"] =
        bin2hex(random_bytes(32));
}


// =================================
// HEADER
// =================================

require_once("../includes/header.php");


// =================================
// OBTENER CARRITO DE SESIÓN
// =================================

$carritoSesion =
    isset($_SESSION["carrito"]) &&
    is_array($_SESSION["carrito"])
        ? $_SESSION["carrito"]
        : [];

$productosCarrito = [];

$totalCarritoCompra = 0;


// =================================
// CONSULTA DE PRODUCTOS
// =================================

$sqlProducto = "
    SELECT
        p.id_producto,
        p.nombre,
        p.codigo_producto,
        p.precio,
        p.estado AS producto_estado,
        c.nombre AS categoria_nombre,
        c.estado AS categoria_estado,
        m.nombre AS marca_nombre,
        COALESCE(i.stock_actual, 0) AS stock_actual,
        img.ruta_imagen

    FROM productos p

    INNER JOIN categorias c
        ON c.id_categoria = p.id_categoria

    LEFT JOIN marcas m
        ON m.id_marca = p.id_marca

    LEFT JOIN inventario i
        ON i.id_producto = p.id_producto

    LEFT JOIN imagenes_producto img
        ON img.id_producto = p.id_producto
        AND img.principal = 1
        AND img.estado = 1

    WHERE p.id_producto = ?

    LIMIT 1
";

$stmtProducto =
    $conexion->prepare($sqlProducto);


// =================================
// CONSTRUIR CARRITO
// =================================

if ($stmtProducto) {

    foreach ($carritoSesion as $idProducto => $item) {

        $idProducto =
            filter_var(
                $idProducto,
                FILTER_VALIDATE_INT
            );

        $cantidad =
            (int) ($item["cantidad"] ?? 0);


        if (
            $idProducto === false ||
            $idProducto < 1 ||
            $cantidad < 1
        ) {
            continue;
        }


        $stmtProducto->bind_param(
            "i",
            $idProducto
        );

        $stmtProducto->execute();

        $resultado =
            $stmtProducto->get_result();

        $producto =
            $resultado->fetch_assoc();


        if (!$producto) {
            continue;
        }


        $precio =
            (float) $producto["precio"];

        $stock =
            (int) $producto["stock_actual"];


        $disponible =
            (int) $producto["producto_estado"] === 1 &&
            (int) $producto["categoria_estado"] === 1 &&
            $stock > 0;


        // Si por alguna razón la cantidad de sesión
        // supera el stock actual, no usamos una cantidad
        // mayor para calcular la compra.
        $cantidadValida =
            $disponible
                ? min($cantidad, $stock)
                : $cantidad;


        $subtotal =
            $disponible
                ? $precio * $cantidadValida
                : 0;


        if ($disponible) {
            $totalCarritoCompra += $subtotal;
        }


        $productosCarrito[] = [
            "id_producto" =>
                (int) $producto["id_producto"],

            "nombre" =>
                $producto["nombre"],

            "codigo_producto" =>
                $producto["codigo_producto"],

            "categoria" =>
                $producto["categoria_nombre"],

            "marca" =>
                $producto["marca_nombre"],

            "precio" =>
                $precio,

            "stock" =>
                $stock,

            "cantidad" =>
                $cantidad,

            "subtotal" =>
                $subtotal,

            "imagen" =>
                $producto["ruta_imagen"],

            "disponible" =>
                $disponible
        ];
    }


    $stmtProducto->close();
}

?>

<link
    rel="stylesheet"
    href="<?= $base_url ?>css/carrito.css"
>


<main class="carrito-page">

    <div class="carrito-container">


        <!-- =================================
             ENCABEZADO
        ================================== -->

        <section class="carrito-encabezado">

            <span class="carrito-etiqueta">
                TU COMPRA
            </span>

            <h1>
                Mi carrito
            </h1>

            <p>
                Revisa tus productos antes de continuar con la compra.
            </p>

        </section>


        <?php if (empty($productosCarrito)): ?>


            <!-- =================================
                 CARRITO VACÍO
            ================================== -->

            <section class="carrito-vacio">

                <div class="carrito-vacio-icono">
                    🛒
                </div>

                <h2>
                    Tu carrito está vacío
                </h2>

                <p>
                    Explora nuestro catálogo y agrega los repuestos
                    que necesitas.
                </p>

                <a
                    href="<?= $base_url ?>productos/"
                    class="carrito-btn-principal"
                >
                    Ver productos
                </a>

            </section>


        <?php else: ?>


            <div class="carrito-layout">


                <!-- =================================
                     PRODUCTOS
                ================================== -->

                <section class="carrito-productos">

                    <div class="carrito-productos-titulo">

                        <h2>
                            Productos
                        </h2>

                        <span>
                            <?= count($productosCarrito) ?>
                            referencia<?= count($productosCarrito) === 1 ? "" : "s" ?>
                        </span>

                    </div>


                    <?php foreach ($productosCarrito as $producto): ?>

                        <article class="carrito-item">


                            <!-- IMAGEN -->

                            <div class="carrito-item-imagen">

                                <?php if (!empty($producto["imagen"])): ?>

                                    <img
                                        src="<?= $base_url ?>../<?= htmlspecialchars(
                                            $producto["imagen"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $producto["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        Sin imagen
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- INFORMACIÓN -->

                            <div class="carrito-item-info">

                                <?php if (!empty($producto["codigo_producto"])): ?>

                                    <span class="carrito-item-codigo">
                                        <?= htmlspecialchars(
                                            $producto["codigo_producto"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>

                                <?php endif; ?>


                                <h3>
                                    <?= htmlspecialchars(
                                        $producto["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </h3>


                                <div class="carrito-item-meta">

                                    <?php if (!empty($producto["categoria"])): ?>

                                        <span>
                                            <?= htmlspecialchars(
                                                $producto["categoria"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if (!empty($producto["marca"])): ?>

                                        <span>
                                            <?= htmlspecialchars(
                                                $producto["marca"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if ($producto["disponible"]): ?>

                                    <span class="carrito-stock carrito-stock-disponible">
                                        <?= $producto["stock"] ?> disponible<?= $producto["stock"] === 1 ? "" : "s" ?>
                                    </span>

                                <?php else: ?>

                                    <span class="carrito-stock carrito-stock-agotado">
                                        Producto no disponible
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- PRECIO -->

                            <div class="carrito-item-precio">

                                <span>
                                    Precio
                                </span>

                                <strong>
                                    $<?= number_format(
                                        $producto["precio"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                                </strong>

                            </div>


                            <!-- CANTIDAD -->

                            <div class="carrito-cantidad">

                                <span class="carrito-cantidad-label">
                                    Cantidad
                                </span>


                                <div class="carrito-cantidad-control">


                                    <!-- RESTAR -->

                                    <form
                                        action="<?= $base_url ?>carrito/actualizar.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= htmlspecialchars(
                                                $_SESSION["csrf_carrito"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="producto"
                                            value="<?= $producto["id_producto"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="restar"
                                        >

                                        <button
                                            type="submit"
                                            aria-label="Restar una unidad"
                                            <?= $producto["cantidad"] <= 1 ? "disabled" : "" ?>
                                        >
                                            −
                                        </button>

                                    </form>


                                    <strong>
                                        <?= $producto["cantidad"] ?>
                                    </strong>


                                    <!-- SUMAR -->

                                    <form
                                        action="<?= $base_url ?>carrito/actualizar.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= htmlspecialchars(
                                                $_SESSION["csrf_carrito"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="producto"
                                            value="<?= $producto["id_producto"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="sumar"
                                        >

                                        <button
                                            type="submit"
                                            aria-label="Agregar una unidad"
                                            <?= (
                                                !$producto["disponible"] ||
                                                $producto["cantidad"] >= $producto["stock"]
                                            ) ? "disabled" : "" ?>
                                        >
                                            +
                                        </button>

                                    </form>

                                </div>

                            </div>


                            <!-- SUBTOTAL -->

                            <div class="carrito-item-subtotal">

                                <span>
                                    Subtotal
                                </span>

                                <strong>
                                    <?php if ($producto["disponible"]): ?>

                                        $<?= number_format(
                                            $producto["subtotal"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>
                                </strong>

                            </div>


                            <!-- ELIMINAR -->

                            <form
                                action="<?= $base_url ?>carrito/eliminar.php"
                                method="POST"
                                class="carrito-eliminar-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["csrf_carrito"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="producto"
                                    value="<?= $producto["id_producto"] ?>"
                                >

                                <button
                                    type="submit"
                                    class="carrito-eliminar"
                                >
                                    Eliminar
                                </button>

                            </form>


                        </article>

                    <?php endforeach; ?>

                </section>


                <!-- =================================
                     RESUMEN
                ================================== -->

                <aside class="carrito-resumen">

                    <span class="carrito-etiqueta">
                        RESUMEN
                    </span>

                    <h2>
                        Resumen de compra
                    </h2>


                    <div class="carrito-resumen-fila">

                        <span>
                            Productos
                        </span>

                        <strong>
                            <?= $totalCarrito ?> unidad<?= $totalCarrito === 1 ? "" : "es" ?>
                        </strong>

                    </div>


                    <div class="carrito-resumen-fila carrito-resumen-total">

                        <span>
                            Total
                        </span>

                        <strong>
                            $<?= number_format(
                                $totalCarritoCompra,
                                0,
                                ",",
                                "."
                            ) ?>
                        </strong>

                    </div>


                    <button
                        type="button"
                        class="carrito-comprar"
                        id="btnContinuarCompra"
                        data-cliente-logueado="<?= $clienteLogueado ? "1" : "0" ?>"
                        data-checkout="<?= htmlspecialchars(
                            $base_url . "checkout/",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >
                        Continuar con la compra
                    </button>

                    <a
                        href="<?= $base_url ?>productos/"
                        class="carrito-seguir"
                    >
                        Seguir comprando
                    </a>


                    <p class="carrito-resumen-ayuda">
                        El stock y los precios se verificarán nuevamente
                        antes de confirmar el pedido.
                    </p>

                </aside>

            </div>

        <?php endif; ?>

    </div>

</main>


<?php

require_once("../includes/footer.php");

?>