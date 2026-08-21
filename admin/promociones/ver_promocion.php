<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// OBTENER ID DE LA PROMOCIÓN
// =================================

$id_promocion = intval($_GET["id"] ?? 0);

if ($id_promocion <= 0) {
    header("Location: promociones.php");
    exit();
}


// =================================
// CONSULTAR INFORMACIÓN DE LA PROMOCIÓN
// =================================

$sql_promocion = "SELECT
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

                  WHERE id_promocion = ?";


$stmt_promocion = $conexion->prepare($sql_promocion);

if (!$stmt_promocion) {
    die("Error en la consulta: " . $conexion->error);
}

$stmt_promocion->bind_param("i", $id_promocion);

$stmt_promocion->execute();

$resultado_promocion = $stmt_promocion->get_result();


// =================================
// VERIFICAR SI EXISTE
// =================================

if ($resultado_promocion->num_rows === 0) {
    header("Location: promociones.php");
    exit();
}

$promocion = $resultado_promocion->fetch_assoc();


// =================================
// CONSULTAR PRODUCTOS ASOCIADOS
// =================================

$sql_productos = "SELECT
                    p.id_producto,
                    p.nombre,
                    p.codigo_producto,
                    p.precio,
                    p.estado

                  FROM promocion_producto pp

                  INNER JOIN productos p
                  ON pp.id_producto = p.id_producto

                  WHERE pp.id_promocion = ?

                  ORDER BY p.nombre ASC";


$stmt_productos = $conexion->prepare($sql_productos);

if (!$stmt_productos) {
    die("Error en la consulta de productos: " . $conexion->error);
}

$stmt_productos->bind_param("i", $id_promocion);

$stmt_productos->execute();

$resultado_productos = $stmt_productos->get_result();


// =================================
// CONTAR PRODUCTOS
// =================================

$cantidad_productos = $resultado_productos->num_rows;


// =================================
// FORMATEAR DESCUENTO
// =================================

