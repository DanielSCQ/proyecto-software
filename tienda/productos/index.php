<?php

// =================================
// CONEXIÓN Y HEADER
// =================================
require_once("../includes/header.php");

?>

<link rel="stylesheet" href="<?= $base_url ?>css/productos.css">

<?php

// =================================
// VARIABLES Y VALIDACIÓN
// =================================

$categoriaId = filter_input(INPUT_GET, "categoria", FILTER_VALIDATE_INT);
$productoId = filter_input(INPUT_GET, "producto", FILTER_VALIDATE_INT);
$busqueda = trim($_GET["busqueda"] ?? "");

// Si el ID de categoría no es válido, lo tratamos como inexistente
if ($categoriaId !== false && $categoriaId !== null && $categoriaId < 1) {
    $categoriaId = null;
}

// Si el ID de producto no es válido, lo tratamos como inexistente
if ($productoId !== false && $productoId !== null && $productoId < 1) {
    $productoId = null;
}
// Si el ID de categoría no es válido, lo tratamos como inexistente
if ($categoriaId !== false && $categoriaId !== null && $categoriaId < 1) {
    $categoriaId = null;
}

// Limitar la búsqueda para evitar entradas excesivamente largas
if (mb_strlen($busqueda) > 100) {
    $busqueda = mb_substr($busqueda, 0, 100);
}


// =================================
// CATEGORÍAS PARA EL FILTRO
// =================================

$categoriasFiltro = [];

$sqlCategoriasFiltro = "
    SELECT
        id_categoria,
        nombre
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoCategoriasFiltro = $conexion->query($sqlCategoriasFiltro);

if ($resultadoCategoriasFiltro) {

    while ($categoria = $resultadoCategoriasFiltro->fetch_assoc()) {
        $categoriasFiltro[] = $categoria;
    }
}


// =================================
// CATEGORÍA SELECCIONADA
// =================================

$categoriaSeleccionada = null;

