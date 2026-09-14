<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// OBTENER ID DEL CLIENTE
// =================================

$id_cliente = intval($_GET["id"] ?? 0);

if ($id_cliente <= 0) {
    header("Location: clientes.php");
    exit();
}


// =================================
// CONSULTAR INFORMACIÓN DEL CLIENTE
// =================================

$sql_cliente = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.telefono,
                    u.estado,
                    u.fecha_registro,

                    COUNT(DISTINCT p.id_pedido) AS cantidad_pedidos,

                    COALESCE(SUM(p.total), 0) AS total_comprado

                FROM usuarios u

                LEFT JOIN pedidos p
                ON u.id_usuario = p.id_usuario

                WHERE u.id_usuario = ?
                AND u.rol = 'cliente'

                GROUP BY
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.telefono,
                    u.estado,
                    u.fecha_registro";


$stmt_cliente = $conexion->prepare($sql_cliente);

if (!$stmt_cliente) {
    die("No fue posible consultar la información del cliente.");
}

$stmt_cliente->bind_param("i", $id_cliente);

$stmt_cliente->execute();

$resultado_cliente = $stmt_cliente->get_result();


// =================================
// VERIFICAR SI EXISTE EL CLIENTE
// =================================

if ($resultado_cliente->num_rows === 0) {

    $stmt_cliente->close();

    header("Location: clientes.php");

    exit();
}

$cliente = $resultado_cliente->fetch_assoc();

$stmt_cliente->close();


// =================================
// CONSULTAR DIRECCIONES
// =================================

$sql_direcciones = "SELECT
                        id_direccion,
                        nombre,
                        telefono,
                        receptor,
                        direccion,
                        barrio,
                        municipio,
                        departamento,
                        referencia,
                        principal,
                        estado

                    FROM direcciones

                    WHERE id_usuario = ?

                    ORDER BY principal DESC, id_direccion DESC";


$stmt_direcciones = $conexion->prepare($sql_direcciones);

if (!$stmt_direcciones) {
    die("No fue posible consultar las direcciones del cliente.");
}

$stmt_direcciones->bind_param("i", $id_cliente);

$stmt_direcciones->execute();

$resultado_direcciones = $stmt_direcciones->get_result();


// =================================
// CONSULTAR HISTORIAL DE PEDIDOS
// =================================

$sql_pedidos = "SELECT
                    p.id_pedido,
                    p.total,
                    p.metodo_pago,
                    p.fecha_pedido,
                    ep.nombre AS estado_pedido

                FROM pedidos p

                INNER JOIN estado_pedido ep
                ON p.id_estado = ep.id_estado

                WHERE p.id_usuario = ?

                ORDER BY
                    p.fecha_pedido DESC,
                    p.id_pedido DESC";


$stmt_pedidos = $conexion->prepare($sql_pedidos);

if (!$stmt_pedidos) {
    die("No fue posible consultar los pedidos del cliente.");
}

$stmt_pedidos->bind_param("i", $id_cliente);

$stmt_pedidos->execute();

$resultado_pedidos = $stmt_pedidos->get_result();


// =================================
// CALCULAR TIEMPO COMO CLIENTE
// =================================

$fecha_registro = new DateTime($cliente["fecha_registro"]);

$fecha_actual = new DateTime();

$diferencia = $fecha_registro->diff($fecha_actual);

$tiempo_cliente = "";

if ($diferencia->y > 0) {

    $tiempo_cliente = $diferencia->y . " año";

    if ($diferencia->y > 1) {
        $tiempo_cliente .= "s";
    }

} elseif ($diferencia->m > 0) {

    $tiempo_cliente = $diferencia->m . " mes";

    if ($diferencia->m > 1) {
        $tiempo_cliente .= "es";
    }

} else {

    $tiempo_cliente = $diferencia->d . " día";

    if ($diferencia->d != 1) {
        $tiempo_cliente .= "s";
    }
}


// =================================
// MENSAJE DE ACTUALIZACIÓN
// =================================

