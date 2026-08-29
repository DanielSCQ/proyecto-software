<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

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

    <title>Inventario | AGRANDA</title>

    <link rel="stylesheet" href="inventario.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Inventario</p>

        </div>

        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="inventario.php" class="activo">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

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

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>

        <!-- Área principal -->
        <section class="resumen">

            <h2>📁 Gestor de Inventario</h2>

            <p>
                Desde aquí podrás administrar el stock de todos los productos registrados en AGRANDA.
            </p>

            <div class="barra-productos">

                <a href="ingresos_de_inventario.php" class="btn-nuevo">
                    📥 Ingresos
                </a>

                <a href="lista_ingresos.php" class="btn-secundario">
                    ✏️ Editar ingresos
                </a>

                <a href="historial_inventario.php" class="btn-secundario">
                    📜 Historial General
                </a>

                <form method="GET">

                    <input
                        type="search"
                        name="busqueda"
                        class="buscar-producto"
                        placeholder="Buscar producto..."
                        value="<?php echo htmlspecialchars($busqueda); ?>">

                </form>

            </div>
            
            <table class="tabla-productos">

                <thead>

                    <tr>

                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                        <th>Actualización</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php while($inventario = $resultado->fetch_assoc()){ ?>

                    <?php

                    $stock_actual = $inventario["stock_actual"];
                    $stock_minimo = $inventario["stock_minimo"];

                    if($stock_actual <= 0){

                        $estado = "Agotado";
                        $clase = "stock-agotado";

                    }elseif($stock_actual <= $stock_minimo){

                        $estado = "Stock bajo";
                        $clase = "stock-bajo";

                    }else{

                        $estado = "Disponible";
                        $clase = "stock-disponible";

                    }

                    ?>

                    <tr>

                        <td>

                            <?php if(!empty($inventario["ruta_imagen"])){ ?>

                                <img
                                    src="../../<?php echo $inventario["ruta_imagen"]; ?>"
                                    alt="Producto"
                                    width="70">

                            <?php }else{ ?>

                                Sin imagen

                            <?php } ?>

                        </td>

                        <td>
                            <?php echo $inventario["nombre"]; ?>
                        </td>

                        <td>
                            <?php echo $inventario["codigo_producto"]; ?>
                        </td>

                        <td>
                            <?php echo $inventario["categoria"]; ?>
                        </td>

                        <td>
                            <?php echo $inventario["marca"] ?? "Sin marca"; ?>
                        </td>

                        <td>
                            <?php echo $inventario["stock_actual"]; ?>
                        </td>

                        <td>
                            <?php echo $inventario["stock_minimo"]; ?>
                        </td>

                        <td>

                            <span class="<?php echo $clase; ?>">
                                <?php echo $estado; ?>
                            </span>

                        </td>

                        <td>
                            <?php echo $inventario["fecha_actualizacion"]; ?>
                        </td>

                        <td class="acciones">

                            <a href="editar_inventario.php?id=<?php echo $inventario["id_inventario"]; ?>"
                            class="btn-editar">✏️</a>

                            <a href="historial_producto.php?id=<?php echo $inventario["id_producto"]; ?>"
                            class="btn-eliminar"
                            title="Ver historial del producto">
                                📜
                            </a>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>