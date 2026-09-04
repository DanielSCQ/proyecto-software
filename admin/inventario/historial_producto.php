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


// Verificar que llegue el ID del producto
if (!isset($_GET["id"]) || !filter_var($_GET["id"], FILTER_VALIDATE_INT)) {

    header("Location: inventario.php");
    exit();

}

$id_producto = (int)$_GET["id"];


// ============================================================
// INFORMACIÓN DEL PRODUCTO
// ============================================================

$sqlProducto = "SELECT

                    p.nombre,
                    p.codigo_producto,
                    p.precio,
                    p.descripcion,

                    c.nombre AS categoria,

                    m.nombre AS marca,

                    i.stock_actual,
                    i.stock_minimo

                FROM productos p

                INNER JOIN categorias c
                ON p.id_categoria = c.id_categoria

                LEFT JOIN marcas m
                ON p.id_marca = m.id_marca

                LEFT JOIN inventario i
                ON p.id_producto = i.id_producto

                WHERE p.id_producto = ?";


$stmtProducto = $conexion->prepare($sqlProducto);

$stmtProducto->bind_param(
    "i",
    $id_producto
);

$stmtProducto->execute();

$resultadoProducto = $stmtProducto->get_result();

$producto = $resultadoProducto->fetch_assoc();


// Si el producto no existe
if (!$producto) {

    header("Location: inventario.php");
    exit();

}


// ============================================================
// HISTORIAL DEL PRODUCTO
// ============================================================
//
// Se relacionan:
//
// movimientos_inventario
//          ↓
// detalle_ingreso
//          ↓
// ingresos_inventario
//          ↓
// proveedores
//
// Esto permite mostrar información adicional cuando el
// movimiento corresponde a un ingreso.
//
// ============================================================

$sqlHistorial = "SELECT

                    mi.fecha,

                    mi.tipo,

                    mi.cantidad,

                    mi.motivo,

                    mi.observacion,

                    CONCAT(
                        u.nombre,
                        ' ',
                        u.apellido
                    ) AS usuario,

                    pr.nombre AS proveedor,

                    ii.documento,

                    ii.referencia,

                    di.precio_compra,

                    di.subtotal,

                    ii.total_compra

                FROM movimientos_inventario mi

                INNER JOIN usuarios u
                ON mi.id_usuario = u.id_usuario

                LEFT JOIN proveedores pr
                ON mi.id_proveedor = pr.id_proveedor

                LEFT JOIN detalle_ingreso di
                ON di.id_ingreso = mi.id_ingreso

                LEFT JOIN ingresos_inventario ii
                ON ii.id_ingreso = mi.id_ingreso

                WHERE mi.id_producto = ?

                ORDER BY mi.fecha DESC";


$stmtHistorial = $conexion->prepare($sqlHistorial);

$stmtHistorial->bind_param(
    "i",
    $id_producto
);

$stmtHistorial->execute();

