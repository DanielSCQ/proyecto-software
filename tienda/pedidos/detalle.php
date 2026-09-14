<?php

// ==========================================
// SESIÓN
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// URL BASE
// ==========================================

$scriptDir =
    str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"])
    );

$posTienda =
    strpos(
        $scriptDir,
        "/tienda"
    );

if ($posTienda !== false) {

    $base_url =
        substr(
            $scriptDir,
            0,
            $posTienda + strlen("/tienda")
        ) . "/";

} else {

    $base_url = "/tienda/";
}


// ==========================================
// PROTEGER PÁGINA
// ==========================================

$clienteLogueado =
    isset($_SESSION["id_usuario"]) &&
    is_numeric($_SESSION["id_usuario"]) &&
    ($_SESSION["rol"] ?? "") === "cliente";

if (!$clienteLogueado) {

    header(
        "Location: " .
        $base_url .
        "cuenta/login.php"
    );

    exit;
}


// ==========================================
// BASE DE DATOS
// ==========================================

require_once __DIR__ . "/../../config/conexion.php";


// ==========================================
// ID DEL CLIENTE
// ==========================================

$idUsuario =
    (int) $_SESSION["id_usuario"];


// ==========================================
// VALIDAR ID DEL PEDIDO
// ==========================================

$idPedido =
    filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );

if (
    !$idPedido ||
    $idPedido <= 0
) {

    header(
        "Location: " .
        $base_url .
        "cuenta/"
    );

    exit;
}


// ==========================================
// CONSULTAR PEDIDO
// ==========================================

$sqlPedido = "
    SELECT
        p.id_pedido,
        p.total,
        p.metodo_pago,
        p.fecha_pedido,
        p.fecha_actualizacion,

        ep.id_estado,
        ep.nombre AS estado,
        ep.descripcion AS descripcion_estado,

        dp.nombre AS nombre_direccion,
        dp.receptor,
        dp.telefono AS telefono_receptor,
        dp.direccion,
        dp.barrio,
        dp.municipio,
        dp.departamento,
        dp.referencia

    FROM pedidos p

    INNER JOIN estado_pedido ep
        ON ep.id_estado = p.id_estado

    INNER JOIN direccion_pedido dp
        ON dp.id_pedido = p.id_pedido

    WHERE p.id_pedido = ?
      AND p.id_usuario = ?

    LIMIT 1
";

$stmtPedido =
    $conexion->prepare(
        $sqlPedido
    );

if (!$stmtPedido) {

    http_response_code(500);

    exit(
        "No fue posible consultar el pedido."
    );
}


$stmtPedido->bind_param(
    "ii",
    $idPedido,
    $idUsuario
);

$stmtPedido->execute();

$resultadoPedido =
    $stmtPedido->get_result();

$pedido =
    $resultadoPedido->fetch_assoc();

$stmtPedido->close();


// ==========================================
// PEDIDO NO ENCONTRADO O NO PERTENECE
// AL CLIENTE
// ==========================================

if (!$pedido) {

    http_response_code(404);

    exit(
        "El pedido solicitado no existe o no está disponible."
    );
}


// ==========================================
// PRODUCTOS DEL PEDIDO
// ==========================================

$productos = [];

$sqlProductos = "
    SELECT
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
        ON pr.id_producto = dp.id_producto

    WHERE dp.id_pedido = ?

    ORDER BY dp.id_detalle_pedido ASC
";


$stmtProductos =
    $conexion->prepare(
        $sqlProductos
    );

if (!$stmtProductos) {

    http_response_code(500);

    exit(
        "No fue posible consultar los productos del pedido."
    );
}


$stmtProductos->bind_param(
    "i",
    $idPedido
);

$stmtProductos->execute();

$resultadoProductos =
    $stmtProductos->get_result();


while (
    $producto =
        $resultadoProductos->fetch_assoc()
) {

    $productos[] =
        $producto;
}


$stmtProductos->close();


// ==========================================
// HISTORIAL DEL PEDIDO
// ==========================================

$historialPedido = [];

