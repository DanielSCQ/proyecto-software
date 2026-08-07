<?php

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Verificar sesión

if (!isset($_SESSION["id_usuario"])) {

    header("Location: login.php");
    exit();

}

require_once("../config/conexion.php");

// Verificar que llegue el ID del producto

if (!isset($_GET["id"])) {

    header("Location: inventario.php");
    exit();

}

$id_producto = $_GET["id"];

// Obtener información del producto

$sqlProducto = "SELECT

                    p.nombre,
                    p.codigo_producto,
                    c.nombre AS categoria,
                    m.nombre AS marca

                FROM productos p

                INNER JOIN categorias c
                ON p.id_categoria = c.id_categoria

                LEFT JOIN marcas m
                ON p.id_marca = m.id_marca

                WHERE p.id_producto = ?";

$stmtProducto = $conexion->prepare($sqlProducto);

$stmtProducto->bind_param("i", $id_producto);

$stmtProducto->execute();

$resultadoProducto = $stmtProducto->get_result();

$producto = $resultadoProducto->fetch_assoc();

// Obtener historial del producto

$sqlHistorial = "SELECT

                    mi.*,
                    CONCAT(u.nombre,' ',u.apellido) AS usuario

                FROM movimientos_inventario mi

                INNER JOIN usuarios u
                ON mi.id_usuario = u.id_usuario

                WHERE mi.id_producto = ?

                ORDER BY mi.fecha DESC";

$stmtHistorial = $conexion->prepare($sqlHistorial);

$stmtHistorial->bind_param("i", $id_producto);

$stmtHistorial->execute();

$resultadoHistorial = $stmtHistorial->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Producto | AGRANDA</title>
    <link rel="stylesheet" href="inventario.css">
</head>
<body>

    <div class="contenedor-dashboard">

    <!-- MENÚ LATERAL -->
        <aside class="menu-lateral">

            <div class="logo-panel">
                    <h1>AGRANDA</h1>
                    <p>Inventario</p>
            </div>

                <nav>

                    <ul>

                        <li><a href="dashboard.php">📊 Dashboard</a></li>

                        <li><a href="productos.php">📦 Productos</a></li>

                        <li><a href="categorias.php">🗂️ Categorías</a></li>

                        <li><a href="marcas.php">🏷️ Marcas</a></li>

                        <li><a href="proveedores.php">🚚 Proveedores</a></li>

                        <li><a href="inventario.php">📁 Inventario</a></li>

                        <li><a href="pedidos.php">🛒 Pedidos</a></li>

                        <li><a href="clientes.php">👥 Clientes</a></li>

                        <li><a href="contactos.php">✉️ Contactos</a></li>

                        <li><a href="promociones.php">🎁 Promociones</a></li>

                        <li><a href="configuracion.php">⚙️ Configuración</a></li>

                        <li><a href="cerrar_sesion.php">🚪 Cerrar sesión</a></li>

                    </ul>

                </nav>

            </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="contenido">

            <header class="encabezado">

                <div class="titulo-panel">

                    <h2>
                        ¡Bienvenido, <?php echo $_SESSION["nombre"]; ?>!
                    </h2>

                    <p>
                        Panel de Administración AGRANDA
                    </p>

                </div>

                <div class="panel-usuario">


                    <div class="fecha-hora">

                        <span id="fecha"></span><br>

                        <span id="hora"></span>

                    </div>

                    <div class="usuario">👤 <?php echo $_SESSION["nombre"]; ?></div>

                    <a href="cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

                </div>
            </header>

        <div class="principal">

            <section class="resumen">

                <h2>📜 Historial del Producto</h2>

                <p>Movimientos realizados sobre este producto.</p>

                <div class="informacion-producto">

                    <h3>
                        <?php echo $producto["nombre"]; ?>
                    </h3>

                    <p>
                        Código:
                        <?php echo $producto["codigo_producto"]; ?>
                    </p>

                    <p>
                        Categoría:
                        <?php echo $producto["categoria"]; ?>
                    </p>

                    <p>
                        Marca:
                        <?php echo $producto["marca"] ?? "Sin marca"; ?>
                    </p>

                </div>

            <table class="tabla-productos">

                <thead>

                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Motivo</th>
                    <th>Observación</th>
                    <th>Usuario</th>
                </tr>

                </thead>

                <tbody>

                <?php while($movimiento = $resultadoHistorial->fetch_assoc()){ ?>

                <tr>

                    <td>
                        <?php echo $movimiento["fecha"]; ?>
                    </td>

                    <td>
                        <?php echo $movimiento["tipo"]; ?>
                    </td>

                    <td>
                        <?php echo $movimiento["cantidad"]; ?>
                    </td>

                    <td>
                        <?php echo $movimiento["motivo"]; ?>
                    </td>

                    <td>
                        <?php echo $movimiento["observacion"] ?? "Sin observación"; ?>
                    </td>

                    <td>
                        <?php echo $movimiento["usuario"]; ?>
                    </td>

                </tr>

                    <?php } ?>

                </tbody>

                </table>

                <br><br>

                <a href="inventario.php" class="btn-cancelar">⬅️ Volver al Inventario</a>

            </section>

        </div>
    </main>
    </div>

<script src="dashboard.js"></script>
</body>
</html>