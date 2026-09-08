<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

/* =========================================
   CSRF - GESTIÓN DEL PEDIDO
========================================= */

if (
    empty($_SESSION["csrf_pedidos"]) ||
    !is_string($_SESSION["csrf_pedidos"])
) {
    $_SESSION["csrf_pedidos"] =
        bin2hex(random_bytes(32));
}

/* =========================================
   VALIDAR ID DEL PEDIDO
========================================= */

$idPedido = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if (
    !$idPedido ||
    $idPedido <= 0
) {

    header("Location: pedidos.php");
    exit();
}


/* =========================================
   CONSULTAR PEDIDO
========================================= */

$sqlPedido = "
    SELECT
        p.id_pedido,
        p.total,
        p.metodo_pago,
        p.fecha_pedido,
        p.fecha_actualizacion,

        u.id_usuario,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,

        d.nombre AS nombre_direccion,
        d.receptor,
        d.telefono AS telefono_receptor,
        d.direccion,
        d.barrio,
        d.municipio,
        d.departamento,
        d.referencia,

        ep.id_estado,
        ep.nombre AS estado,
        ep.descripcion AS descripcion_estado

    FROM pedidos p

    INNER JOIN usuarios u
        ON p.id_usuario = u.id_usuario

    INNER JOIN direcciones d
        ON p.id_direccion = d.id_direccion

    INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

    WHERE p.id_pedido = ?

    LIMIT 1
";


$stmtPedido =
    $conexion->prepare($sqlPedido);


if (!$stmtPedido) {

    die("No fue posible consultar el pedido.");
}


$stmtPedido->bind_param(
    "i",
    $idPedido
);

$stmtPedido->execute();

$pedido =
    $stmtPedido
        ->get_result()
        ->fetch_assoc();

$stmtPedido->close();


if (!$pedido) {

    header("Location: pedidos.php");
    exit();
}


/* =========================================
   PRODUCTOS DEL PEDIDO
========================================= */

$sqlDetalles = "
    SELECT
        dp.id_detalle_pedido,
        dp.id_producto,
        dp.cantidad,
        dp.precio_unitario,
        dp.descuento,
        dp.subtotal,

        pr.nombre AS producto,
        pr.codigo_producto,

        (
            SELECT ip.ruta_imagen

            FROM imagenes_producto ip

            WHERE ip.id_producto = pr.id_producto
              AND ip.estado = 1

            ORDER BY
                ip.principal DESC,
                ip.orden ASC

            LIMIT 1
        ) AS imagen

    FROM detalle_pedido dp

    INNER JOIN productos pr
        ON dp.id_producto = pr.id_producto

    WHERE dp.id_pedido = ?

    ORDER BY dp.id_detalle_pedido ASC
";


$stmtDetalles =
    $conexion->prepare($sqlDetalles);


if (!$stmtDetalles) {

    die("No fue posible consultar los productos del pedido.");
}


$stmtDetalles->bind_param(
    "i",
    $idPedido
);

$stmtDetalles->execute();

$resultadoDetalles =
    $stmtDetalles->get_result();


/* =========================================
   HISTORIAL DEL PEDIDO
========================================= */

$sqlHistorial = "
    SELECT
        h.id_historial,
        h.ubicacion,
        h.descripcion,
        h.fecha,

        ep.nombre AS estado

    FROM historial_estado_pedido h

    INNER JOIN estado_pedido ep
        ON h.id_estado = ep.id_estado

    WHERE h.id_pedido = ?

    ORDER BY h.fecha ASC,
             h.id_historial ASC
";


$stmtHistorial =
    $conexion->prepare($sqlHistorial);


if (!$stmtHistorial) {

    die("No fue posible consultar el historial del pedido.");
}


$stmtHistorial->bind_param(
    "i",
    $idPedido
);

$stmtHistorial->execute();

$resultadoHistorial =
    $stmtHistorial->get_result();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Pedido #<?= $idPedido ?> | AGRANDA
    </title>

    <link
        rel="stylesheet"
        href="pedidos.css"
    >

</head>

<body>

