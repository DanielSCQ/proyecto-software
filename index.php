<?php
// Cargar conexión de base de datos
require_once(__DIR__ . "/config/conexion.php");
require_once(__DIR__ . "/includes/helpers.php");

// 1. Obtener Categorías Activas
$sqlCategorias = "SELECT id_categoria, nombre, descripcion, imagen 
                  FROM categorias 
                  WHERE estado = 1 
                  ORDER BY nombre ASC";
$resCategorias = $conexion->query($sqlCategorias);

// 2. Obtener Productos Destacados con Imagen Principal, Marca, Stock y Promociones Activas
$sqlDestacados = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.codigo_producto,
                    p.precio,
                    p.destacado,
                    c.nombre AS categoria,
                    COALESCE(m.nombre, 'Sin Marca') AS marca,
                    COALESCE(i.stock_actual, 0) AS stock_actual,
                    img.ruta_imagen,
                    pr.id_promocion,
                    pr.nombre AS nombre_promocion,
                    pr.tipo AS tipo_promocion,
                    pr.valor_descuento,
                    CASE 
                        WHEN pr.tipo = 'Porcentaje' THEN ROUND(p.precio - (p.precio * (pr.valor_descuento / 100)), 2)
                        WHEN pr.tipo = 'Fijo' THEN GREATEST(0, p.precio - pr.valor_descuento)
                        ELSE p.precio
                    END AS precio_final
                  FROM productos p
                  INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                  LEFT JOIN marcas m ON p.id_marca = m.id_marca
                  LEFT JOIN inventario i ON p.id_producto = i.id_producto
                  LEFT JOIN imagenes_producto img ON p.id_producto = img.id_producto AND img.principal = 1 AND img.estado = 1
                  LEFT JOIN promociones pr ON pr.id_promocion = (
                      SELECT pp2.id_promocion
                      FROM promocion_producto pp2
                      INNER JOIN promociones pr2 ON pp2.id_promocion = pr2.id_promocion
                      WHERE pp2.id_producto = p.id_producto
                        AND pr2.estado = 1
                        AND CURDATE() BETWEEN pr2.fecha_inicio AND pr2.fecha_fin
                      LIMIT 1
                  )
                  WHERE p.estado = 1 AND p.destacado = 1
                  ORDER BY p.id_producto DESC
                  LIMIT 8";
$resDestacados = $conexion->query($sqlDestacados);

// Incluir Cabecera Modular
require_once(__DIR__ . "/includes/header.php");
?>

