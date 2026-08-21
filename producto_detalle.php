<?php
// producto_detalle.php - Detalle de Producto y Compatibilidades AGRANDA (Fase 3)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

$idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProducto <= 0) {
    header("Location: productos.php");
    exit;
}

// 1. Obtener datos del producto con promociones e inventario
$producto = obtenerProductoPorId($conexion, $idProducto);

if (!$producto) {
    // Si no existe o no está activo, redirigir al catálogo
    header("Location: productos.php?error=no_encontrado");
    exit;
}

// 2. Obtener galería de imágenes
$galeria = obtenerGaleriaProducto($conexion, $idProducto);
if (empty($galeria)) {
    // Fallback con la imagen principal o por defecto
    $galeria[] = [
        'id_imagen' => 0,
        'ruta_imagen' => obtenerRutaImagen($producto['imagen_principal'], 'producto'),
        'principal' => 1,
        'orden' => 1
    ];
}

// 3. Obtener compatibilidades con maquinaria (compatibilidades -> modelos -> marcas)
$compatibilidades = obtenerCompatibilidadesProducto($conexion, $idProducto);

// Variables auxiliares
$stockActual = (int)$producto['stock_actual'];
$agotado = ($stockActual <= 0);
$tienePromo = !empty($producto['id_promocion']) && ((float)$producto['precio_final'] < (float)$producto['precio']);
$descuentoPorcentaje = 0;
if ($tienePromo) {
    if ($producto['tipo_promocion'] === 'Porcentaje') {
        $descuentoPorcentaje = (float)$producto['valor_descuento'];
    } else {
        $descuentoPorcentaje = round((((float)$producto['precio'] - (float)$producto['precio_final']) / (float)$producto['precio']) * 100);
    }
}

include('includes/header.php');
?>