if ($promocion["tipo"] === "Porcentaje") {

    $descuento = number_format(
        $promocion["valor_descuento"],
        0,
        ",",
        "."
    ) . "%";

} else {

    $descuento = "$" . number_format(
        $promocion["valor_descuento"],
        0,
        ",",
        "."
    ) . " COP";

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ver promoción | AGRANDA</title>

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

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"]);?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"]);?></div>

                <a href="../cerrar_sesion.php"
                    class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- =================================
             DETALLE DE PROMOCIÓN
        ================================== -->

        <section class="resumen">

            <!-- =================================
                 CABECERA
            ================================== -->

            <div class="encabezado-promociones">

                <div>

                    <h2>🎁<?php echo htmlspecialchars($promocion["nombre"]);?></h2>

                    <p>Información detallada de la promoción</p>

                </div>

                <div class="acciones-detalle">

                    <a href="promociones.php"
                        class="btn-cancelar">← Volver</a>


                    <a href="editar_promocion.php?id=<?php echo $promocion["id_promocion"]; ?>"
                        class="btn-editar">✏️ Editar</a>

                </div>

            </div>

            <!-- =================================
                 INFORMACIÓN DE LA PROMOCIÓN
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>📋 Información de la promoción</h3>

                <div class="datos-cliente">

                    <!-- NOMBRE -->

                    <div class="dato">

                        <span class="dato-titulo">Nombre</span>

                        <span class="dato-valor">

                            <?php
                            echo htmlspecialchars(
                                $promocion["nombre"]
                            );
                            ?>

                        </span>

                    </div>

                    <!-- DESCRIPCIÓN -->

                    <div class="dato">

                        <span class="dato-titulo">Descripción</span>

                        <span class="dato-valor">

                            <?php

                            echo !empty(
                                $promocion["descripcion"]
                            )
                                ? htmlspecialchars(
                                    $promocion["descripcion"]
                                )
                                : "Sin descripción";

                            ?>

                        </span>

                    </div>

                    <!-- TIPO -->

                    <div class="dato">

                        <span class="dato-titulo">Tipo de descuento</span>

                        <span class="dato-valor">

                            <?php
                            echo htmlspecialchars(
                                $promocion["tipo"]
                            );
                            ?>

                        </span>

                    </div>

                    <!-- DESCUENTO -->

                    <div class="dato">

                        <span class="dato-titulo">Descuento</span>

                        <span class="dato-valor">

                            <strong>
                                <?php echo $descuento; ?>
                            </strong>

                        </span>

                    </div>

                    <!-- FECHA INICIO -->

                    <div class="dato">

                        <span class="dato-titulo">Fecha de inicio</span>

                        <span class="dato-valor">

                            <?php

                            echo date(
                                "d/m/Y",
                                strtotime(
                                    $promocion["fecha_inicio"]
                                )
                            );

                            ?>

                        </span>

                    </div>

                    <!-- FECHA FINAL -->

                    <div class="dato">

                        <span class="dato-titulo">Fecha de finalización</span>

                        <span class="dato-valor">

                            <?php

                            echo date(
                                "d/m/Y",
                                strtotime(
                                    $promocion["fecha_fin"]
                                )
                            );

                            ?>

                        </span>

                    </div>

                    <!-- ESTADO -->

                    <div class="dato">

                        <span class="dato-titulo">Estado</span>

                        <span class="dato-valor">

                            <?php if ($promocion["estado"]): ?>

                                <span class="estado-activo">Activa</span>

                            <?php else: ?>

                                <span class="estado-inactivo">Inactiva</span>

                            <?php endif; ?>

                        </span>

                    </div>

                    <!-- FECHA CREACIÓN -->

                    <div class="dato">

                        <span class="dato-titulo">Fecha de creación</span>

                        <span class="dato-valor">

                            <?php

                            echo date(
                                "d/m/Y H:i",
                                strtotime(
                                    $promocion["fecha_creacion"]
                                )
                            );

                            ?>

                        </span>

                    </div>

                </div>

            </div>

            <!-- =================================
                 RESUMEN DE PRODUCTOS
            ================================== -->

            <div class="tarjetas-estadisticas">

                <div class="estadistica">

                    <span class="estadistica-icono">📦</span>

                    <div>

                        <span class="estadistica-titulo">Productos asociados</span>

                        <strong>
                            <?php
                            echo $cantidad_productos;
                            ?>
                        </strong>

                    </div>

                </div>

                <div class="estadistica">

                    <span class="estadistica-icono">💰</span>

                    <div>

                        <span class="estadistica-titulo">Descuento aplicado</span>

                        <strong><?php echo $descuento; ?></strong>

                    </div>

                </div>

                <div class="estadistica">

                    <span class="estadistica-icono">🎁</span>

                    <div>

                        <span class="estadistica-titulo">Tipo</span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $promocion["tipo"]
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            </div>

            <!-- =================================
                 PRODUCTOS ASOCIADOS
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>📦 Productos asociados</h3>


                <?php if ($resultado_productos->num_rows > 0): ?>


                    <div class="contenedor-tabla">

                        <table class="tabla-promociones">

                            <thead>

                                <tr>

                                    <th>ID</th>

                                    <th>Producto</th>

                                    <th>Código</th>

                                    <th>Precio normal</th>

                                    <th>Descuento</th>

                                    <th>Precio promocional</th>

                                    <th>Estado</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php while (
                                $producto =
                                $resultado_productos->fetch_assoc()
                            ): ?>

                            <?php

                                $precio_normal = (float) $producto["precio"];

                                if ($promocion["tipo"] === "Porcentaje") {

                                    $valor_descuento_producto =
                                        $precio_normal *
                                        ((float) $promocion["valor_descuento"] / 100);

                                } else {

                                    $valor_descuento_producto =
                                        (float) $promocion["valor_descuento"];

                                }

                                $precio_promocional =
                                    $precio_normal - $valor_descuento_producto;

                                // Evitar precios negativos
                                if ($precio_promocional < 0) {
                                    $precio_promocional = 0;
                                }

                                ?>

                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo $producto["id_producto"];
                                        ?>

                                    </td>


                                    <!-- PRODUCTO -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $producto["nombre"]
                                            );

                                            ?>

                                        </strong>

                                    </td>


                                    <!-- CÓDIGO -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $producto["codigo_producto"]
                                        );

                                        ?>

                                    </td>


                                    <!-- PRECIO NORMAL -->

                                    <td>

                                        $

                                        <?php

                                        echo number_format(
                                            $precio_normal,
                                            0,
                                            ",",
                                            "."
                                        );

                                        ?>

                                        COP

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
                                                $valor_descuento_producto,
                                                0,
                                                ",",
                                                "."
                                            );
                                            ?>

                                            COP

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRECIO PROMOCIONAL -->

                                    <td>

                                        <strong>

                                            $

                                            <?php

                                            echo number_format(
                                                $precio_promocional,
                                                0,
                                                ",",
                                                "."
                                            );

                                            ?>

                                            COP

                                        </strong>

                                    </td>

                                    <!-- ESTADO -->

                                    <td>

                                        <?php if ($producto["estado"]): ?>

                                            <span class="estado-activo">Activo</span>

                                        <?php else: ?>

                                            <span class="estado-inactivo">Inactivo</span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p class="sin-promociones">Esta promoción no tiene productos asociados.</p>

                <?php endif; ?>

            </div>


        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>