<main class="estilom">

    <!-- Banner Principal / Hero -->
    <section class="hero">
        <h2>Bienvenidos a <?php echo htmlspecialchars($configTienda['nombre_tienda'] ?? 'AGRANDA'); ?></h2>
        <p><?php echo htmlspecialchars($configTienda['eslogan'] ?? 'Los mejores repuestos agrícolas al mejor precio'); ?></p>
        <div style="margin-top: 15px;">
            <a href="productos.php" style="display: inline-block; background: orangered; color: white; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">Ver Catálogo Completo</a>
        </div>
    </section>

    <!-- Sección de Categorías Dinámicas -->
    <section class="productos">
        <h3 class="fade" style="letter-spacing: 1px; color: #333; font-size: 1.6rem; font-weight: bold;">CATEGORÍAS DESTACADAS</h3>
        
        <div class="grid">
            <?php if ($resCategorias && $resCategorias->num_rows > 0): ?>
                <?php while ($cat = $resCategorias->fetch_assoc()): ?>
                    <?php 
                        $imgCat = obtenerRutaImagen($cat['imagen'] ?? '', 'categoria');
                    ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($imgCat); ?>" alt="<?php echo htmlspecialchars($cat['nombre']); ?>" loading="lazy">
                        <h4 style="margin-top: 12px; font-size: 1.15rem; color: #222;"><?php echo htmlspecialchars($cat['nombre']); ?></h4>
                        <?php if (!empty($cat['descripcion'])): ?>
                            <p style="color: #666; font-size: 0.85rem; margin: 6px 0 10px;"><?php echo htmlspecialchars(mb_strimwidth($cat['descripcion'], 0, 60, '...')); ?></p>
                        <?php endif; ?>
                        <a href="productos.php?cat=<?php echo (int)$cat['id_categoria']; ?>">
                            <button type="button">Explorar Categoría</button>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Fallback visual si no hay categorías registradas aún -->
                <div class="card">
                    <img src="destacado 1.jfif" alt="Repuestos de maquinaria">
                    <h4>Repuestos de Maquinaria</h4>
                    <a href="productos.php"><button type="button">Explorar</button></a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección de Productos Destacados -->
    <section class="productos" style="background: #ffffff; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; padding: 45px 20px;">
        <h3 style="letter-spacing: 1px; color: #1f5f28; font-size: 1.6rem; font-weight: bold; margin-bottom: 25px;">PRODUCTOS DESTACADOS</h3>
        
        <div class="grid">
            <?php if ($resDestacados && $resDestacados->num_rows > 0): ?>
                <?php while ($prod = $resDestacados->fetch_assoc()): ?>
                    <?php 
                        $imgProd = obtenerRutaImagen($prod['ruta_imagen'] ?? '', 'producto');
                        $tienePromo = !empty($prod['id_promocion']) && $prod['precio_final'] < $prod['precio'];
                        $stockDisponible = (int)$prod['stock_actual'];
                        $agotado = $stockDisponible <= 0;
                    ?>
                    <div class="card producto-card" style="position: relative; display: flex; flex-direction: column; justify-content: space-between;">
                        
                        <?php if ($tienePromo): ?>
                            <span style="position: absolute; top: 10px; left: 10px; background: #e53935; color: white; padding: 4px 8px; font-size: 0.75rem; font-weight: bold; border-radius: 4px; z-index: 2;">
                                <?php echo $prod['tipo_promocion'] === 'Porcentaje' ? '-' . round($prod['valor_descuento']) . '%' : 'OFERTA'; ?>
                            </span>
                        <?php endif; ?>

                        <div>
                            <img src="<?php echo htmlspecialchars($imgProd); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" loading="lazy">
                            <span style="display: block; font-size: 0.75rem; color: #888; text-transform: uppercase; margin-top: 8px; font-weight: bold;">
                                <?php echo htmlspecialchars($prod['categoria']); ?> · <?php echo htmlspecialchars($prod['marca']); ?>
                            </span>
                            <h4 style="margin: 6px 0; font-size: 1.1rem; color: #222;"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
                            <p style="color: #777; font-size: 0.8rem; margin-bottom: 8px;">Cód: <?php echo htmlspecialchars($prod['codigo_producto']); ?></p>
                        </div>

                        <div style="margin-top: 10px;">
                            <div style="margin-bottom: 8px;">
                                <?php if ($tienePromo): ?>
                                    <span style="text-decoration: line-through; color: #999; font-size: 0.9rem; margin-right: 5px;">
                                        <?php echo formatearPrecio($prod['precio'], $configTienda['moneda'] ?? '$'); ?>
                                    </span>
                                    <span style="color: #e53935; font-size: 1.25rem; font-weight: bold;">
                                        <?php echo formatearPrecio($prod['precio_final'], $configTienda['moneda'] ?? '$'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #2e7d32; font-size: 1.25rem; font-weight: bold;">
                                        <?php echo formatearPrecio($prod['precio'], $configTienda['moneda'] ?? '$'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div style="font-size: 0.8rem; margin-bottom: 10px; font-weight: bold; color: <?php echo $agotado ? '#d32f2f' : '#2e7d32'; ?>;">
                                <?php echo $agotado ? '⚠️ Agotado' : '✓ Stock: ' . $stockDisponible . ' unidades'; ?>
                            </div>

                            <?php if (!$agotado): ?>
                                <button type="button" 
                                        class="btn-agregar-carrito" 
                                        data-id="<?php echo (int)$prod['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                        data-precio="<?php echo (float)$prod['precio_final']; ?>"
                                        data-imagen="<?php echo htmlspecialchars($imgProd); ?>"
                                        style="width: 100%;">
                                    🛒 Agregar al Carrito
                                </button>
                            <?php else: ?>
                                <button type="button" disabled style="width: 100%; background: #ccc; cursor: not-allowed; color: #666;">
                                    Sin Stock
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; color: #777; font-style: italic;">No hay productos destacados activos en este momento.</p>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php
// Incluir Pie de Página Modular
require_once(__DIR__ . "/includes/footer.php");
?>
