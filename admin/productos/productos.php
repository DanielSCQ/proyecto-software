<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// CONSULTA DE PRODUCTOS
// =================================

$sql = "SELECT
    p.id_producto,
    p.nombre,
    p.codigo_producto,
    p.precio,
    pp.precio_compra,
    p.peso,
    p.descripcion,
    p.estado,
    p.destacado,

    c.nombre AS categoria,

    m.nombre AS marca,

    prov.nombre AS proveedor,

    pp.codigo_proveedor,

    i.stock_actual,
    i.stock_minimo,

    img.ruta_imagen,

    GROUP_CONCAT(
        CONCAT(
            ap.nombre,
            ': ',
            pa.valor
        )
        SEPARATOR ' | '
    ) AS caracteristicas,

    pr.id_promocion,
    pr.nombre AS nombre_promocion,
    pr.tipo AS tipo_promocion,
    pr.valor_descuento,
    pr.fecha_inicio,
    pr.fecha_fin

FROM productos p

INNER JOIN categorias c
    ON p.id_categoria = c.id_categoria

LEFT JOIN marcas m
    ON p.id_marca = m.id_marca

LEFT JOIN proveedor_producto pp
    ON p.id_producto = pp.id_producto

LEFT JOIN proveedores prov
    ON pp.id_proveedor = prov.id_proveedor

LEFT JOIN inventario i
    ON p.id_producto = i.id_producto

LEFT JOIN imagenes_producto img
    ON p.id_producto = img.id_producto
    AND img.principal = 1

LEFT JOIN producto_atributo pa
    ON p.id_producto = pa.id_producto

LEFT JOIN atributos_producto ap
    ON pa.id_atributo = ap.id_atributo

LEFT JOIN promociones pr
    ON pr.id_promocion = (
        SELECT pp2.id_promocion

        FROM promocion_producto pp2

        INNER JOIN promociones pr2
            ON pp2.id_promocion = pr2.id_promocion

        WHERE pp2.id_producto = p.id_producto

        AND pr2.estado = 1

        AND CURDATE() BETWEEN
            pr2.fecha_inicio
            AND pr2.fecha_fin

        ORDER BY
            pr2.id_promocion DESC

        LIMIT 1
    )

GROUP BY
    p.id_producto,
    p.nombre,
    p.codigo_producto,
    p.precio,
    pp.precio_compra,
    p.peso,
    p.descripcion,
    p.estado,
    p.destacado,
    c.nombre,
    m.nombre,
    prov.nombre,
    pp.codigo_proveedor,
    i.stock_actual,
    i.stock_minimo,
    img.ruta_imagen,
    pr.id_promocion,
    pr.nombre,
    pr.tipo,
    pr.valor_descuento,
    pr.fecha_inicio,
    pr.fecha_fin

