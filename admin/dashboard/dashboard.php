<?php

session_start();


// =================================
// PROTEGER PANEL
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}


require_once("../../config/conexion.php");


// =================================
// FUNCIÓN PARA OBTENER CONTEOS
// =================================

function obtenerConteo($conexion, $sql)
{
    $resultado = $conexion->query($sql);

    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();

    return (int) ($fila["total"] ?? 0);
}


// =================================
// PRODUCTOS ACTIVOS
// =================================

$totalProductos = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE estado = 1"
);


// =================================
// PEDIDOS PENDIENTES
// =================================

$totalPedidosPendientes = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM pedidos p

     INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

     WHERE ep.nombre = 'Pendiente'"
);


// =================================
// CLIENTES ACTIVOS
// =================================

$totalClientes = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM usuarios
     WHERE rol = 'cliente'
     AND estado = 1"
);


// =================================
// PRODUCTOS CON STOCK BAJO
// =================================

$totalStockBajo = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM inventario i

     INNER JOIN productos p
        ON i.id_producto = p.id_producto

     WHERE p.estado = 1
     AND i.stock_actual <= i.stock_minimo"
);


// =================================
// PEDIDOS POR ESTADO
// =================================

$pedidosEnProceso = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM pedidos p

     INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

     WHERE ep.nombre = 'En proceso'"
);


$pedidosEnviados = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM pedidos p

     INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

     WHERE ep.nombre = 'Enviado'"
);


$pedidosEntregados = obtenerConteo(
    $conexion,
    "SELECT COUNT(*) AS total
     FROM pedidos p

     INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

     WHERE ep.nombre = 'Entregado'"
);


// =================================
// ÚLTIMOS PEDIDOS
// =================================

$ultimosPedidos = [];

$sqlPedidos = "
    SELECT
        p.id_pedido,
        p.total,
        p.metodo_pago,
        p.fecha_pedido,

        ep.nombre AS estado,

        u.nombre,
        u.apellido

    FROM pedidos p

    INNER JOIN usuarios u
        ON p.id_usuario = u.id_usuario

    INNER JOIN estado_pedido ep
        ON p.id_estado = ep.id_estado

    ORDER BY p.id_pedido DESC

    LIMIT 5
";


$resultadoPedidos = $conexion->query($sqlPedidos);


if ($resultadoPedidos) {

    while ($pedido = $resultadoPedidos->fetch_assoc()) {
        $ultimosPedidos[] = $pedido;
    }
}


// =================================
// IMAGEN CONFIGURABLE DASHBOARD
// =================================

$imagenDashboard = "";


$stmtConfiguracion = $conexion->prepare(
    "SELECT imagen_dashboard_admin
     FROM configuracion_tienda
     WHERE id_configuracion = ?
     LIMIT 1"
);