$sqlHistorial = "
    SELECT
        h.id_historial,
        h.ubicacion,
        h.descripcion,
        h.fecha,

        ep.id_estado,
        ep.nombre AS estado

    FROM historial_estado_pedido h

    INNER JOIN estado_pedido ep
        ON ep.id_estado = h.id_estado

    WHERE h.id_pedido = ?

    ORDER BY
        h.fecha ASC,
        h.id_historial ASC
";


$stmtHistorial =
    $conexion->prepare(
        $sqlHistorial
    );

if (!$stmtHistorial) {

    http_response_code(500);

    exit(
        "No fue posible consultar el historial del pedido."
    );
}


$stmtHistorial->bind_param(
    "i",
    $idPedido
);

$stmtHistorial->execute();

$resultadoHistorial =
    $stmtHistorial->get_result();


while (
    $historial =
        $resultadoHistorial->fetch_assoc()
) {

    $historialPedido[] =
        $historial;
}


$stmtHistorial->close();


// ==========================================
// FECHAS
// ==========================================

$fechaPedido = "No disponible";

if (!empty($pedido["fecha_pedido"])) {

    $timestamp =
        strtotime(
            $pedido["fecha_pedido"]
        );

    if ($timestamp !== false) {

        $fechaPedido =
            date(
                "d/m/Y H:i",
                $timestamp
            );
    }
}


// ==========================================
// HEADER
// ==========================================

require_once __DIR__ . "/../includes/header.php";

?>