$actualizado = isset($_GET["actualizado"])
    && $_GET["actualizado"] === "1";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ver cliente | AGRANDA</title>

    <link rel="stylesheet" href="clientes.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Clientes</p>

        </div>


        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="clientes.php" class="activo">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- =================================
         CONTENIDO
    ================================== -->

    <main class="contenido">


        <!-- =================================
             ENCABEZADO
        ================================== -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php echo htmlspecialchars(
                        $_SESSION["nombre"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>!
                </h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>


                <div class="usuario">

                    👤<?php echo htmlspecialchars(
                        $_SESSION["nombre"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- =================================
             INFORMACIÓN DEL CLIENTE
        ================================== -->

        <section class="contenido-clientes">


            <!-- =================================
                 CABECERA DEL CLIENTE
            ================================== -->

            <div class="encabezado-detalle">

                <div>

                    <h2>
                        👤
                        <?php
                        echo htmlspecialchars(
                            $cliente["nombre"] . " " . $cliente["apellido"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </h2>

                    <p>Información detallada del cliente</p>

                </div>


                <div class="acciones-detalle">

                    <a href="clientes.php"
                        class="btn-volver">
                        ← Volver
                    </a>


                    <a href="editar_cliente.php?id=<?php echo (int)$cliente["id_usuario"]; ?>"
                        class="btn-editar">
                        ✏️ Editar
                    </a>

                </div>

            </div>


            <!-- =================================
                 MENSAJE DE ACTUALIZACIÓN
            ================================== -->

            <?php if ($actualizado): ?>

                <div class="mensaje-exito">

                    Los datos del cliente fueron actualizados correctamente.

                </div>

            <?php endif; ?>


            <!-- =================================
                 INFORMACIÓN PERSONAL
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>📋 Información personal</h3>


                <div class="datos-cliente">


                    <div class="dato">

                        <span class="dato-titulo">
                            Nombre completo
                        </span>

                        <span class="dato-valor">

                            <?php
                            echo htmlspecialchars(
                                $cliente["nombre"] . " " . $cliente["apellido"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-titulo">
                            Correo electrónico
                        </span>

                        <span class="dato-valor">

                            <?php
                            echo htmlspecialchars(
                                $cliente["correo"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-titulo">
                            Teléfono
                        </span>

                        <span class="dato-valor">

                            <?php

                            echo !empty($cliente["telefono"])
                                ? htmlspecialchars(
                                    $cliente["telefono"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                : "No registrado";

                            ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-titulo">
                            Estado
                        </span>

                        <span class="dato-valor">


                            <?php if ((int)$cliente["estado"] === 1): ?>

                                <span class="estado-cliente estado-activo">
                                    Activo
                                </span>

                            <?php else: ?>

                                <span class="estado-cliente estado-inactivo">
                                    Inactivo
                                </span>

                            <?php endif; ?>


                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-titulo">
                            Fecha de registro
                        </span>

                        <span class="dato-valor">

                            <?php

                            echo date(
                                "d/m/Y",
                                strtotime($cliente["fecha_registro"])
                            );

                            ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-titulo">
                            Tiempo como cliente
                        </span>

                        <span class="dato-valor">

                            <?php
                            echo htmlspecialchars(
                                $tiempo_cliente,
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </span>

                    </div>


                </div>

            </div>


            <!-- =================================
                 RESUMEN
            ================================== -->

            <div class="tarjetas-estadisticas">


                <div class="estadistica">

                    <span class="estadistica-icono">
                        🛒
                    </span>

                    <div>

                        <span class="estadistica-titulo">
                            Pedidos realizados
                        </span>

                        <strong>
                            <?php echo (int)$cliente["cantidad_pedidos"]; ?>
                        </strong>

                    </div>

                </div>


                <div class="estadistica">

                    <span class="estadistica-icono">
                        💰
                    </span>

                    <div>

                        <span class="estadistica-titulo">
                            Total comprado
                        </span>

                        <strong>

                            $
                            <?php
                            echo number_format(
                                (float)$cliente["total_comprado"],
                                0,
                                ",",
                                "."
                            );
                            ?>

                            COP

                        </strong>

                    </div>

                </div>


                <div class="estadistica">

                    <span class="estadistica-icono">
                        📍
                    </span>

                    <div>

                        <span class="estadistica-titulo">
                            Direcciones
                        </span>

                        <strong>

                            <?php
                            echo (int)$resultado_direcciones->num_rows;
                            ?>

                        </strong>

                    </div>

                </div>


            </div>


            <!-- =================================
                 DIRECCIONES
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>📍 Direcciones de envío</h3>


                <?php if ($resultado_direcciones->num_rows > 0): ?>


                    <div class="lista-direcciones">


                        <?php while ($direccion = $resultado_direcciones->fetch_assoc()): ?>


                            <div class="direccion">


                                <div class="direccion-cabecera">

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $direccion["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </strong>


                                    <?php if ((int)$direccion["principal"] === 1): ?>

                                        <span class="direccion-principal">
                                            Principal
                                        </span>

                                    <?php endif; ?>


                                    <?php if ((int)$direccion["estado"] === 0): ?>

                                        <span class="estado-cliente estado-inactivo">
                                            Inactiva
                                        </span>

                                    <?php endif; ?>


                                </div>


                                <p>

                                    <strong>Receptor:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $direccion["receptor"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>Teléfono:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $direccion["telefono"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>Dirección:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $direccion["direccion"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </p>


                                <?php if (!empty($direccion["barrio"])): ?>

                                    <p>

                                        <strong>Barrio:</strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $direccion["barrio"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>


                                <p>

                                    <strong>Ubicación:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $direccion["municipio"]
                                        . ", "
                                        . $direccion["departamento"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </p>


                                <?php if (!empty($direccion["referencia"])): ?>

                                    <p>

                                        <strong>Referencia:</strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $direccion["referencia"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        <?php endwhile; ?>

                    </div>

                <?php else: ?>

                    <p class="sin-datos">
                        Este cliente todavía no tiene direcciones registradas.
                    </p>

                <?php endif; ?>


            </div>


            <!-- =================================
                 HISTORIAL DE PEDIDOS
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>🛒 Historial de pedidos</h3>


                <?php if ($resultado_pedidos->num_rows > 0): ?>


                    <div class="contenedor-tabla">

                        <table class="tabla-clientes">

                            <thead>

                                <tr>

                                    <th>Pedido</th>

                                    <th>Fecha</th>

                                    <th>Total</th>

                                    <th>Método de pago</th>

                                    <th>Estado</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($pedido = $resultado_pedidos->fetch_assoc()): ?>

                                    <tr>

                                        <td>

                                            #<?php
                                            echo (int)$pedido["id_pedido"];
                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo date(
                                                "d/m/Y H:i",
                                                strtotime(
                                                    $pedido["fecha_pedido"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            $

                                            <?php

                                            echo number_format(
                                                (float)$pedido["total"],
                                                0,
                                                ",",
                                                "."
                                            );

                                            ?>

                                            COP

                                        </td>

                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $pedido["metodo_pago"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </td>

                                        <td>

                                            <span class="estado-pedido">

                                                <?php
                                                echo htmlspecialchars(
                                                    $pedido["estado_pedido"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                                ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p class="sin-datos">
                        Este cliente todavía no ha realizado pedidos.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </main>
</div>


<?php

$stmt_direcciones->close();

$stmt_pedidos->close();

?>


<script src="../dashboard/dashboard.js"></script>

</body>

</html>