<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

require_once("../../config/conexion.php");


// ======================================================
// BUSCADOR
// ======================================================

$busqueda = trim($_GET["busqueda"] ?? "");


// ======================================================
// CONSULTAR INGRESOS
// ======================================================

$sql = "SELECT

            ii.id_ingreso,
            ii.fecha,
            ii.documento,
            ii.referencia,
            ii.total_compra,
            ii.estado,

            pr.nombre AS proveedor,

            p.nombre AS producto,
            p.codigo_producto,

            di.cantidad,
            di.precio_compra,
            di.subtotal

        FROM ingresos_inventario ii

        INNER JOIN proveedores pr
        ON ii.id_proveedor = pr.id_proveedor

        INNER JOIN detalle_ingreso di
        ON ii.id_ingreso = di.id_ingreso

        INNER JOIN productos p
        ON di.id_producto = p.id_producto";


// ======================================================
// FILTRO DE BÚSQUEDA
// ======================================================

if (!empty($busqueda)) {

    $sql .= " WHERE

                pr.nombre LIKE ?
                OR ii.documento LIKE ?
                OR ii.referencia LIKE ?
                OR p.nombre LIKE ?
                OR p.codigo_producto LIKE ?";

}


$sql .= " ORDER BY ii.fecha DESC, ii.id_ingreso DESC";


// ======================================================
// PREPARAR CONSULTA
// ======================================================

$stmt = $conexion->prepare($sql);


if (!empty($busqueda)) {

    $buscar = "%" . $busqueda . "%";

    $stmt->bind_param(
        "sssss",
        $buscar,
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Ingresos | AGRANDA</title>

    <link
        rel="stylesheet"
        href="inventario.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ==================================================
         MENÚ LATERAL
    =================================================== -->

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


    <!-- ==================================================
         CONTENIDO PRINCIPAL
    =================================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
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
                    <?php echo htmlspecialchars($_SESSION["nombre"]); ?>

                </div>


                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir">

                    Cerrar sesión

                </a>

            </div>

        </header>



        <!-- ==================================================
             SECCIÓN INGRESOS
        =================================================== -->

        <section class="resumen">

            <h2>
                📋 Ingresos de Inventario
            </h2>

            <p>
                Consulta los ingresos registrados para
                seleccionar el que deseas editar.
            </p>

            <!-- BARRA SUPERIOR -->

            <div class="barra-productos">


                <a
                    href="ingresos_de_inventario.php"
                    class="btn-nuevo">

                    📥 Nuevo ingreso

                </a>


                <a
                    href="historial_inventario.php"
                    class="btn-secundario">

                    📜 Historial General

                </a>

                <a href="inventario.php" class="btn-cancelar">
                    ⬅️ Volver al Inventario
                </a>


                <form method="GET">

                    <input
                        type="search"
                        name="busqueda"
                        class="buscar-producto"
                        placeholder="Buscar ingreso..."
                        value="<?php echo htmlspecialchars($busqueda); ?>">

                </form>

            </div>



            <!-- ==================================================
                 TABLA
            =================================================== -->

            <table class="tabla-productos">

                <thead>

                    <tr>

                        <th>Fecha</th>

                        <th>Proveedor</th>

                        <th>Documento</th>

                        <th>Referencia</th>

                        <th>Producto</th>

                        <th>Cantidad</th>

                        <th>Precio compra</th>

                        <th>Subtotal</th>

                        <th>Total ingreso</th>

                        <th>Estado</th>

                        <th>Acciones</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($resultado->num_rows > 0) { ?>


                    <?php while ($ingreso = $resultado->fetch_assoc()) { ?>


                        <tr>


                            <!-- FECHA -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $ingreso["fecha"]
                                );
                                ?>

                            </td>



                            <!-- PROVEEDOR -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $ingreso["proveedor"]
                                );
                                ?>

                            </td>



                            <!-- DOCUMENTO -->

                            <td>

                                <?php

                                echo !empty($ingreso["documento"])

                                    ? htmlspecialchars(
                                        $ingreso["documento"]
                                    )

                                    : "Sin documento";

                                ?>

                            </td>



                            <!-- REFERENCIA -->

                            <td>

                                <?php

                                echo !empty($ingreso["referencia"])

                                    ? htmlspecialchars(
                                        $ingreso["referencia"]
                                    )

                                    : "Sin referencia";

                                ?>

                            </td>



                            <!-- PRODUCTO -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $ingreso["producto"]
                                );
                                ?>

                                <br>

                                <small>

                                    <?php
                                    echo htmlspecialchars(
                                        $ingreso["codigo_producto"]
                                    );
                                    ?>

                                </small>

                            </td>



                            <!-- CANTIDAD -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $ingreso["cantidad"]
                                );
                                ?>

                            </td>



                            <!-- PRECIO COMPRA -->

                            <td>

                                $
                                <?php

                                echo number_format(
                                    $ingreso["precio_compra"],
                                    2,
                                    ",",
                                    "."
                                );

                                ?>

                            </td>



                            <!-- SUBTOTAL -->

                            <td>

                                $
                                <?php

                                echo number_format(
                                    $ingreso["subtotal"],
                                    2,
                                    ",",
                                    "."
                                );

                                ?>

                            </td>



                            <!-- TOTAL INGRESO -->

                            <td>

                                $
                                <?php

                                echo number_format(
                                    $ingreso["total_compra"],
                                    2,
                                    ",",
                                    "."
                                );

                                ?>

                            </td>



                            <!-- ESTADO -->

                            <td>

                                <?php

                                if ($ingreso["estado"] == "Recibido") {

                                    echo '<span class="stock-disponible">
                                            Recibido
                                          </span>';

                                } elseif ($ingreso["estado"] == "Pendiente") {

                                    echo '<span class="stock-bajo">
                                            Pendiente
                                          </span>';

                                } else {

                                    echo '<span class="stock-agotado">
                                            Cancelado
                                          </span>';

                                }

                                ?>

                            </td>



                            <!-- ACCIONES -->

                            <td class="acciones">

                                <a
                                    href="editar_ingreso.php?id=<?php echo $ingreso["id_ingreso"]; ?>"
                                    class="btn-editar"
                                    title="Editar ingreso">

                                    ✏️

                                </a>

                            </td>


                        </tr>


                    <?php } ?>


                <?php } else { ?>


                    <tr>

                        <td
                            colspan="11"
                            style="text-align:center;">

                            No se encontraron ingresos registrados.

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