<main class="pedido-detalle-page">

    <section class="pedido-detalle-container">


        <!-- =================================
             REGRESAR
        ================================== -->

        <a
            href="<?= htmlspecialchars(
                $base_url . "cuenta/?vista=pedidos",
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="pedido-detalle-volver"
        >
            ← Volver a Mi cuenta
        </a>


        <!-- =================================
             ENCABEZADO
        ================================== -->

        <div class="pedido-detalle-encabezado">

            <div>

                <span class="pedido-detalle-etiqueta">
                    SEGUIMIENTO DE COMPRA
                </span>

                <h1>
                    Pedido #<?= (int) $pedido["id_pedido"] ?>
                </h1>

                <p>
                    Realizado el
                    <?= htmlspecialchars(
                        $fechaPedido,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

            </div>


            <span
                class="
                    pedido-detalle-estado
                    pedido-estado-<?=
                        (int) $pedido["id_estado"]
                    ?>
                "
            >

                <?= htmlspecialchars(
                    $pedido["estado"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </span>

        </div>


        <!-- =================================
             PROGRESO DEL PEDIDO
        ================================== -->

        <?php if ((int) $pedido["id_estado"] === 5): ?>

            <div class="pedido-cancelado-aviso">

                <strong>
                    Pedido cancelado
                </strong>

                <p>
                    Este pedido fue marcado como cancelado.
                    Consulta el historial para conocer
                    la información registrada.
                </p>

            </div>

        <?php else: ?>

            <div class="pedido-progreso">

                <?php

                $estadoActual =
                    (int) $pedido["id_estado"];

                $pasos = [
                    1 => "Pendiente",
                    2 => "En proceso",
                    3 => "Enviado",
                    4 => "Entregado"
                ];

                ?>

                <?php foreach ($pasos as $idEstado => $nombreEstado): ?>

                    <div
                        class="
                            pedido-progreso-paso

                            <?=
                                $estadoActual >= $idEstado
                                    ? "pedido-progreso-completado"
                                    : ""
                            ?>

                            <?=
                                $estadoActual === $idEstado
                                    ? "pedido-progreso-actual"
                                    : ""
                            ?>
                        "
                    >

                        <span class="pedido-progreso-punto">

                            <?= $idEstado ?>

                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $nombreEstado,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- =================================
             INFORMACIÓN DEL PEDIDO
        ================================== -->

        <div class="pedido-detalle-grid">


            <!-- ENTREGA -->

            <article class="pedido-detalle-card">

                <span class="pedido-detalle-card-etiqueta">
                    ENTREGA
                </span>

                <h2>
                    Dirección de entrega
                </h2>

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
                    ) ?>,
                    <?= htmlspecialchars(
                        $pedido["departamento"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <?php if (!empty($pedido["telefono_receptor"])): ?>

                    <p>
                        Teléfono:
                        <?= htmlspecialchars(
                            $pedido["telefono_receptor"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                <?php endif; ?>

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

            </article>


            <!-- PAGO -->

            <article class="pedido-detalle-card">

                <span class="pedido-detalle-card-etiqueta">
                    PAGO
                </span>

                <h2>
                    Resumen
                </h2>

                <div class="pedido-resumen-linea">

                    <span>
                        Método
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $pedido["metodo_pago"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <div class="pedido-resumen-linea">

                    <span>
                        Estado
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $pedido["estado"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <div class="pedido-resumen-linea pedido-resumen-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        $<?= number_format(
                            (float) $pedido["total"],
                            0,
                            ",",
                            "."
                        ) ?>
                    </strong>

                </div>

            </article>

        </div>


        <!-- =================================
             PRODUCTOS
        ================================== -->

        <section class="pedido-detalle-seccion">

            <div class="pedido-seccion-encabezado">

                <span>
                    PRODUCTOS
                </span>

                <h2>
                    Productos del pedido
                </h2>

            </div>


            <?php if (empty($productos)): ?>

                <div class="pedido-detalle-vacio">

                    No hay productos registrados
                    para este pedido.

                </div>

            <?php else: ?>

                <div class="pedido-productos-lista">


                    <?php foreach ($productos as $producto): ?>

                        <article class="pedido-producto-card">


                            <div class="pedido-producto-imagen">

                                <?php if (!empty($producto["imagen"])): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $base_url .
                                            "../" .
                                            $producto["imagen"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $producto["producto"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <span>
                                        Sin imagen
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="pedido-producto-info">

                                <span class="pedido-producto-codigo">

                                    <?= htmlspecialchars(
                                        $producto["codigo_producto"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>

                                <h3>

                                    <?= htmlspecialchars(
                                        $producto["producto"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </h3>


                                <div class="pedido-producto-datos">

                                    <span>
                                        Cantidad:
                                        <strong>
                                            <?= (int) $producto["cantidad"] ?>
                                        </strong>
                                    </span>

                                    <span>
                                        Precio:
                                        <strong>
                                            $<?= number_format(
                                                (float) $producto["precio_unitario"],
                                                0,
                                                ",",
                                                "."
                                            ) ?>
                                        </strong>
                                    </span>

                                </div>

                            </div>


                            <div class="pedido-producto-subtotal">

                                <span>
                                    Subtotal
                                </span>

                                <strong>
                                    $<?= number_format(
                                        (float) $producto["subtotal"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>

        </section>


        <!-- =================================
             HISTORIAL
        ================================== -->

        <section class="pedido-detalle-seccion">

            <div class="pedido-seccion-encabezado">

                <span>
                    SEGUIMIENTO
                </span>

                <h2>
                    Historial del pedido
                </h2>

                <p>
                    Aquí puedes consultar los cambios
                    registrados durante el proceso de tu compra.
                </p>

            </div>


            <?php if (empty($historialPedido)): ?>

                <div class="pedido-detalle-vacio">

                    Este pedido todavía no tiene
                    movimientos de seguimiento.

                </div>

            <?php else: ?>

                <div class="pedido-historial">


                    <?php foreach ($historialPedido as $historial): ?>

                        <article class="pedido-historial-item">

                            <div
                                class="
                                    pedido-historial-punto
                                    pedido-estado-fondo-<?=
                                        (int) $historial["id_estado"]
                                    ?>
                                "
                            ></div>


                            <div class="pedido-historial-contenido">

                                <div class="pedido-historial-superior">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $historial["estado"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>

                                    <time>

                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $historial["fecha"]
                                            )
                                        ) ?>

                                    </time>

                                </div>


                                <?php if (!empty($historial["descripcion"])): ?>

                                    <p>
                                        <?= htmlspecialchars(
                                            $historial["descripcion"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($historial["ubicacion"])): ?>

                                    <span class="pedido-historial-ubicacion">

                                        Ubicación:
                                        <strong>
                                            <?= htmlspecialchars(
                                                $historial["ubicacion"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </strong>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>

        </section>


    </section>

</main>


<?php

require_once __DIR__ . "/../includes/footer.php";

?>