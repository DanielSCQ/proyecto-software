<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

$busqueda = $_GET["busqueda"] ?? "";

$sql = "SELECT
            i.id_inventario,
            i.id_producto,
            i.stock_actual,
            i.stock_minimo,
            i.fecha_actualizacion,

            p.nombre,
            p.codigo_producto,

            c.nombre AS categoria,
            m.nombre AS marca,

            img.ruta_imagen

        FROM inventario i

        INNER JOIN productos p
        ON i.id_producto = p.id_producto

        INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria

        LEFT JOIN marcas m
        ON p.id_marca = m.id_marca

        LEFT JOIN imagenes_producto img
        ON p.id_producto = img.id_producto
        AND img.principal = 1";

if (!empty($busqueda)) {

    $sql .= " WHERE
                p.nombre LIKE ?
                OR p.codigo_producto LIKE ?
                OR c.nombre LIKE ?
                OR m.nombre LIKE ?";

}

$sql .= " ORDER BY i.id_inventario DESC";

$stmt = $conexion->prepare($sql);

if (!empty($busqueda)) {

    $buscar = "%".$busqueda."%";

    $stmt->bind_param(
        "ssss",
        $buscar,
        $buscar,
        $buscar,
        $buscar
    );

}

$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>clientes | AGRANDA</title>

    <link rel="stylesheet" href="clientes.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Clientes</p>

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

    <!-- Contenido -->
    <main class="contenido">

        <!-- Encabezado -->
        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido, <?php echo $_SESSION["nombre"]; ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>
                    <span id="hora"></span>

                </div>

                <div class="usuario">

                    👤 <?php echo $_SESSION["nombre"]; ?>

                </div>

                <a href="cerrar_sesion.php" class="btn-salir">

                    Cerrar sesión

                </a>

            </div>

        </header>

        <!-- Área principal -->
        <section class="resumen">

            <h2>🛒 Gestor de Pedidos</h2>

            <p>
                Desde aquí podrás administrar todos los pedidos realizados en AGRANDA.
            </p>

        </section>

    </main>

</div>

<script src="dashboard.js"></script>
</body>
</html>