<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Configuración | AGRANDA</title>

    <link rel="stylesheet" href="configuracion.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ===============================
         MENÚ LATERAL
    ================================ -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Configuración</p>

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

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="configuracion.php" class="activo">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- ===============================
         CONTENIDO PRINCIPAL
    ================================ -->

    <main class="contenido">


        <!-- ===============================
             ENCABEZADO
        ================================ -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">
                    👤 <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- ===============================
             CONFIGURACIÓN
        ================================ -->

        <section class="contenido-configuracion">

            <div class="encabezado-configuracion">

                <div>

                    <h2>⚙️ Configuración</h2>

                    <p>
                        Administra la apariencia, la información institucional
                        y otras opciones de la tienda AGRANDA.
                    </p>

                </div>

            </div>


            <!-- ===============================
                 OPCIONES DE CONFIGURACIÓN
            ================================ -->

            <div class="configuracion-grid">


                <!-- APARIENCIA -->

                <a href="apariencia.php" class="configuracion-card">

                    <div class="configuracion-icono">
                        🎨
                    </div>

                    <div class="configuracion-card-contenido">

                        <h3>Apariencia</h3>

                        <p>
                            Administra el logo y las imágenes principales
                            utilizadas en la tienda y el panel administrativo.
                        </p>

                        <span class="configuracion-enlace">
                            Gestionar apariencia →
                        </span>

                    </div>

                </a>


                <!-- NOSOTROS -->

                <a href="nosotros.php" class="configuracion-card">

                    <div class="configuracion-icono">
                        🏢
                    </div>

                    <div class="configuracion-card-contenido">

                        <h3>Nosotros</h3>

                        <p>
                            Administra la información que se mostrará
                            en la sección Nosotros de la tienda.
                        </p>

                        <span class="configuracion-enlace">
                            Gestionar información →
                        </span>

                    </div>

                </a>


                <!-- CUPONES -->

                <a href="cupones.php" class="configuracion-card">

                    <div class="configuracion-icono">
                        🎟️
                    </div>

                    <div class="configuracion-card-contenido">

                        <h3>Cupones</h3>

                        <p>
                            Crea y administra cupones de descuento
                            para los clientes de AGRANDA.
                        </p>

                        <span class="configuracion-enlace">
                            Gestionar cupones →
                        </span>

                    </div>

                </a>


            </div>

        </section>

    </main>

</div>


<script src="../dashboard/dashboard.js"></script>

</body>

</html>