if ($categoriaId !== null) {

    $stmtCategoria = $conexion->prepare("
        SELECT
            id_categoria,
            nombre,
            descripcion
        FROM categorias
        WHERE id_categoria = ?
          AND estado = 1
        LIMIT 1
    ");

    if ($stmtCategoria) {

        $stmtCategoria->bind_param("i", $categoriaId);
        $stmtCategoria->execute();

        $resultadoCategoria = $stmtCategoria->get_result();

        if ($resultadoCategoria->num_rows > 0) {

            $categoriaSeleccionada = $resultadoCategoria->fetch_assoc();

        } else {

            // Si la categoría no existe o está inactiva,
            // mostramos todo el catálogo.
            $categoriaId = null;
        }

        $stmtCategoria->close();
    } else {

        $categoriaId = null;
    }
}


// =================================
// OBTENER TODOS LOS PRODUCTOS
// =================================

$productosCatalogo = [];

$stmtProductos = $conexion->prepare("
    SELECT
        p.id_producto,
        p.nombre,
        p.descripcion,
        p.codigo_producto,
        p.precio,
        p.id_categoria,
        c.nombre AS categoria_nombre,
        m.nombre AS marca_nombre,
        i.stock_actual,
        img.ruta_imagen
    FROM productos p

    INNER JOIN categorias c
        ON c.id_categoria = p.id_categoria

    LEFT JOIN marcas m
        ON m.id_marca = p.id_marca

    INNER JOIN inventario i
        ON i.id_producto = p.id_producto

    LEFT JOIN imagenes_producto img
        ON img.id_producto = p.id_producto
        AND img.principal = 1
        AND img.estado = 1

    WHERE p.estado = 1
      AND c.estado = 1
      AND i.stock_actual > 0

    ORDER BY
        p.destacado DESC,
        p.fecha_creacion DESC
");

if ($stmtProductos) {

    $stmtProductos->execute();

    $resultadoProductos = $stmtProductos->get_result();

    while ($producto = $resultadoProductos->fetch_assoc()) {
        $productosCatalogo[] = $producto;
    }

    $stmtProductos->close();
}


// =================================
// BÚSQUEDA
// =================================
// La búsqueda se realiza sobre los productos
// obtenidos desde la base de datos.
//
// No confiamos en datos enviados por el navegador
// para mostrar información directamente.

if ($busqueda !== "") {

    $textoBusqueda = mb_strtolower($busqueda);

    $productosCatalogo = array_values(
        array_filter(
            $productosCatalogo,
            function ($producto) use ($textoBusqueda) {

                $nombre = mb_strtolower($producto["nombre"] ?? "");
                $codigo = mb_strtolower($producto["codigo_producto"] ?? "");
                $categoria = mb_strtolower($producto["categoria_nombre"] ?? "");
                $marca = mb_strtolower($producto["marca_nombre"] ?? "");

                return
                    mb_strpos($nombre, $textoBusqueda) !== false ||
                    mb_strpos($codigo, $textoBusqueda) !== false ||
                    mb_strpos($categoria, $textoBusqueda) !== false ||
                    mb_strpos($marca, $textoBusqueda) !== false;
            }
        )
    );
}


// =================================
// PRODUCTO SELECCIONADO
// =================================
//
// Si llegamos desde un producto destacado,
// colocamos ese producto como el primero
// del catálogo.

if ($productoId !== null) {

    foreach ($productosCatalogo as $indice => $producto) {

        if ((int) $producto["id_producto"] === (int) $productoId) {

            $productoSeleccionado = $producto;

            unset($productosCatalogo[$indice]);

            array_unshift($productosCatalogo, $productoSeleccionado);

            break;
        }
    }

    // Reindexamos el arreglo después de modificarlo.
    $productosCatalogo = array_values($productosCatalogo);
}
// =================================
// ORGANIZAR PRODUCTOS
// =================================
//
// Si existe una categoría seleccionada:
//
// 1. Primero aparecen los productos de esa categoría.
// 2. Después aparecen los demás productos.
//
// Esto permite mantener un único catálogo visual,
// sin separar la página en diferentes secciones.
//
// Si no hay categoría seleccionada,
// simplemente se muestra todo el catálogo.

// =================================

$productosCategoria = [];
$otrosProductos = [];

if ($categoriaId !== null) {

    foreach ($productosCatalogo as $producto) {

        if ((int) $producto["id_categoria"] === (int) $categoriaId) {

            $productosCategoria[] = $producto;

        } else {

            $otrosProductos[] = $producto;
        }
    }

} else {

    // Sin categoría seleccionada,
    // todos los productos pertenecen al catálogo general.
    $otrosProductos = $productosCatalogo;
}


// =================================
// FUNCIÓN PARA MOSTRAR PRODUCTOS
// =================================

function mostrarProductoCard($producto, $base_url)
{
    ?>

    <article class="producto-card">

        <a
            href="<?= $base_url ?>productos/detalle.php?id=<?= (int) $producto["id_producto"] ?>"
            class="producto-imagen producto-enlace-detalle"
            data-producto-id="<?= (int) $producto["id_producto"] ?>"
        >

            <?php if (!empty($producto["ruta_imagen"])): ?>

                <img
                    src="<?= $base_url ?>../<?= htmlspecialchars($producto["ruta_imagen"], ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                    loading="lazy"
                >

            <?php else: ?>

                <div class="producto-sin-imagen">
                    <span>Sin imagen</span>
                </div>

            <?php endif; ?>

        </a>


        <div class="producto-contenido">

            <?php if (!empty($producto["codigo_producto"])): ?>

                <span class="producto-codigo">
                    <?= htmlspecialchars($producto["codigo_producto"], ENT_QUOTES, "UTF-8") ?>
                </span>

            <?php endif; ?>


            <h3>

                <a
                    href="<?= $base_url ?>productos/detalle.php?id=<?= (int) $producto["id_producto"] ?>"
                    class="producto-enlace-detalle"
                    data-producto-id="<?= (int) $producto["id_producto"] ?>"
                >
                    <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>
                </a>

            </h3>


            <?php if (!empty($producto["categoria_nombre"])): ?>

                <span class="producto-categoria">
                    <?= htmlspecialchars($producto["categoria_nombre"], ENT_QUOTES, "UTF-8") ?>
                </span>

            <?php endif; ?>


            <?php if (!empty($producto["marca_nombre"])): ?>

                <span class="producto-marca">
                    <?= htmlspecialchars($producto["marca_nombre"], ENT_QUOTES, "UTF-8") ?>
                </span>

            <?php endif; ?>


            <p class="producto-precio">
                $<?= number_format((float) $producto["precio"], 0, ",", ".") ?>
            </p>


            <a
                href="<?= $base_url ?>productos/detalle.php?id=<?= (int) $producto["id_producto"] ?>"
                class="producto-boton producto-enlace-detalle"
                data-producto-id="<?= (int) $producto["id_producto"] ?>"
            >
                Ver producto
            </a>

        </div>

    </article>

    <?php
}

?>

<main class="productos-main">

    <!-- =================================
         ENCABEZADO DEL CATÁLOGO
         ================================= -->

    <section class="productos-encabezado">

        <div class="productos-container">

            <span class="productos-etiqueta">
                CATÁLOGO
            </span>


            <?php if ($categoriaSeleccionada): ?>

                <h1>
                    <?= htmlspecialchars($categoriaSeleccionada["nombre"], ENT_QUOTES, "UTF-8") ?>
                </h1>

                <?php if (!empty($categoriaSeleccionada["descripcion"])): ?>

                    <p>
                        <?= htmlspecialchars($categoriaSeleccionada["descripcion"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                <?php endif; ?>


            <?php elseif ($busqueda !== ""): ?>

                <h1>
                    Resultados de búsqueda
                </h1>

                <p>
                    Resultados para:
                    <strong>
                        <?= htmlspecialchars($busqueda, ENT_QUOTES, "UTF-8") ?>
                    </strong>
                </p>


            <?php else: ?>

                <h1>
                    Todos los productos
                </h1>

                <p>
                    Encuentra los repuestos agrícolas disponibles en AGRANDA.
                </p>

            <?php endif; ?>

        </div>

    </section>


    <!-- =================================
         CATÁLOGO UNIFICADO
         ================================= -->

    <section class="productos-seccion productos-seccion-general">

        <div class="productos-container">

            <div class="catalogo-layout">


                <!-- =================================
                     FILTRO DE CATEGORÍAS
                     ================================= -->

                <aside class="productos-filtros">

                    <div class="filtros-contenido">

                        <span class="productos-etiqueta">
                            FILTRAR
                        </span>

                        <h2>
                            Categorías
                        </h2>

                        <p class="filtros-descripcion">
                            Explora los productos por categoría.
                        </p>


                        <div class="filtros-lista">

                            <!-- TODAS LAS CATEGORÍAS -->

                            <a
                                href="<?= $base_url ?>productos/"
                                class="filtro-categoria <?= $categoriaId === null ? "filtro-activo" : "" ?>"
                            >

                                <span class="filtro-radio">
                                    <?= $categoriaId === null ? "✓" : "" ?>
                                </span>

                                <span>
                                    Todas las categorías
                                </span>

                            </a>


                            <!-- CATEGORÍAS ACTIVAS -->

                            <?php foreach ($categoriasFiltro as $categoria): ?>

                                <a
                                    href="<?= $base_url ?>productos/?categoria=<?= (int) $categoria["id_categoria"] ?>"
                                    class="filtro-categoria <?= $categoriaId === (int) $categoria["id_categoria"] ? "filtro-activo" : "" ?>"
                                >

                                    <span class="filtro-radio">
                                        <?= $categoriaId === (int) $categoria["id_categoria"] ? "✓" : "" ?>
                                    </span>

                                    <span>
                                        <?= htmlspecialchars($categoria["nombre"], ENT_QUOTES, "UTF-8") ?>
                                    </span>

                                </a>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </aside>


                <!-- =================================
                     CONTENIDO DEL CATÁLOGO
                     ================================= -->

                <div class="catalogo-productos">


                    <!-- =================================
                         ENCABEZADO DE PRODUCTOS
                         ================================= -->

                    <div class="productos-seccion-encabezado">

                        <div>

                            <span class="productos-etiqueta">
                                PRODUCTOS
                            </span>


                            <?php if ($categoriaSeleccionada): ?>

                                <h2>
                                    <?= htmlspecialchars($categoriaSeleccionada["nombre"], ENT_QUOTES, "UTF-8") ?>
                                </h2>


                            <?php elseif ($busqueda !== ""): ?>

                                <h2>
                                    Productos encontrados
                                </h2>


                            <?php else: ?>

                                <h2>
                                    Productos disponibles
                                </h2>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- =================================
                         PRODUCTOS DE LA CATEGORÍA
                         ================================= -->

                    <?php if ($categoriaSeleccionada && !empty($productosCategoria)): ?>

                        <div class="productos-grid">

                            <?php foreach ($productosCategoria as $producto): ?>

                                <?php mostrarProductoCard($producto, $base_url); ?>

                            <?php endforeach; ?>

                        </div>


                    <?php elseif ($categoriaSeleccionada && empty($productosCategoria)): ?>

                        <div class="productos-vacio">

                            <h3>
                                No encontramos productos
                            </h3>

                            <p>
                                Actualmente no hay productos disponibles
                                en esta categoría.
                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- =================================
                         RESTO DEL CATÁLOGO
                         ================================= -->

                    <?php if ($categoriaSeleccionada && !empty($otrosProductos)): ?>

                        <div class="catalogo-mas-productos">

                            <span class="productos-etiqueta">
                                CATÁLOGO
                            </span>

                            <h3>
                                Más productos
                            </h3>

                            <p>
                                Explora también otros repuestos disponibles
                                en AGRANDA.
                            </p>

                        </div>


                        <div class="productos-grid">

                            <?php foreach ($otrosProductos as $producto): ?>

                                <?php mostrarProductoCard($producto, $base_url); ?>

                            <?php endforeach; ?>

                        </div>


                    <?php elseif (!$categoriaSeleccionada && !empty($otrosProductos)): ?>

                        <!-- =================================
                             CATÁLOGO GENERAL
                             ================================= -->

                        <div class="productos-grid">

                            <?php foreach ($otrosProductos as $producto): ?>

                                <?php mostrarProductoCard($producto, $base_url); ?>

                            <?php endforeach; ?>

                        </div>


                    <?php elseif (!$categoriaSeleccionada && empty($otrosProductos)): ?>

                        <div class="productos-vacio">

                            <h3>
                                No encontramos productos
                            </h3>

                            <p>
                                Intenta realizar otra búsqueda o
                                explora otra categoría.
                            </p>

                            <a
                                href="<?= $base_url ?>productos/"
                                class="producto-boton"
                            >
                                Ver todos los productos
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================
         VOLVER AL INICIO
         ================================= -->

    <section class="productos-final">

        <div class="productos-container">

            <a
                href="<?= $base_url ?>"
                class="btn-secundario"
            >
                Volver al inicio
            </a>

        </div>

    </section>

</main>

<!-- =================================
     MODAL DETALLE DEL PRODUCTO
     ================================= -->

<div
    id="modalProducto"
    class="modal-producto"
    aria-hidden="true"
>

    <div class="modal-producto-overlay"></div>


    <div
        class="modal-producto-contenedor"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalProductoNombre"
    >

        <!-- BOTÓN CERRAR -->

        <button
            type="button"
            class="modal-producto-cerrar"
            id="cerrarModalProducto"
            aria-label="Cerrar detalle del producto"
        >
            ×
        </button>


        <!-- CONTENIDO -->

        <div class="modal-producto-contenido">

            <!-- IMAGEN -->

            <div class="modal-producto-imagen">

                <img
                    id="modalProductoImagen"
                    src=""
                    alt=""
                >

                <div
                    id="modalProductoSinImagen"
                    class="modal-producto-sin-imagen"
                    hidden
                >
                    Sin imagen
                </div>

            </div>


            <!-- INFORMACIÓN -->

            <div class="modal-producto-info">

                <span
                    id="modalProductoCategoria"
                    class="modal-producto-categoria"
                ></span>


                <h2 id="modalProductoNombre">
                    Producto
                </h2>


                <span
                    id="modalProductoCodigo"
                    class="modal-producto-codigo"
                ></span>


                <p
                    id="modalProductoDescripcion"
                    class="modal-producto-descripcion"
                ></p>


                <!-- CARACTERÍSTICAS -->

                <div
                    id="modalProductoCaracteristicas"
                    class="modal-producto-caracteristicas"
                    hidden
                >

                    <h3>
                        Características
                    </h3>

                    <div
                        id="listaCaracteristicas"
                        class="lista-caracteristicas"
                    ></div>

                </div>


                <!-- DATOS -->

                <div class="modal-producto-datos">

                    <div class="modal-dato">

                        <span>
                            Marca
                        </span>

                        <strong id="modalProductoMarca">
                            —
                        </strong>

                    </div>


                    <div class="modal-dato">

                        <span>
                            Peso
                        </span>

                        <strong id="modalProductoPeso">
                            —
                        </strong>

                    </div>


                    <div class="modal-dato">

                        <span>
                            Disponibilidad
                        </span>

                        <strong id="modalProductoStock">
                            —
                        </strong>

                    </div>

                </div>


                <!-- PRECIO -->

                <div class="modal-producto-precio">

                    <span>
                        Precio
                    </span>

                    <strong id="modalProductoPrecio">
                        $0
                    </strong>

                </div>


                <!-- ACCIONES -->

                <div class="modal-producto-acciones">

                    <button
                        type="button"
                        class="modal-btn-carrito"
                        id="modalBtnCarrito"
                        disabled
                    >
                        Agregar al carrito
                    </button>


                    <button
                        type="button"
                        class="modal-btn-favorito"
                        id="modalBtnFavorito"
                        disabled
                    >
                        ♡
                    </button>

                </div>


                <!-- ESTADO -->

                <p
                    id="modalProductoMensaje"
                    class="modal-producto-mensaje"
                    aria-live="polite"
                ></p>

            </div>

        </div>

    </div>

</div>

<?php

// =================================
// FOOTER
// =================================
require_once("../includes/footer.php");

?>