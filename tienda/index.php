<?php

// =================================
// CONEXIÓN Y HEADER
// =================================
require_once("includes/header.php");

?>

<link rel="stylesheet" href="<?= $base_url ?>css/inicio.css">

<?php

// =================================
// CATEGORÍAS ACTIVAS
// =================================
$categorias = [];

$sqlCategorias = "
    SELECT
        id_categoria,
        nombre,
        descripcion,
        imagen
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoCategorias = $conexion->query($sqlCategorias);

if ($resultadoCategorias) {
    while ($categoria = $resultadoCategorias->fetch_assoc()) {
        $categorias[] = $categoria;
    }
}


// =================================
// PRODUCTOS DESTACADOS
// =================================
$productosDestacados = [];

$sqlProductos = "
    SELECT
        p.id_producto,
        p.nombre,
        p.descripcion,
        p.codigo_producto,
        p.precio,
        img.ruta_imagen
    FROM productos p

    LEFT JOIN imagenes_producto img
        ON img.id_producto = p.id_producto
        AND img.principal = 1
        AND img.estado = 1

    INNER JOIN inventario i
        ON i.id_producto = p.id_producto

    WHERE p.estado = 1
      AND p.destacado = 1
      AND i.stock_actual > 0

    ORDER BY p.fecha_creacion DESC
    LIMIT 8
";

$resultadoProductos = $conexion->query($sqlProductos);

if ($resultadoProductos) {
    while ($producto = $resultadoProductos->fetch_assoc()) {
        $productosDestacados[] = $producto;
    }
}

?>

<main>

    <!-- =================================
         HERO
         ================================= -->

    <section class="inicio-hero">

        <div class="inicio-container">

            <div class="hero-contenido">

                <span class="hero-etiqueta">
                    REPUESTOS AGRÍCOLAS
                </span>

                <h1>
                    Repuestos para mantener
                    tu maquinaria en marcha.
                </h1>

                <p>
                    Encuentra repuestos agrícolas de calidad
                    para mantener tus equipos funcionando
                    cuando más los necesitas.
                </p>

                <a href="<?= $base_url ?>productos/" class="btn-hero">
                    Ver productos
                </a>

            </div>

        </div>

    </section>


    <!-- =================================
         CATEGORÍAS
         ================================= -->

    <section class="inicio-seccion">

        <div class="inicio-container">

            <div class="seccion-encabezado">

                <span class="seccion-etiqueta">
                    EXPLORA
                </span>

                <h2>
                    Encuentra lo que necesitas
                </h2>

                <p>
                    Explora nuestras categorías de repuestos agrícolas.
                </p>

            </div>


            <?php if (!empty($categorias)): ?>

                <div class="categorias-grid">

                    <?php foreach ($categorias as $categoria): ?>

                        <a
                            href="<?= $base_url ?>productos/?categoria=<?= (int) $categoria["id_categoria"] ?>"
                            class="categoria-card"
                        >

                            <?php if (!empty($categoria["imagen"])): ?>

                                <div class="categoria-imagen">

                                    <img
                                        src="<?= $base_url ?>../<?= htmlspecialchars($categoria["imagen"], ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($categoria["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                    >

                                </div>

                            <?php else: ?>

                                <div class="categoria-imagen categoria-sin-imagen">
                                    <span>AGRANDA</span>
                                </div>

                            <?php endif; ?>


                            <div class="categoria-contenido">

                                <h3>
                                    <?= htmlspecialchars($categoria["nombre"], ENT_QUOTES, "UTF-8") ?>
                                </h3>

                                <?php if (!empty($categoria["descripcion"])): ?>

                                    <p>
                                        <?= htmlspecialchars($categoria["descripcion"], ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                <?php endif; ?>

                                <span class="categoria-enlace">
                                    Ver productos →
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="inicio-vacio">

                    <p>
                        Aún no hay categorías disponibles.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =================================
         PRODUCTOS DESTACADOS
         ================================= -->

    <section class="inicio-seccion productos-seccion">

        <div class="inicio-container">

            <div class="seccion-encabezado">

                <span class="seccion-etiqueta">
                    DESTACADOS
                </span>

                <h2>
                    Productos destacados
                </h2>

                <p>
                    Algunos de nuestros repuestos disponibles.
                </p>

            </div>


            <?php if (!empty($productosDestacados)): ?>

                <div class="productos-grid">

                    <?php foreach ($productosDestacados as $producto): ?>

                        <article class="producto-card">

                            <a
                                href="<?= $base_url ?>productos/?producto=<?= (int) $producto["id_producto"] ?>"
                                class="producto-imagen"
                            >

                                <?php if (!empty($producto["ruta_imagen"])): ?>

                                    <img
                                        src="<?= $base_url ?>../<?= htmlspecialchars($producto["ruta_imagen"], ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                    >

                                <?php else: ?>

                                    <div class="producto-sin-imagen">
                                        <span>Sin imagen</span>
                                    </div>

                                <?php endif; ?>

                            </a>


                            <div class="producto-contenido">

                                <span class="producto-codigo">
                                    <?= htmlspecialchars($producto["codigo_producto"], ENT_QUOTES, "UTF-8") ?>
                                </span>

                                <h3>
                                    <a href="<?= $base_url ?>productos/?producto=<?= (int) $producto["id_producto"] ?>">
                                        <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>
                                    </a>
                                </h3>

                                <p class="producto-precio">
                                    $<?= number_format((float) $producto["precio"], 0, ",", ".") ?>
                                </p>

                                <a
                                    href="<?= $base_url ?>productos/?producto=<?= (int) $producto["id_producto"] ?>"
                                    class="producto-boton"
                                >
                                    Ver producto
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="inicio-vacio">

                    <p>
                        Aún no hay productos destacados disponibles.
                    </p>

                </div>

            <?php endif; ?>


            <div class="seccion-boton">

                <a href="<?= $base_url ?>productos/" class="btn-secundario">
                    Ver todos los productos
                </a>

            </div>

        </div>

    </section>


    <!-- =================================
         POR QUÉ AGRANDA
         ================================= -->

    <section class="inicio-confianza">

        <div class="inicio-container">

            <div class="seccion-encabezado">

                <span class="seccion-etiqueta">
                    AGRANDA
                </span>

                <h2>
                    ¿Por qué elegirnos?
                </h2>

            </div>


            <div class="confianza-grid">

                <div class="confianza-item">

                    <div class="confianza-icono">
                        ✓
                    </div>

                    <h3>
                        Repuestos agrícolas
                    </h3>

                    <p>
                        Productos pensados para las necesidades
                        de la maquinaria agrícola.
                    </p>

                </div>


                <div class="confianza-item">

                    <div class="confianza-icono">
                        ✓
                    </div>

                    <h3>
                        Compra sencilla
                    </h3>

                    <p>
                        Encuentra tus productos y realiza tu
                        pedido de manera fácil y rápida.
                    </p>

                </div>


                <div class="confianza-item">

                    <div class="confianza-icono">
                        ✓
                    </div>

                    <h3>
                        Atención al cliente
                    </h3>

                    <p>
                        Estamos disponibles para ayudarte con
                        tus productos y pedidos.
                    </p>

                </div>

            </div>

        </div>

    </section>

</main>


<?php

// =================================
// FOOTER
// =================================
require_once("includes/footer.php");

?>