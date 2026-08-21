<?php
// productos.php - Catálogo dinámico de productos AGRANDA (Fase 2)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

// 1. Obtener filtros y búsqueda sanitizados
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$idCategoria = isset($_GET['cat']) ? (int)$_GET['cat'] : (isset($_GET['id_categoria']) ? (int)$_GET['id_categoria'] : 0);
$idMarca = isset($_GET['marca']) ? (int)$_GET['marca'] : (isset($_GET['id_marca']) ? (int)$_GET['id_marca'] : 0);
$orden = isset($_GET['orden']) ? trim($_GET['orden']) : 'recientes';

// 2. Obtener listas para filtros dinámicos (Categorías y Marcas activas)
$listaCategorias = [];
$resCat = $conexion->query("SELECT id_categoria, nombre, (SELECT COUNT(*) FROM productos p WHERE p.id_categoria = categorias.id_categoria AND p.estado = 1) AS total_prod FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
if ($resCat) {
    while ($row = $resCat->fetch_assoc()) {
        $listaCategorias[] = $row;
    }
}

$listaMarcas = [];
$resMarcas = $conexion->query("SELECT id_marca, nombre, (SELECT COUNT(*) FROM productos p WHERE p.id_marca = marcas.id_marca AND p.estado = 1) AS total_prod FROM marcas WHERE estado = 1 ORDER BY nombre ASC");
if ($resMarcas) {
    while ($row = $resMarcas->fetch_assoc()) {
        $listaMarcas[] = $row;
    }
}

// 3. Construir consulta SQL con prepared statements dinámicos
$whereClauses = ["p.estado = 1"];
$params = [];
$types = "";

if ($idCategoria > 0) {
    $whereClauses[] = "p.id_categoria = ?";
    $params[] = $idCategoria;
    $types .= "i";
}

if ($idMarca > 0) {
    $whereClauses[] = "p.id_marca = ?";
    $params[] = $idMarca;
    $types .= "i";
}

if (!empty($busqueda)) {
    $whereClauses[] = "(p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo_producto LIKE ? OR c.nombre LIKE ? OR m.nombre LIKE ?)";
    $likeStr = "%" . $busqueda . "%";
    $params[] = $likeStr;
    $params[] = $likeStr;
    $params[] = $likeStr;
    $params[] = $likeStr;
    $params[] = $likeStr;
    $types .= "sssss";
}

$whereSql = implode(" AND ", $whereClauses);

// Ordenamiento
$orderSql = "ORDER BY p.id_producto DESC";
if ($orden === 'precio_menor') {
    $orderSql = "ORDER BY p.precio ASC";
} elseif ($orden === 'precio_mayor') {
    $orderSql = "ORDER BY p.precio DESC";
} elseif ($orden === 'nombre_asc') {
    $orderSql = "ORDER BY p.nombre ASC";
}

$sqlProductos = "SELECT 
                    p.id_producto,
                    p.id_categoria,
                    p.id_marca,
                    p.nombre,
                    p.descripcion,
                    p.codigo_producto,
                    p.precio,
                    p.peso,
                    p.destacado,
                    c.nombre AS categoria,
                    COALESCE(m.nombre, 'Genérica') AS marca,
                    COALESCE(i.stock_actual, 0) AS stock_actual,
                    img.ruta_imagen AS imagen_principal,
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
                WHERE {$whereSql}
                {$orderSql}";

$stmt = $conexion->prepare($sqlProductos);
$productos = [];

if ($stmt) {
    if (!empty($types) && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado) {
        while ($prod = $resultado->fetch_assoc()) {
            $productos[] = $prod;
        }
    }
}

// Título de la página
$tituloHeader = "Catálogo de Repuestos y Productos";
if ($idCategoria > 0) {
    foreach ($listaCategorias as $cat) {
        if ($cat['id_categoria'] == $idCategoria) {
            $tituloHeader = "Categoría: " . $cat['nombre'];
            break;
        }
    }
}

include('includes/header.php');
?>

<main class="productos-catalogo-wrapper">
    <!-- Header del Catálogo -->
    <section class="catalogo-hero">
        <div class="catalogo-hero-content">
            <span class="catalogo-badge">🌾 Catálogo Especializado</span>
            <h2><?php echo htmlspecialchars($tituloHeader); ?></h2>
            <p>Repuestos originales, maquinaria y accesorios agrícolas de alto rendimiento con garantía directa.</p>
        </div>
    </section>

    <!-- Barra de Búsqueda y Filtros Combinados -->
    <section class="catalogo-filtros-bar">
        <form method="GET" action="productos.php" class="filtros-form" id="filtrosCatalogoForm">
            <div class="filtros-grid">
                <!-- Buscador de texto -->
                <div class="filtro-item filtro-busqueda">
                    <label for="inputBusqueda">🔍 Buscar por nombre, código o parte:</label>
                    <div class="input-with-action">
                        <input type="text" 
                               id="inputBusqueda" 
                               name="busqueda" 
                               placeholder="Ej: Cigüeñal, Bomba, 223878..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                        <?php if (!empty($busqueda)): ?>
                            <a href="productos.php?<?php echo http_build_query(array_filter(['cat' => $idCategoria, 'marca' => $idMarca, 'orden' => $orden])); ?>" 
                               class="btn-clear-search" 
                               title="Borrar búsqueda">✕</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtro por Categoría -->
                <div class="filtro-item">
                    <label for="selectCategoria">📂 Categoría:</label>
                    <select id="selectCategoria" name="cat" onchange="document.getElementById('filtrosCatalogoForm').submit();">
                        <option value="0">Todas las categorías</option>
                        <?php foreach ($listaCategorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $idCategoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['total_prod']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Marca -->
                <div class="filtro-item">
                    <label for="selectMarca">🚜 Marca:</label>
                    <select id="selectMarca" name="marca" onchange="document.getElementById('filtrosCatalogoForm').submit();">
                        <option value="0">Todas las marcas</option>
                        <?php foreach ($listaMarcas as $m): ?>
                            <option value="<?php echo $m['id_marca']; ?>" <?php echo $idMarca == $m['id_marca'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['nombre']); ?> (<?php echo $m['total_prod']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ordenamiento -->
                <div class="filtro-item">
                    <label for="selectOrden">↕️ Ordenar por:</label>
                    <select id="selectOrden" name="orden" onchange="document.getElementById('filtrosCatalogoForm').submit();">
                        <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                        <option value="precio_menor" <?php echo $orden === 'precio_menor' ? 'selected' : ''; ?>>Menor precio</option>
                        <option value="precio_mayor" <?php echo $orden === 'precio_mayor' ? 'selected' : ''; ?>>Mayor precio</option>
                        <option value="nombre_asc" <?php echo $orden === 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="filtro-item filtro-acciones">
                    <label>&nbsp;</label>
                    <div class="botones-filtro-flex">
                        <button type="submit" class="btn-filtrar">Filtrar</button>
                        <?php if (!empty($busqueda) || $idCategoria > 0 || $idMarca > 0 || $orden !== 'recientes'): ?>
                            <a href="productos.php" class="btn-limpiar-todo" title="Restablecer todos los filtros">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <!-- Filtros Rápidos / Pills Activos -->
        <div class="active-filters-row">
            <span class="results-count">
                Mostrando <strong><?php echo count($productos); ?></strong> <?php echo count($productos) === 1 ? 'producto' : 'productos'; ?>
            </span>
            
            <?php if (!empty($busqueda) || $idCategoria > 0 || $idMarca > 0): ?>
                <div class="chips-list">
                    <?php if (!empty($busqueda)): ?>
                        <span class="filter-chip">
                            Búsqueda: "<?php echo htmlspecialchars($busqueda); ?>"
                            <a href="productos.php?<?php echo http_build_query(array_filter(['cat' => $idCategoria, 'marca' => $idMarca, 'orden' => $orden])); ?>">✕</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($idCategoria > 0): ?>
                        <span class="filter-chip">
                            Categoría: <?php 
                                foreach ($listaCategorias as $c) { 
                                    if ($c['id_categoria'] == $idCategoria) echo htmlspecialchars($c['nombre']); 
                                } 
                            ?>
                            <a href="productos.php?<?php echo http_build_query(array_filter(['busqueda' => $busqueda, 'marca' => $idMarca, 'orden' => $orden])); ?>">✕</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($idMarca > 0): ?>
                        <span class="filter-chip">
                            Marca: <?php 
                                foreach ($listaMarcas as $m) { 
                                    if ($m['id_marca'] == $idMarca) echo htmlspecialchars($m['nombre']); 
                                } 
                            ?>
                            <a href="productos.php?<?php echo http_build_query(array_filter(['busqueda' => $busqueda, 'cat' => $idCategoria, 'orden' => $orden])); ?>">✕</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Grilla Dinámica de Productos -->
    <section class="catalogo-grid-container">
        <?php if (empty($productos)): ?>
            <div class="catalogo-vacio">
                <div class="icono-vacio">🚜</div>
                <h3>No se encontraron productos disponibles</h3>
                <p>No hay coincidencias con los criterios de búsqueda o filtros seleccionados. Intenta con otros términos o limpia los filtros.</p>
                <a href="productos.php" class="btn-restablecer">Ver todo el catálogo</a>
            </div>
        <?php else: ?>
            <div class="grid-productos-modern">
                <?php foreach ($productos as $prod): ?>
                    <?php 
                        $imgSrc = obtenerRutaImagen($prod['imagen_principal'], 'producto');
                        $stock = (int)$prod['stock_actual'];
                        $agotado = ($stock <= 0);
                        $tienePromo = !empty($prod['id_promocion']) && ((float)$prod['precio_final'] < (float)$prod['precio']);
                        $descuentoPorcentaje = 0;
                        if ($tienePromo) {
                            if ($prod['tipo_promocion'] === 'Porcentaje') {
                                $descuentoPorcentaje = (float)$prod['valor_descuento'];
                            } else {
                                $descuentoPorcentaje = round((((float)$prod['precio'] - (float)$prod['precio_final']) / (float)$prod['precio']) * 100);
                            }
                        }
                    ?>
                    <article class="card-producto-agranda <?php echo $agotado ? 'producto-agotado-card' : ''; ?>" id="producto-card-<?php echo $prod['id_producto']; ?>">
                        <!-- Badges Superiores -->
                        <div class="card-badges-top">
                            <?php if ($tienePromo): ?>
                                <span class="badge-promo" title="<?php echo htmlspecialchars($prod['nombre_promocion']); ?>">
                                    -<?php echo $descuentoPorcentaje; ?>% OFF
                                </span>
                            <?php endif; ?>
                            <?php if ($agotado): ?>
                                <span class="badge-stock badge-agotado">Agotado</span>
                            <?php else: ?>
                                <span class="badge-stock badge-disponible">
                                    Stock: <?php echo $stock; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Imagen con link al detalle -->
                        <a href="producto_detalle.php?id=<?php echo $prod['id_producto']; ?>" class="card-img-link">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                 alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                 loading="lazy"
                                 onerror="this.src='destacado 1.jfif';">
                        </a>

                        <!-- Cuerpo del Card -->
                        <div class="card-info-body">
                            <div class="card-meta">
                                <span class="card-categoria"><?php echo htmlspecialchars($prod['categoria']); ?></span>
                                <span class="card-marca"><?php echo htmlspecialchars($prod['marca']); ?></span>
                            </div>

                            <h3 class="card-titulo">
                                <a href="producto_detalle.php?id=<?php echo $prod['id_producto']; ?>">
                                    <?php echo htmlspecialchars($prod['nombre']); ?>
                                </a>
                            </h3>

                            <div class="card-sku">
                                <strong>Código:</strong> <span><?php echo htmlspecialchars($prod['codigo_producto']); ?></span>
                            </div>

                            <!-- Precios -->
                            <div class="card-precio-box">
                                <?php if ($tienePromo): ?>
                                    <span class="precio-original-tachado"><?php echo formatearPrecio($prod['precio']); ?></span>
                                    <span class="precio-final-destacado"><?php echo formatearPrecio($prod['precio_final']); ?></span>
                                <?php else: ?>
                                    <span class="precio-final-destacado"><?php echo formatearPrecio($prod['precio']); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Acciones / Botones -->
                            <div class="card-acciones">
                                <a href="producto_detalle.php?id=<?php echo $prod['id_producto']; ?>" class="btn-ver-detalle">
                                    Ver Detalle
                                </a>
                                
                                <?php if ($agotado): ?>
                                    <button class="btn-agregar-disabled" disabled title="Producto sin unidades en inventario">
                                        Agotado
                                    </button>
                                <?php else: ?>
                                    <button class="btn-agregar-carrito" 
                                            data-id="<?php echo $prod['id_producto']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                            data-precio="<?php echo (float)$prod['precio_final']; ?>"
                                            data-imagen="<?php echo htmlspecialchars($imgSrc); ?>"
                                            data-stock="<?php echo $stock; ?>"
                                            title="Agregar al carrito de compras">
                                        🛒 Añadir
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('includes/footer.php'); ?>