if ($stmtConfiguracion) {

    $idConfiguracion = 1;

    $stmtConfiguracion->bind_param(
        "i",
        $idConfiguracion
    );

    $stmtConfiguracion->execute();

    $resultadoConfiguracion =
        $stmtConfiguracion->get_result();


    if (
        $configuracion =
        $resultadoConfiguracion->fetch_assoc()
    ) {

        if (!empty($configuracion["imagen_dashboard_admin"])) {

            $imagenDashboard =
                "../../" .
                $configuracion["imagen_dashboard_admin"];
        }
    }


    $stmtConfiguracion->close();
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Panel de Control | AGRANDA</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>


<body>


<div class="contenedor-dashboard">


    <!-- ===============================
         MENÚ LATERAL
    ================================ -->

    <aside class="menu-lateral">


        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Panel de Control</p>

        </div>


        <nav>

            <ul>

                <li><a href="dashboard.php" class="activo">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- ===============================
         CONTENIDO
    ================================ -->

    <main class="contenido">


        <!-- ===============================
             ENCABEZADO
        ================================ -->

        <header class="encabezado">


            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"]
                    );
                    ?>!
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
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"]
                    );
                    ?>

                </div>


                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir"
                >
                    Cerrar sesión
                </a>


            </div>

        </header>


        <!-- ===============================
             CONTENIDO DASHBOARD
        ================================ -->

        <section class="contenido-dashboard">


            <!-- ===============================
                 PRESENTACIÓN
            ================================ -->

            <div class="dashboard-presentacion">


                <div class="dashboard-presentacion-texto">

                    <span class="dashboard-etiqueta">
                        RESUMEN GENERAL
                    </span>

                    <h2>
                        Estado general de AGRANDA
                    </h2>

                    <p>
                        Consulta rápidamente los productos,
                        pedidos, clientes e inventario de la tienda.
                    </p>


                    <div class="dashboard-accesos">

                        <a
                            href="../productos/productos.php"
                            class="acceso-principal"
                        >
                            Ver productos
                        </a>

                        <a
                            href="../pedidos/pedidos.php"
                            class="acceso-secundario"
                        >
                            Ver pedidos
                        </a>

                    </div>

                </div>


                <div class="dashboard-presentacion-imagen">


                    <?php if ($imagenDashboard !== ""): ?>

                        <img
                            src="<?php echo htmlspecialchars(
                                $imagenDashboard,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            alt="Imagen Dashboard AGRANDA"
                        >

                    <?php else: ?>

                        <div class="dashboard-sin-imagen">

                            <strong>AGRANDA</strong>

                            <span>
                                Administración de la tienda
                            </span>

                        </div>

                    <?php endif; ?>


                </div>


            </div>


            <!-- ===============================
                 TARJETAS PRINCIPALES
            ================================ -->

            <div class="tarjetas-dashboard">


                <a
                    href="../productos/productos.php"
                    class="tarjeta-dashboard"
                >

                    <div class="tarjeta-icono">
                        📦
                    </div>

                    <div>

                        <span class="tarjeta-titulo">
                            Productos activos
                        </span>

                        <strong>
                            <?php echo $totalProductos; ?>
                        </strong>

                        <small>
                            Productos disponibles
                        </small>

                    </div>

                </a>


                <a
                    href="../pedidos/pedidos.php"
                    class="tarjeta-dashboard"
                >

                    <div class="tarjeta-icono">
                        🛒
                    </div>

                    <div>

                        <span class="tarjeta-titulo">
                            Pedidos pendientes
                        </span>

                        <strong>
                            <?php echo $totalPedidosPendientes; ?>
                        </strong>

                        <small>
                            Requieren atención
                        </small>

                    </div>

                </a>


                <a
                    href="../clientes/clientes.php"
                    class="tarjeta-dashboard"
                >

                    <div class="tarjeta-icono">
                        👥
                    </div>

                    <div>

                        <span class="tarjeta-titulo">
                            Clientes activos
                        </span>

                        <strong>
                            <?php echo $totalClientes; ?>
                        </strong>

                        <small>
                            Usuarios registrados
                        </small>

                    </div>

                </a>


                <a
                    href="../inventario/inventario.php"
                    class="tarjeta-dashboard"
                >

                    <div class="tarjeta-icono">
                        ⚠️
                    </div>

                    <div>

                        <span class="tarjeta-titulo">
                            Stock bajo
                        </span>

                        <strong>
                            <?php echo $totalStockBajo; ?>
                        </strong>

                        <small>
                            Productos por revisar
                        </small>

                    </div>

                </a>


            </div>


            <!-- ===============================
                 SEGUNDA FILA
            ================================ -->

            <div class="dashboard-grid-secundario">


                <!-- ===============================
                     ÚLTIMOS PEDIDOS
                ================================ -->

                <div class="dashboard-bloque pedidos-recientes">


                    <div class="dashboard-bloque-encabezado">

                        <div>

                            <span class="dashboard-mini-etiqueta">
                                ACTIVIDAD
                            </span>

                            <h3>
                                Últimos pedidos
                            </h3>

                        </div>


                        <a href="../pedidos/pedidos.php">
                            Ver todos →
                        </a>

                    </div>


                    <div class="tabla-dashboard-contenedor">

                        <table class="tabla-dashboard">

                            <thead>

                                <tr>

                                    <th>Pedido</th>

                                    <th>Cliente</th>

                                    <th>Total</th>

                                    <th>Estado</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (!empty($ultimosPedidos)): ?>


                                <?php foreach ($ultimosPedidos as $pedido): ?>

                                    <tr>

                                        <td>
                                            #
                                            <?php
                                            echo (int)
                                            $pedido["id_pedido"];
                                            ?>
                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $pedido["nombre"] .
                                                " " .
                                                $pedido["apellido"]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            $
                                            <?php

                                            echo number_format(
                                                (float)
                                                $pedido["total"],
                                                0,
                                                ",",
                                                "."
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <span class="estado-dashboard">

                                                <?php
                                                echo htmlspecialchars(
                                                    $pedido["estado"]
                                                );
                                                ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="4"
                                        class="dashboard-sin-datos"
                                    >
                                        No hay pedidos registrados.
                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>


                </div>


                <!-- ===============================
                     ESTADO DE PEDIDOS
                ================================ -->

                <div class="dashboard-bloque estado-pedidos">


                    <div class="dashboard-bloque-encabezado">

                        <div>

                            <span class="dashboard-mini-etiqueta">
                                PEDIDOS
                            </span>

                            <h3>
                                Estado de pedidos
                            </h3>

                        </div>

                    </div>


                    <div class="lista-estados-dashboard">


                        <div class="estado-resumen">

                            <div>

                                <span>
                                    Pendientes
                                </span>

                                <strong>
                                    <?php echo $totalPedidosPendientes; ?>
                                </strong>

                            </div>

                            <span class="estado-punto pendiente"></span>

                        </div>


                        <div class="estado-resumen">

                            <div>

                                <span>
                                    En proceso
                                </span>

                                <strong>
                                    <?php echo $pedidosEnProceso; ?>
                                </strong>

                            </div>

                            <span class="estado-punto proceso"></span>

                        </div>


                        <div class="estado-resumen">

                            <div>

                                <span>
                                    Enviados
                                </span>

                                <strong>
                                    <?php echo $pedidosEnviados; ?>
                                </strong>

                            </div>

                            <span class="estado-punto enviado"></span>

                        </div>


                        <div class="estado-resumen">

                            <div>

                                <span>
                                    Entregados
                                </span>

                                <strong>
                                    <?php echo $pedidosEntregados; ?>
                                </strong>

                            </div>

                            <span class="estado-punto entregado"></span>

                        </div>


                    </div>


                    <a
                        href="../pedidos/pedidos.php"
                        class="btn-dashboard-pedidos"
                    >
                        Gestionar pedidos
                    </a>


                </div>


            </div>


            <!-- ===============================
                 ACCESOS RÁPIDOS
            ================================ -->

            <div class="dashboard-bloque accesos-rapidos">


                <div class="dashboard-bloque-encabezado">

                    <div>

                        <span class="dashboard-mini-etiqueta">
                            ACCESOS RÁPIDOS
                        </span>

                        <h3>
                            Administración
                        </h3>

                    </div>

                </div>


                <div class="accesos-grid">


                    <a href="../productos/agregar_producto.php">

                        <span>＋</span>

                        <strong>
                            Agregar producto
                        </strong>

                    </a>


                    <a href="../inventario/inventario.php">

                        <span>📁</span>

                        <strong>
                            Revisar inventario
                        </strong>

                    </a>


                    <a href="../clientes/clientes.php">

                        <span>👥</span>

                        <strong>
                            Ver clientes
                        </strong>

                    </a>


                    <a href="../configuracion/configuracion.php">

                        <span>⚙️</span>

                        <strong>
                            Configuración
                        </strong>

                    </a>


                </div>


            </div>


        </section>


    </main>


</div>


<!-- =================================
     FECHA Y HORA
================================= -->

<script src="dashboard.js"></script>


</body>

</html>