$resultadoHistorial = $stmtHistorial->get_result();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Historial Producto | AGRANDA
    </title>

    <link
        rel="stylesheet"
        href="inventario.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- =====================================================
         MENÚ LATERAL
    ====================================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>
                Inventario
            </p>

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


    <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

    <main class="contenido">

        <!-- ENCABEZADO -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"],ENT_QUOTES,"UTF-8");?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span>

                    <br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"],ENT_QUOTES,"UTF-8");?></div>

                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- =================================================
             INFORMACIÓN DEL PRODUCTO
        ================================================== -->

        <div class="principal">

            <section class="resumen">

                <h2>📜 Historial del Producto</h2> 

                <p>
                    Consulta todos los movimientos e información
                    relacionada con este producto.
                </p>

                <a href="inventario.php" class="btn-cancelar">
                    ⬅️ Volver al Inventario
                </a>

                <br></br>
                <!-- INFORMACIÓN GENERAL DEL PRODUCTO -->

                <div class="informacion-producto">

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $producto["nombre"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </h3>

                    <p>
                        <strong>Código:</strong>

                        <?php
                        echo htmlspecialchars(
                            $producto["codigo_producto"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Categoría:</strong>

                        <?php
                        echo htmlspecialchars(
                            $producto["categoria"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </p>

                    <p>

                        <strong>Marca:</strong>

                        <?php

                        echo !empty($producto["marca"])

                            ? htmlspecialchars(
                                $producto["marca"],
                                ENT_QUOTES,
                                "UTF-8"
                            )

                            : "Sin marca";
                        ?>
                    </p>

                    <p>
                        <strong>Precio de venta:</strong>

                        $

                        <?php
                        echo number_format(
                            (float)$producto["precio"],
                            2,
                            ",",
                            "."
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Stock actual:</strong>

                        <?php
                        echo (int)$producto["stock_actual"];
                        ?>
                    </p>

                    <p>
                        <strong>Stock mínimo:</strong>

                        <?php
                        echo (int)$producto["stock_minimo"];
                        ?>
                    </p>

                </div>

                <br>

                <!-- =================================================
                     TABLA DEL HISTORIAL
                ================================================== -->

                <div class="tabla-contenedor">

                    <table class="tabla-productos">

                        <thead>

                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Proveedor</th>
                                <th>Documento</th>
                                <th>Referencia</th>
                                <th>Cantidad</th>
                                <th>Precio compra</th>
                                <th>Subtotal</th>
                                <th>Motivo</th>
                                <th>Observación</th>
                                <th>Usuario</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php

                        if ($resultadoHistorial->num_rows > 0) {

                            while (
                                $movimiento =
                                $resultadoHistorial->fetch_assoc()
                            ) {

                        ?>


                            <tr>


                                <!-- FECHA -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["fecha"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </td>


                                <!-- TIPO -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["tipo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </td>


                                <!-- PROVEEDOR -->

                                <td>

                                    <?php

                                    echo !empty(
                                        $movimiento["proveedor"]
                                    )

                                        ? htmlspecialchars(
                                            $movimiento["proveedor"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )

                                        : "—";

                                    ?>

                                </td>
                                <!-- DOCUMENTO -->

                                <td>

                                    <?php

                                    echo !empty(
                                        $movimiento["documento"]
                                    )

                                        ? htmlspecialchars(
                                            $movimiento["documento"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )

                                        : "—";

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    echo !empty(
                                        $movimiento["referencia"]
                                    )

                                        ? htmlspecialchars(
                                            $movimiento["referencia"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )

                                        : "—";
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo (int)$movimiento["cantidad"];
                                    ?>

                                </td>

                                <td>

                                    <?php

                                    if (
                                        $movimiento["precio_compra"]
                                        !== null
                                    ) {

                                        echo "$" .
                                            number_format(
                                                (float)$movimiento[
                                                    "precio_compra"
                                                ],
                                                2,
                                                ",",
                                                "."
                                            );

                                    } else {

                                        echo "—";

                                    }

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    if (
                                        $movimiento["subtotal"]
                                        !== null
                                    ) {

                                        echo "$" .
                                            number_format(
                                                (float)$movimiento[
                                                    "subtotal"
                                                ],
                                                2,
                                                ",",
                                                "."
                                            );

                                    } else {

                                        echo "—";
                                    }
                                    ?>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["motivo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo !empty(
                                        $movimiento["observacion"]
                                    )
                                        ? htmlspecialchars(
                                            $movimiento["observacion"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        : "Sin observación";
                                    ?>
                                </td>
                                
                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["usuario"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </td>
                            </tr>

                        <?php

                            }

                        } else {

                        ?>
                            <tr>
                                <td
                                    colspan="11"
                                    style="text-align:center;">

                                    No hay movimientos registrados
                                    para este producto.

                                </td>
                            </tr>
                        <?php
                        }
                        ?>

                        </tbody>

                    </table>

                </div>


            </section>

        </div>


    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>