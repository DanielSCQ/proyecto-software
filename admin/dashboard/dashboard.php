<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Control | AGRANDA</title>

    <link rel="stylesheet" href="dashboard.css">

</head>

<body>

    <!-- Contenedor principal -->
    <div class="contenedor-dashboard">

        <!-- Menú lateral -->
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

                    <a href="../cerrar_sesion.php" class="btn-salir">
                        Cerrar sesión
                    </a>

                </div>

            </header>

            <!-- Tarjetas -->
            <section class="tarjetas">

                <div class="tarjeta">
                    <h3>Productos</h3>
                    <p>0</p>
                </div>

                <div class="tarjeta">
                    <h3>Pedidos</h3>
                    <p>0</p>
                </div>

                <div class="tarjeta">
                    <h3>Clientes</h3>
                    <p>0</p>
                </div>

                <div class="tarjeta">
                    <h3>Stock Bajo</h3>
                    <p>0</p>
                </div>

            </section>

            <!-- Área principal -->
            <section class="resumen">

                <h2>Resumen del sistema</h2>

                <p>
                    Aquí se mostrará la información principal del panel de administración.
                </p>

            </section>

        </main>

    </div>

    <script src="dashboard.js"></script>

</body>

</html>