<main class="detalle-producto-wrapper">
    <!-- Breadcrumb de navegación -->
    <nav class="breadcrumb-container" aria-label="Ruta de navegación">
        <div class="breadcrumb-inner">
            <a href="index.php">Inicio</a>
            <span class="bc-sep">/</span>
            <a href="productos.php">Catálogo</a>
            <span class="bc-sep">/</span>
            <a href="productos.php?cat=<?php echo $producto['id_categoria']; ?>"><?php echo htmlspecialchars($producto['categoria']); ?></a>
            <span class="bc-sep">/</span>
            <span class="bc-current"><?php echo htmlspecialchars($producto['nombre']); ?></span>
        </div>
    </nav>

    <div class="detalle-main-card">
        <!-- Columna Izquierda: Galería Interactiva -->
        <section class="detalle-galeria-seccion">
            <div class="galeria-visor-principal">
                <?php if ($tienePromo): ?>
                    <span class="badge-promo-flotante">-<?php echo $descuentoPorcentaje; ?>% OFF</span>
                <?php endif; ?>
                <img id="imagen-visor-principal" 
                     src="<?php echo htmlspecialchars($galeria[0]['ruta_imagen']); ?>" 
                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                     onerror="this.src='destacado 1.jfif';">
            </div>

            <?php if (count($galeria) > 1): ?>
                <div class="galeria-thumbnails-grid">
                    <?php foreach ($galeria as $idx => $img): ?>
                        <button type="button" 
                                class="thumb-btn <?php echo $idx === 0 ? 'thumb-activo' : ''; ?>" 
                                onclick="cambiarImagenDetalle('<?php echo htmlspecialchars($img['ruta_imagen']); ?>', this)">
                            <img src="<?php echo htmlspecialchars($img['ruta_imagen']); ?>" 
                                 alt="Miniatura <?php echo $idx + 1; ?>"
                                 onerror="this.src='destacado 1.jfif';">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Columna Derecha: Información y Acciones -->
        <section class="detalle-info-seccion">
            <div class="detalle-categoria-marca">
                <span class="tag-cat"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                <span class="tag-marca">🚜 Marca: <?php echo htmlspecialchars($producto['marca']); ?></span>
            </div>

            <h1 class="detalle-titulo"><?php echo htmlspecialchars($producto['nombre']); ?></h1>

            <div class="detalle-meta-row">
                <div class="meta-item">
                    <span class="meta-label">Código / SKU:</span>
                    <strong class="meta-val"><?php echo htmlspecialchars($producto['codigo_producto']); ?></strong>
                </div>
                <?php if (!empty($producto['peso']) && (float)$producto['peso'] > 0): ?>
                    <div class="meta-item">
                        <span class="meta-label">Peso estimado:</span>
                        <strong class="meta-val"><?php echo number_format((float)$producto['peso'], 2, ',', '.'); ?> kg</strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Precios -->
            <div class="detalle-precio-container">
                <?php if ($tienePromo): ?>
                    <div class="precio-tachado-wrapper">
                        <span class="lbl-antes">Precio habitual:</span>
                        <del class="precio-antes"><?php echo formatearPrecio($producto['precio']); ?></del>
                    </div>
                    <div class="precio-actual-wrapper">
                        <span class="precio-ahora"><?php echo formatearPrecio($producto['precio_final']); ?></span>
                        <span class="badge-ahorro">¡Ahorras <?php echo formatearPrecio($producto['precio'] - $producto['precio_final']); ?>!</span>
                    </div>
                    <p class="promo-nombre-aviso">🎉 <?php echo htmlspecialchars($producto['nombre_promocion']); ?></p>
                <?php else: ?>
                    <div class="precio-actual-wrapper">
                        <span class="precio-ahora"><?php echo formatearPrecio($producto['precio']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Estado de Disponibilidad e Inventario -->
            <div class="detalle-stock-box">
                <?php if ($agotado): ?>
                    <div class="stock-alerta stock-agotado">
                        <span class="stock-icono">⚠️</span>
                        <div>
                            <strong>Producto Actualmente Agotado</strong>
                            <p>No tenemos stock disponible en este momento. Contáctanos para solicitar reposición.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stock-alerta stock-disponible">
                        <span class="stock-icono">✓</span>
                        <div>
                            <strong>Disponibilidad Inmediata en Bodega</strong>
                            <p>Contamos con <strong><?php echo $stockActual; ?> unidades</strong> listas para despacho.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Formulario de Compra y Selección de Cantidad -->
            <?php if (!$agotado): ?>
                <div class="detalle-compra-controles">
                    <div class="cantidad-selector-box">
                        <label for="cant-detalle">Cantidad:</label>
                        <div class="cant-input-group">
                            <button type="button" class="btn-cant" onclick="ajustarCantidadDetalle(-1, <?php echo $stockActual; ?>)">−</button>
                            <input type="number" id="cant-detalle" value="1" min="1" max="<?php echo $stockActual; ?>" readonly>
                            <button type="button" class="btn-cant" onclick="ajustarCantidadDetalle(1, <?php echo $stockActual; ?>)">+</button>
                        </div>
                    </div>

                    <div class="detalle-botones-flex">
                        <button type="button" 
                                class="btn-detalle-agregar"
                                onclick="agregarAlCarritoDesdeDetalle(<?php echo $producto['id_producto']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'])); ?>', <?php echo (float)$producto['precio_final']; ?>, '<?php echo htmlspecialchars($galeria[0]['ruta_imagen']); ?>', <?php echo $stockActual; ?>)">
                            🛒 Añadir al Carrito
                        </button>
                        <button type="button" 
                                class="btn-detalle-comprar"
                                onclick="comprarAhoraDetalle(<?php echo $producto['id_producto']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'])); ?>', <?php echo (float)$producto['precio_final']; ?>, '<?php echo htmlspecialchars($galeria[0]['ruta_imagen']); ?>', <?php echo $stockActual; ?>)">
                            ⚡ Comprar Ahora
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="detalle-agotado-acciones">
                    <a href="contacto.php?asunto=Consulta%20de%20Disponibilidad%20SKU%20<?php echo urlencode($producto['codigo_producto']); ?>" class="btn-consultar-agotado">
                        ✉️ Solicitar Cotización o Disponibilidad
                    </a>
                </div>
            <?php endif; ?>

            <!-- Descripción del Producto -->
            <div class="detalle-seccion-bloque">
                <h3>📝 Descripción Técnica del Producto</h3>
                <div class="detalle-descripcion-texto">
                    <?php if (!empty($producto['descripcion'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                    <?php else: ?>
                        <p>Pieza de alto rendimiento fabricada bajo estrictos estándares de durabilidad y ajuste perfecto para aplicaciones agrícolas.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Compatibilidad con Maquinaria Agrícola -->
            <div class="detalle-seccion-bloque compatibilidad-bloque">
                <h3>🚜 Compatibilidad con Maquinaria y Tractores</h3>
                <?php if (empty($compatibilidades)): ?>
                    <p class="compat-universal">
                        ℹ️ <strong>Compatibilidad Universal / Estándar:</strong> Este repuesto es apto para múltiples modelos según las especificaciones técnicas y dimensiones indicadas.
                    </p>
                <?php else: ?>
                    <p class="compat-intro">Este producto está homologado y verificado para los siguientes modelos de maquinaria:</p>
                    <div class="compatibilidades-table-wrapper">
                        <table class="tabla-compatibilidades">
                            <thead>
                                <tr>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Años Aplicables</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compatibilidades as $comp): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($comp['marca']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($comp['modelo']); ?></td>
                                        <td>
                                            <?php 
                                                if (!empty($comp['anio_inicio']) && !empty($comp['anio_fin'])) {
                                                    echo htmlspecialchars($comp['anio_inicio']) . ' - ' . htmlspecialchars($comp['anio_fin']);
                                                } elseif (!empty($comp['anio_inicio'])) {
                                                    echo 'Desde ' . htmlspecialchars($comp['anio_inicio']);
                                                } else {
                                                    echo 'Todos los años';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($comp['observaciones']) ? htmlspecialchars($comp['observaciones']) : 'Ajuste directo sin modificaciones.'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script>
function cambiarImagenDetalle(ruta, btn) {
    const visor = document.getElementById('imagen-visor-principal');
    if (visor) {
        visor.src = ruta;
    }
    const thumbs = document.querySelectorAll('.thumb-btn');
    thumbs.forEach(t => t.classList.remove('thumb-activo'));
    if (btn) {
        btn.classList.add('thumb-activo');
    }
}

function ajustarCantidadDetalle(delta, maxStock) {
    const input = document.getElementById('cant-detalle');
    if (!input) return;
    let actual = parseInt(input.value) || 1;
    actual += delta;
    if (actual < 1) actual = 1;
    if (actual > maxStock) actual = maxStock;
    input.value = actual;
}

function agregarAlCarritoDesdeDetalle(id, nombre, precio, imagen, maxStock) {
    const input = document.getElementById('cant-detalle');
    const cantidad = input ? parseInt(input.value) || 1 : 1;
    
    // Obtener carrito
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    let item = carrito.find(p => p.id === id || p.nombre === nombre);
    
    if (item) {
        let nuevaCant = item.cantidad + cantidad;
        if (nuevaCant > maxStock) {
            nuevaCant = maxStock;
            alert("Has alcanzado el stock máximo disponible (" + maxStock + " unidades).");
        }
        item.cantidad = nuevaCant;
    } else {
        carrito.push({
            id: id,
            nombre: nombre,
            precio: parseFloat(precio),
            imagen: imagen,
            cantidad: Math.min(cantidad, maxStock)
        });
    }
    
    localStorage.setItem("carrito", JSON.stringify(carrito));
    if (typeof UI !== 'undefined' && UI.actualizarContador) {
        UI.actualizarContador();
        UI.mostrarToast("¡" + cantidad + "x " + nombre + " agregado al carrito! 🛒");
    } else {
        alert("¡Producto añadido al carrito exitosamente!");
    }
}

function comprarAhoraDetalle(id, nombre, precio, imagen, maxStock) {
    agregarAlCarritoDesdeDetalle(id, nombre, precio, imagen, maxStock);
    window.location.href = "carrito.php";
}
</script>

<?php include('includes/footer.php'); ?>