<div class="contenedor-dashboard">


    <!-- =====================================
         MENÚ LATERAL
    ====================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Pedidos</p>

        </div>


        <nav>

            <ul>

                <li>
                    <a href="../dashboard/dashboard.php">
                        📊 Dashboard
                    </a>
                </li>

                <li>
                    <a href="../productos/productos.php">
                        📦 Productos
                    </a>
                </li>

                <li>
                    <a href="../categorias/categorias.php">
                        🗂️ Categorías
                    </a>
                </li>

                <li>
                    <a href="../marcas/marcas.php">
                        🏷️ Marcas
                    </a>
                </li>

                <li>
                    <a href="../proveedores/proveedores.php">
                        🚚 Proveedores
                    </a>
                </li>

                <li>
                    <a href="../inventario/inventario.php">
                        📁 Inventario
                    </a>
                </li>

                <li>
                    <a
                        href="pedidos.php"
                        class="activo"
                    >
                        🛒 Pedidos
                    </a>
                </li>

                <li>
                    <a href="../clientes/clientes.php">
                        👥 Clientes
                    </a>
                </li>

                <li>
                    <a href="../contactos/contactos.php">
                        ✉️ Contactos
                    </a>
                </li>

                <li>
                    <a href="../promociones/promociones.php">
                        🎁 Promociones
                    </a>
                </li>

                <li>
                    <a href="../configuracion/configuracion.php">
                        ⚙️ Configuración
                    </a>
                </li>

            </ul>

        </nav>

    </aside>


    <!-- =====================================
         CONTENIDO
    ====================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?= htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>!
                </h2>

                <p>
                    Panel de Administración AGRANDA
                </p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span>

                    <br>

                    <span id="hora"></span>

                </div>


                <div class="usuario">

                    👤
                    <?= htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>


                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir"
                >
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- =================================
             DETALLE
        ================================== -->

        <section class="resumen">


            <div class="detalle-pedido-cabecera">

                <div>

                    <a
                        href="pedidos.php"
                        class="btn-volver"
                    >
                        ← Volver a pedidos
                    </a>


                    <h2>
                        Pedido #<?= $idPedido ?>
                    </h2>


                    <p>
                        Realizado el
                        <?= date(
                            "d/m/Y H:i",
                            strtotime(
                                $pedido["fecha_pedido"]
                            )
                        ) ?>
                    </p>

                </div>


                <span
                    class="estado estado-<?=
                        (int) $pedido["id_estado"]
                    ?>"
                >

                    <?= htmlspecialchars(
                        $pedido["estado"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </span>

            </div>


            <!-- =================================
                 INFORMACIÓN GENERAL
            ================================== -->

            <div class="detalle-grid">


                <div class="detalle-tarjeta">

                    <h3>Cliente</h3>

                    <p>
                        <strong>
                            <?= htmlspecialchars(
                                $pedido["nombre"] .
                                " " .
                                $pedido["apellido"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        <?= htmlspecialchars(
                            $pedido["correo"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <p>
                        <?= htmlspecialchars(
                            $pedido["telefono"] ?? "Sin teléfono",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                </div>


                <div class="detalle-tarjeta">

                    <h3>Entrega</h3>

                    <p>
                        <strong>
                            <?= htmlspecialchars(
                                $pedido["receptor"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        <?= htmlspecialchars(
                            $pedido["direccion"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>


                    <?php if (!empty($pedido["barrio"])): ?>

                        <p>
                            Barrio:
                            <?= htmlspecialchars(
                                $pedido["barrio"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                    <?php endif; ?>


                    <p>

                        <?= htmlspecialchars(
                            $pedido["municipio"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $pedido["departamento"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </p>


                    <p>
                        Tel:
                        <?= htmlspecialchars(
                            $pedido["telefono_receptor"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>


                    <?php if (!empty($pedido["referencia"])): ?>

                        <p>
                            Referencia:
                            <?= htmlspecialchars(
                                $pedido["referencia"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                    <?php endif; ?>

                </div>


                <div class="detalle-tarjeta">

                    <h3>Pago</h3>

                    <p>
                        Método:
                        <strong>
                            <?= htmlspecialchars(
                                $pedido["metodo_pago"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        Total:
                        <strong>
                            $<?= number_format(
                                $pedido["total"],
                                0,
                                ",",
                                "."
                            ) ?>
                        </strong>
                    </p>

                </div>

            </div>


            <!-- =================================
                 PRODUCTOS
            ================================== -->

            <div class="detalle-seccion">

                <h3>
                    Productos del pedido
                </h3>


                <div class="tabla-contenedor">

                    <table class="tabla-pedidos">

                        <thead>

                            <tr>

                                <th>Producto</th>

                                <th>Código</th>

                                <th>Cantidad</th>

                                <th>Precio</th>

                                <th>Descuento</th>

                                <th>Subtotal</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php
                        while (
                            $detalle =
                            $resultadoDetalles->fetch_assoc()
                        ):
                        ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $detalle["producto"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $detalle["codigo_producto"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <td>

                                    <?= (int) $detalle["cantidad"] ?>

                                </td>


                                <td>

                                    $<?= number_format(
                                        $detalle["precio_unitario"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <td>

                                    $<?= number_format(
                                        $detalle["descuento"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <td>

                                    <strong>

                                        $<?= number_format(
                                            $detalle["subtotal"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    </strong>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =================================
                 HISTORIAL
            ================================== -->

            <div class="detalle-seccion">

                <h3>
                    Historial del pedido
                </h3>


                <?php
                if (
                    $resultadoHistorial->num_rows > 0
                ):
                ?>

                    <div class="historial-pedido">

                        <?php
                        while (
                            $historial =
                            $resultadoHistorial->fetch_assoc()
                        ):
                        ?>

                            <div class="historial-item">

                                <strong>

                                    <?= htmlspecialchars(
                                        $historial["estado"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </strong>


                                <span>

                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $historial["fecha"]
                                        )
                                    ) ?>

                                </span>


                                <?php
                                if (
                                    !empty(
                                        $historial["descripcion"]
                                    )
                                ):
                                ?>

                                    <p>

                                        <?= htmlspecialchars(
                                            $historial["descripcion"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </p>

                                <?php endif; ?>


                                <?php
                                if (
                                    !empty(
                                        $historial["ubicacion"]
                                    )
                                ):
                                ?>

                                    <small>

                                        Ubicación:
                                        <?= htmlspecialchars(
                                            $historial["ubicacion"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </div>

                        <?php endwhile; ?>

                    </div>


                <?php else: ?>

                    <p>
                        Este pedido todavía no tiene
                        historial registrado.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>


<script src="../dashboard/dashboard.js"></script>

</body>

</html>

<?php

$stmtDetalles->close();
$stmtHistorial->close();

?>