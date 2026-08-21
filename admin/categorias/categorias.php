<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

$sql = "SELECT * FROM categorias ORDER BY id_categoria DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Categorías | AGRANDA</title>

    <link rel="stylesheet" href="categorias.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">
            <h1>AGRANDA</h1>
            <p>Categorías</p>
        </div>

        <nav>
            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="categorias.php" class="activo">🗂️ Categorías</a></li>

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

    <!-- Contenido -->
    <main class="contenido">

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

        <section class="resumen">

            <h2>🗂️ Gestor de Categorías</h2>

            <p>
                Desde aquí podrás agregar, editar y eliminar las categorías de AGRANDA.
            </p>

            <div class="barra-productos">

                <a href="agregar_categoria.php" class="btn-nuevo">
                    ➕ Nueva Categoría
                </a>

                <input
                    type="search"
                    class="buscar-producto"
                    placeholder="Buscar categoría..."
                >

            </div>

            <table class="tabla-productos">

                <thead>

                    <tr>

                        <th>Imagen</th>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php while($categoria = $resultado->fetch_assoc()){ ?>

                    <tr>

                        <td>

                            <?php if(!empty($categoria["imagen"])){ ?>

                                <img
                                    src="../../<?php echo $categoria["imagen"]; ?>"
                                    alt="Categoría"
                                    width="100">

                            <?php }else{ ?>

                                Sin imagen

                            <?php } ?>

                        </td>

                        <td>
                            <?php echo $categoria["id_categoria"]; ?>
                        </td>

                        <td>
                            <?php echo $categoria["nombre"]; ?>
                        </td>

                        <td>
                            <?php echo $categoria["descripcion"]; ?>
                        </td>

                        <td>

                            <?php if($categoria["estado"]){ ?>

                                <span class="estado-activo">
                                    Activa
                                </span>

                            <?php } else { ?>

                                <span class="estado-inactivo">
                                    Inactiva
                                </span>

                            <?php } ?>

                        </td>

                        <td class="acciones">

                            <a href="editar_categoria.php?id=<?php echo $categoria["id_categoria"]; ?>"
                                class="btn-editar">✏️</a>
                            
                            <a href="eliminar_categoria.php?id=<?php echo $categoria["id_categoria"]; ?>"
                                class="btn-eliminar"onclick="return confirm('¿Estas seguro de eliminar esta categoria?');">🗑️</a>

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