ORDER BY p.id_producto DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

    <!-- =================================
         CONTENEDOR PRINCIPAL
    ================================== -->

    <div class="contenedor-dashboard">


        <!-- =================================
             MENÚ LATERAL
        ================================== -->

        <aside class="menu-lateral">

            <div class="logo-panel">

                <h1>AGRANDA</h1>

                <p>productos</p>

            </div>

            <nav>

                <ul>

                    <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                    <li><a href="productos.php" class="activo">📦 Productos</a></li>

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


        <!-- =================================
             CONTENIDO PRINCIPAL
        ================================== -->

        <main class="contenido pagina-productos">

            <!-- =================================
                 ENCABEZADO
            ================================== -->

            <header class="encabezado">

                <div class="titulo-panel">

                    <h2>
                        ¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
                    </h2>

                    <p>Panel de Administración AGRANDA</p>

                </div>


                <div class="panel-usuario">

                    <div class="fecha-hora">

                        <span id="fecha"></span><br>

                        <span id="hora"></span>

                    </div>

                    <div class="usuario">
                        👤<?php echo htmlspecialchars($_SESSION["nombre"]); ?>
                    </div>


                    <a href="../cerrar_sesion.php"
                        class="btn-salir">Cerrar sesión</a>

                </div>

            </header>


            <!-- =================================
                 TARJETAS
            ================================== -->

            <section class="tarjetas">

            </section>


            <!-- =================================
                 GESTOR DE PRODUCTOS
            ================================== -->

            <section class="resumen">

                <h2>📦 Gestor de Productos</h2>

                <p>
                    Desde aquí podrás agregar, editar, eliminar y
                    administrar todos los productos de AGRANDA.
                </p>


                <!-- =================================
                     BARRA DE PRODUCTOS
                ================================== -->

                <div class="barra-productos">

                    <a href="agregar_producto.php"
                        class="btn-nuevo">➕ Nuevo Producto</a>

                    <a href="atributos.php"
                        class="btn-nuevo">⚙️ Atributos</a>


                    <input
                        type="search"
                        class="buscar-producto"
                        placeholder="Buscar producto..."
                    >

                </div>


                <!-- =================================
                     TABLA
                ================================== -->

                <div class="contenedor-tabla">
                <table class="tabla-productos">

                    <thead>

                        <tr>

                            <th>Imagen</th>

                            <th>Nombre</th>

                            <th>Código</th>

                            <th>Categoría</th>

                            <th>Marca</th>

                            <th>Proveedor</th>

                            <th>Código proveedor</th>

                            <th>Características</th>

                            <th>Precio venta</th>

                            <th>Precio compra</th>

                            <th>Stock actual</th>

                            <th>Stock mínimo</th>

                            <th>Peso</th>

                            <th>Descripción</th>

                            <th>Promoción</th>

                            <th>Destacado</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($producto = $resultado->fetch_assoc()): ?>


                        <?php

                        // =================================
                        // CALCULAR PRECIO
                        // =================================

                        $precioOriginal = (float) $producto["precio"];

                        $precioFinal = $precioOriginal;

                        $descuentoAplicado = 0;


                        // Verificar si tiene promoción vigente

                        if (!empty($producto["id_promocion"])) {

                            // ---------------------------------
                            // DESCUENTO PORCENTUAL
                            // ---------------------------------

                            if (
                                $producto["tipo_promocion"]
                                === "Porcentaje"
                            ) {

                                $descuentoAplicado =
                                    $precioOriginal *
                                    (
                                        (float) $producto["valor_descuento"]
                                        / 100
                                    );

                            }


                            // ---------------------------------
                            // DESCUENTO FIJO
                            // ---------------------------------

                            elseif (
                                $producto["tipo_promocion"]
                                === "Fijo"
                            ) {

                                $descuentoAplicado =
                                    (float)
                                    $producto["valor_descuento"];

                            }


                            // ---------------------------------
                            // CALCULAR PRECIO FINAL
                            // ---------------------------------

                            $precioFinal =
                                $precioOriginal -
                                $descuentoAplicado;


                            // Evitar precios negativos

                            if ($precioFinal < 0) {

                                $precioFinal = 0;

                            }

                        }

                        ?>


                        <tr>


                            <!-- =================================
                                 IMAGEN
                            ================================== -->

                            <td>

                                <?php if (!empty($producto["ruta_imagen"])): ?>

                                    <img
                                        src="../../<?php echo htmlspecialchars($producto["ruta_imagen"]); ?>"
                                        alt="Producto"
                                        width="70"
                                    >

                                <?php else: ?>

                                    Sin imagen

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 NOMBRE
                            ================================== -->

                            <td>

                                <?php echo htmlspecialchars(
                                    $producto["nombre"]
                                ); ?>

                            </td>


                            <!-- =================================
                                 CÓDIGO
                            ================================== -->

                            <td>

                                <?php echo htmlspecialchars(
                                    $producto["codigo_producto"]
                                ); ?>

                            </td>


                            <!-- =================================
                                 CATEGORÍA
                            ================================== -->

                            <td>

                                <?php echo htmlspecialchars(
                                    $producto["categoria"]
                                ); ?>

                            </td>


                            <!-- =================================
                                 MARCA
                            ================================== -->

                            <td>

                                <?php

                                echo !empty($producto["marca"])
                                    ? htmlspecialchars(
                                        $producto["marca"]
                                    )
                                    : "Sin marca";

                                ?>

                            </td>


                            <!-- =================================
                                 PROVEEDOR
                            ================================== -->

                            <td>

                                <?php

                                echo !empty($producto["proveedor"])
                                    ? htmlspecialchars(
                                        $producto["proveedor"]
                                    )
                                    : "Sin proveedor";

                                ?>

                            </td>


                            <!-- =================================
                                 CÓDIGO DEL PROVEEDOR
                            ================================== -->

                            <td>

                                <?php

                                echo !empty($producto["codigo_proveedor"])
                                    ? htmlspecialchars(
                                        $producto["codigo_proveedor"]
                                    )
                                    : "Sin código";

                                ?>

                            </td>


                            <!-- =================================
                                 CARACTERÍSTICAS
                            ================================== -->

                            <td>

                                <?php if (!empty($producto["caracteristicas"])): ?>

                                    <?php

                                    $caracteristicas = explode(
                                        " | ",
                                        $producto["caracteristicas"]
                                    );

                                    ?>

                                    <?php foreach ($caracteristicas as $caracteristica): ?>

                                        <div>
                                            <?php echo htmlspecialchars($caracteristica); ?>
                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <span class="sin-caracteristicas">
                                        Sin características
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 PRECIO DE VENTA
                            ================================== -->

                            <td>

                                <?php if (!empty($producto["id_promocion"])): ?>

                                    <span class="precio-original">

                                        $
                                        <?php echo number_format(
                                            $precioOriginal,
                                            0,
                                            ",",
                                            "."
                                        ); ?>

                                    </span>

                                    <br>

                                    <strong class="precio-promocion">

                                        $
                                        <?php echo number_format(
                                            $precioFinal,
                                            0,
                                            ",",
                                            "."
                                        ); ?>

                                    </strong>

                                    <br>

                                    <span class="descuento-promocion">

                                        🔥

                                        <?php

                                        if (
                                            $producto["tipo_promocion"]
                                            === "Porcentaje"
                                        ) {

                                            echo number_format(
                                                $producto["valor_descuento"],
                                                0,
                                                ",",
                                                "."
                                            ) . "%";

                                        } else {

                                            echo "$" .
                                                number_format(
                                                    $producto["valor_descuento"],
                                                    0,
                                                    ",",
                                                    "."
                                                );

                                        }

                                        ?>

                                    </span>

                                <?php else: ?>

                                    $
                                    <?php echo number_format(
                                        $precioOriginal,
                                        0,
                                        ",",
                                        "."
                                    ); ?>

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 PRECIO DE COMPRA
                            ================================== -->

                            <td>

                                <?php

                                if (
                                    $producto["precio_compra"] !== null &&
                                    $producto["precio_compra"] !== ""
                                ) {

                                    echo "$" .
                                        number_format(
                                            (float) $producto["precio_compra"],
                                            0,
                                            ",",
                                            "."
                                        );

                                } else {

                                    echo "Sin registrar";

                                }

                                ?>

                            </td>


                            <!-- =================================
                                 STOCK ACTUAL
                            ================================== -->

                            <td>

                                <?php

                                echo $producto["stock_actual"] ?? 0;

                                ?>

                            </td>


                            <!-- =================================
                                 STOCK MÍNIMO
                            ================================== -->

                            <td>

                                <?php

                                echo $producto["stock_minimo"] ?? 0;

                                ?>

                            </td>


                            <!-- =================================
                                 PESO
                            ================================== -->

                            <td>

                                <?php

                                if (
                                    $producto["peso"] !== null &&
                                    $producto["peso"] !== ""
                                ) {

                                    echo htmlspecialchars(
                                        $producto["peso"]
                                    ) . " kg";

                                } else {

                                    echo "Sin registrar";

                                }

                                ?>

                            </td>


                            <!-- =================================
                                 DESCRIPCIÓN
                            ================================== -->

                            <td>

                                <?php

                                echo !empty($producto["descripcion"])
                                    ? htmlspecialchars(
                                        $producto["descripcion"]
                                    )
                                    : "Sin descripción";

                                ?>

                            </td>


                            <!-- =================================
                                 PROMOCIÓN
                            ================================== -->

                            <td>

                                <?php if (!empty($producto["id_promocion"])): ?>

                                    <span class="promocion-activa">

                                        🔥<?php echo htmlspecialchars(
                                            $producto["nombre_promocion"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    <span class="sin-promocion">
                                        Sin promoción
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 DESTACADO
                            ================================== -->

                            <td>

                                <?php if ($producto["destacado"]): ?>

                                    ⭐ Sí

                                <?php else: ?>

                                    No

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 ESTADO
                            ================================== -->

                            <td>

                                <?php if ($producto["estado"]): ?>

                                    <span class="estado-activo">
                                        Activo
                                    </span>

                                <?php else: ?>

                                    <span class="estado-inactivo">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================
                                 ACCIONES
                            ================================== -->

                            <td class="acciones">

                                <a
                                    href="editar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                                    class="btn-editar"
                                    title="Editar producto"
                                >✏️</a>


                                <a
                                    href="eliminar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                                    class="btn-eliminar"
                                    title="Eliminar producto"
                                    onclick="return confirm('¿Está seguro de eliminar este producto?');"
                                >🗑️</a>

                            </td>

                        </tr>


                    <?php endwhile; ?>

                    </tbody>

                </table>
                </div>         
            </section>

        </main>

    </div>
    <script src="../dashboard/dashboard.js"></script>
</body>
</html>