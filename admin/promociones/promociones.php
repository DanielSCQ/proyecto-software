<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// CONSULTA DE PROMOCIONES
// =================================

$sql = "SELECT
            id_promocion,
            nombre,
            descripcion,
            tipo,
            valor_descuento,
            fecha_inicio,
            fecha_fin,
            estado,
            fecha_creacion
        FROM promociones
        ORDER BY id_promocion DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Promociones | AGRANDA</title>

    <link rel="stylesheet" href="promociones.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Promociones</p>

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

                <li><a href="promociones.php" class="activo">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <!-- =================================
         CONTENIDO PRINCIPAL
    ================================== -->

    <main class="contenido">

        <!-- =================================
             ENCABEZADO
        ================================== -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"]); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>


                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"]); ?></div>

                <a href="../cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

            </div>

        </header>


        <!-- =================================
             CONTENIDO PROMOCIONES
        ================================== -->

        <section class="resumen">

            <div class="encabezado-promociones">

                <div>

                    <h2>🎁 Gestor de Promociones</h2>

                    <p>
                        Desde aquí podrás crear, editar y administrar
                        las promociones y descuentos de los productos
                        de AGRANDA.
                    </p>

                </div>

            </div>

            <!-- =================================
                 BARRA DE HERRAMIENTAS
            ================================== -->

            <div class="barra-promociones">

                <a href="agregar_promocion.php" class="btn-nuevo">➕ Nueva promoción</a>


                <input
                    type="search"
                    class="buscar-promocion"
                    placeholder="🔎 Buscar promoción..."
                >

            </div>


            <!-- =================================
                 TABLA DE PROMOCIONES
            ================================== -->

            <div class="contenedor-tabla">

                <table class="tabla-promociones">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Nombre</th>

                            <th>Descripción</th>

                            <th>Tipo</th>

                            <th>Descuento</th>

                            <th>Inicio</th>

                            <th>Fin</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($promocion = $resultado->fetch_assoc()): ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    <?php echo $promocion["id_promocion"]; ?>

                                </td>

                                <!-- NOMBRE -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $promocion["nombre"]
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <!-- DESCRIPCIÓN -->

                                <td>

                                    <?php

                                    echo !empty($promocion["descripcion"])
                                        ? htmlspecialchars(
                                            $promocion["descripcion"]
                                        )
                                        : "Sin descripción";

                                    ?>

                                </td>

                                <!-- TIPO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $promocion["tipo"]
                                    );

                                    ?>

                                </td>

                                <!-- DESCUENTO -->

                                <td>

                                    <?php if ($promocion["tipo"] === "Porcentaje"): ?>

                                        <?php
                                        echo number_format(
                                            $promocion["valor_descuento"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>%

                                    <?php else: ?>

                                        $
                                        <?php
                                        echo number_format(
                                            $promocion["valor_descuento"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    <?php endif; ?>

                                </td>

                                <!-- FECHA INICIO -->

                                <td>

                                    <?php
                                    echo date(
                                        "d/m/Y",
                                        strtotime(
                                            $promocion["fecha_inicio"]
                                        )
                                    );
                                    ?>

                                </td>

                                <!-- FECHA FIN -->

                                <td>

                                    <?php
                                    echo date(
                                        "d/m/Y",
                                        strtotime(
                                            $promocion["fecha_fin"]
                                        )
                                    );
                                    ?>

                                </td>

                                <!-- ESTADO -->

                                <td>

                                    <?php if ($promocion["estado"]): ?>

                                        <span class="estado-activo">Activa</span>

                                    <?php else: ?>

                                        <span class="estado-inactivo">Inactiva</span>

                                    <?php endif; ?>

                                </td>

                                <!-- ACCIONES -->

                                <td class="acciones">

                                    <a href="ver_promocion.php?id=<?php echo $promocion["id_promocion"]; ?>"
                                        class="btn-ver"
                                        title="Ver promoción">👁️</a>

                                    <a href="editar_promocion.php?id=<?php echo $promocion["id_promocion"]; ?>"
                                        class="btn-editar"
                                        title="Editar promoción">✏️</a>

                                    <a href="eliminar_promocion.php?id=<?php echo $promocion["id_promocion"]; ?>"
                                        class="btn-eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar esta promoción?');">🗑️</a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="9" class="sin-promociones">No hay promociones registradas.</td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>