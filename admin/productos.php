<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}
require_once("../config/conexion.php");

$sql = "SELECT
p.id_producto,
p.nombre,
p.codigo_producto,
p.precio,
p.estado,
p.destacado,
c.nombre AS categoria,
m.nombre AS marca,
i.stock_actual,
img.ruta_imagen
FROM productos p

INNER JOIN categorias c
ON p.id_categoria = c.id_categoria

LEFT JOIN marcas m
ON p.id_marca = m.id_marca

LEFT JOIN inventario i
ON p.id_producto = i.id_producto

LEFT JOIN imagenes_producto img
ON p.id_producto = img.id_producto
AND img.principal = 1

ORDER BY p.id_producto DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos| AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

    <!-- Contenedor principal -->
    <div class="contenedor-dashboard">

        <!-- Menú lateral -->
        <aside class="menu-lateral">

            <div class="logo-panel">
    
                <h1>AGRANDA</h1>

                <p>productos</p>

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

        <!-- Contenido principal -->
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

            <!-- Tarjetas -->
        <section class="tarjetas">

        </section>

            <!-- Área principal -->
        <section class="resumen">

            <h2>📦 Gestor de Productos</h2>

                <p>
                    Desde aquí podrás agregar, editar, eliminar y administrar todos los productos de AGRANDA.
                </p>

            <div class="barra-productos">

                <a href="agregar_producto.php" class="btn-nuevo">
                    ➕ Nuevo Producto
                </a>

            <input type="search" class="buscar-producto" placeholder="Buscar producto...">

            </div>

            <table class="tabla-productos">

            <thead>

                <tr>

                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Destacado</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>

                <?php while($producto = $resultado->fetch_assoc()){ ?>

            <tr>

                <td>

                    <?php if(!empty($producto["ruta_imagen"])){ ?>

                        <img
                            src="<?php echo $producto["ruta_imagen"]; ?>"
                            alt="Producto"
                            width="70">

                    <?php }else{ ?>

                        Sin imagen

                    <?php } ?>

                </td>

                <td>
                    <?php echo $producto["nombre"]; ?>
                </td>

                <td>
                    <?php echo $producto["codigo_producto"]; ?>
                </td>

                <td>
                    <?php echo $producto["categoria"]; ?>
                </td>

                <td>
                    <?php echo $producto["marca"] ?? "Sin marca"; ?>
                </td>

                <td>
                    $<?php echo number_format($producto["precio"], 0, ",", "."); ?>
                </td>

                <td>
                    <?php echo $producto["stock_actual"]; ?>
                </td>

                <td>

                    <?php if($producto["destacado"]){ ?>

                        ⭐ Sí

                    <?php }else{ ?>

                        No

                    <?php } ?>

                </td>

                <td>

                    <?php if($producto["estado"]){ ?>

                        <span class="estado-activo">
                            Activo
                        </span>

                    <?php }else{ ?>

                        <span class="estado-inactivo">
                            Inactivo
                        </span>

                    <?php } ?>

                </td>

                <td class="acciones">

                    <a href="editar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                    class="btn-editar">✏️</a>

                    <a href="eliminar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                    class="btn-eliminar"onclick="return confirm('¿Está seguro de eliminar este producto?');">🗑️</a>

                </td>           

            </tr>

                <?php } ?>

            </tbody>

            </table>

        </section>

        </main>

    </div>

    <script src="dashboard.js"></script>

</body>

</html>