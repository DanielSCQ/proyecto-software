<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf_carrito']) || !is_string($_SESSION['csrf_carrito'])) {
    $_SESSION['csrf_carrito'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/carrito.php';
$_SESSION['carrito'] = is_array($_SESSION['carrito'] ?? null) ? $_SESSION['carrito'] : [];
try {
    $estadoCarrito = agranda_carrito_estado($conexion, $_SESSION['carrito']);
} catch (Throwable $error) {
    http_response_code(503);
    exit('No fue posible consultar tu carrito. Inténtalo nuevamente.');
}
$productosCarrito = $estadoCarrito['productos'];
$avisosCarrito = $_SESSION['carrito_avisos'] ?? [];
$mensajeCarrito = $_SESSION['carrito_mensaje'] ?? '';
unset($_SESSION['carrito_avisos'], $_SESSION['carrito_mensaje']);
require_once __DIR__ . '/../includes/header.php';
?>

<link
    rel="stylesheet"
    href="<?= $base_url . v_tienda('css/carrito.css') ?>"
>

<script src="<?= htmlspecialchars($base_url . v_tienda("js/carrito.js"), ENT_QUOTES, "UTF-8") ?>" defer></script>
<main class="carrito-page" data-carrito-url="<?= htmlspecialchars($base_url . "carrito/actualizar.php", ENT_QUOTES, "UTF-8") ?>" data-carrito-csrf="<?= htmlspecialchars($_SESSION["csrf_carrito"], ENT_QUOTES, "UTF-8") ?>">

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


        <p class="carrito-mensaje" role="status" aria-live="polite"><?= htmlspecialchars($mensajeCarrito, ENT_QUOTES, "UTF-8") ?></p>
        <button type="button" class="carrito-reintentar" hidden>Consultar estado del carrito</button>
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

                        <article class="carrito-item" data-producto="<?= $producto["id_producto"] ?>">


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
                                        <?= htmlspecialchars($producto["disponibilidad_texto"], ENT_QUOTES, "UTF-8") ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- PRECIO -->

                            <div class="carrito-item-precio">

                                <span>
                                    Precio
                                </span>

                                <strong>
                                    <?= $producto["precio_formateado"] ?>
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
                                        action="<?= $base_url ?>carrito/actualizar.php" data-carrito-actualizar
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
                                            <?= !$producto["puede_restar"] ? "disabled" : "" ?>
                                        >
                                            −
                                        </button>

                                    </form>


                                    <strong>
                                        <?= $producto["cantidad"] ?>
                                    </strong>


                                    <!-- SUMAR -->

                                    <form
                                        action="<?= $base_url ?>carrito/actualizar.php" data-carrito-actualizar
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
                                            <?= !$producto["puede_sumar"] ? "disabled" : "" ?>
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
                                    <?= $producto["subtotal_formateado"] ?>
                                </strong>

                            </div>


                            <p class="carrito-item-aviso" role="status"><?= htmlspecialchars(in_array($producto["id_producto"], $avisosCarrito, true) ? "La cantidad de este producto fue ajustada al stock disponible." : $producto["mensaje"], ENT_QUOTES, "UTF-8") ?></p>
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
                            <?= $estadoCarrito["total_formateado"] ?>
                        </strong>

                    </div>


                    <button
                        type="button"
                        class="carrito-comprar"
                        id="btnContinuarCompra" <?= !$estadoCarrito["puede_continuar"] ? "disabled" : "" ?>
                        data-cliente-logueado="<?= $clienteLogueado ? "1" : "0" ?>"
                        data-checkout="<?= htmlspecialchars(
                            $base_url . "checkout/",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >
                        Continuar con la compra
                    </button>

                    <p class="carrito-bloqueo" role="status"><?= htmlspecialchars($estadoCarrito["bloqueo_texto"], ENT_QUOTES, "UTF-8") ?></p>
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