<?php
// carrito.php - Carrito de compras AGRANDA con validación en servidor (Fase 4)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Procesar cupones enviados por formulario estándar (fallback POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion_cupon']) && $_POST['accion_cupon'] === 'aplicar') {
        $cod = trim($_POST['codigo_cupon'] ?? '');
        if (!empty($cod)) {
            $_SESSION['cupon'] = $cod;
        }
    } elseif (isset($_POST['accion_cupon']) && $_POST['accion_cupon'] === 'remover') {
        unset($_SESSION['cupon']);
    } elseif (isset($_POST['accion_carrito']) && $_POST['accion_carrito'] === 'vaciar') {
        $_SESSION['carrito'] = [];
        unset($_SESSION['cupon']);
    }
}

$codigoCupon = $_SESSION['cupon'] ?? null;
$carritoCalculado = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);

include('includes/header.php');
?>

<main class="carrito-seccion-principal">
    <div class="carrito-header-row">
        <h2>🛒 Tu Carrito de Compras</h2>
        <a href="productos.php" class="btn-seguir-comprando">← Seguir Comprando</a>
    </div>

    <!-- Avisos del Servidor (ajustes de stock, promociones, etc.) -->
    <?php if (!empty($carritoCalculado['mensajes'])): ?>
        <div class="carrito-avisos-box">
            <?php foreach ($carritoCalculado['mensajes'] as $msg): ?>
                <div class="aviso-item-alerta">
                    <span class="icono-alerta">⚠️</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="contenedor-carrito-dinamico">
        <?php if (empty($carritoCalculado['items'])): ?>
            <div class="carrito-vacio-moderno" id="carrito-vacio-vista">
                <div class="vacio-icono">🛒</div>
                <h3>Tu carrito de compras está vacío</h3>
                <p>Explora nuestro catálogo de maquinaria agrícola, repuestos y lubricantes de alta calidad.</p>
                <a href="productos.php" class="btn-ir-catalogo">Explorar Productos</a>
            </div>
        <?php else: ?>
            <div class="carrito-layout-grid" id="carrito-layout-vista">
                <!-- Columna Izquierda: Lista de Productos -->
                <section class="carrito-items-col">
                    <div class="carrito-items-lista" id="carrito-items-container">
                        <?php foreach ($carritoCalculado['items'] as $item): ?>
                            <div class="carrito-item-card" id="item-fila-<?php echo $item['id_producto']; ?>">
                                <div class="item-img-col">
                                    <img src="<?php echo htmlspecialchars($item['imagen']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                         onerror="this.src='destacado 1.jfif';">
                                </div>

                                <div class="item-info-col">
                                    <div class="item-meta-top">
                                        <span class="item-cat-badge"><?php echo htmlspecialchars($item['categoria']); ?></span>
                                        <span class="item-sku">SKU: <?php echo htmlspecialchars($item['codigo_producto']); ?></span>
                                    </div>
                                    
                                    <h4 class="item-nombre">
                                        <a href="producto_detalle.php?id=<?php echo $item['id_producto']; ?>">
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </a>
                                    </h4>

                                    <div class="item-precio-unitario">
                                        <?php if ($item['tiene_promo']): ?>
                                            <span class="precio-tachado"><?php echo formatearPrecio($item['precio_original']); ?></span>
                                            <strong class="precio-unit-actual"><?php echo formatearPrecio($item['precio_unitario']); ?></strong>
                                            <span class="tag-promo-item">🎉 <?php echo htmlspecialchars($item['nombre_promo']); ?></span>
                                        <?php else: ?>
                                            <strong class="precio-unit-actual"><?php echo formatearPrecio($item['precio_unitario']); ?></strong>
                                        <?php endif; ?>
                                    </div>

                                    <div class="item-stock-indicador">
                                        <span class="stock-disponible-txt">Stock disponible: <?php echo $item['stock_disponible']; ?> unid.</span>
                                    </div>
                                </div>

                                <div class="item-controles-col">
                                    <div class="item-cantidad-selector">
                                        <button type="button" 
                                                class="btn-qty" 
                                                onclick="actualizarCantidadItem(<?php echo $item['id_producto']; ?>, <?php echo $item['cantidad'] - 1; ?>, <?php echo $item['stock_disponible']; ?>)">−</button>
                                        <span class="qty-num"><?php echo $item['cantidad']; ?></span>
                                        <button type="button" 
                                                class="btn-qty" 
                                                onclick="actualizarCantidadItem(<?php echo $item['id_producto']; ?>, <?php echo $item['cantidad'] + 1; ?>, <?php echo $item['stock_disponible']; ?>)">+</button>
                                    </div>

                                    <div class="item-subtotal-box">
                                        <span class="subtotal-label">Subtotal:</span>
                                        <strong class="subtotal-val"><?php echo formatearPrecio($item['subtotal']); ?></strong>
                                    </div>

                                    <button type="button" 
                                            class="btn-eliminar-item" 
                                            onclick="eliminarItemCarrito(<?php echo $item['id_producto']; ?>)"
                                            title="Eliminar producto">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="carrito-acciones-inferiores">
                        <button type="button" class="btn-vaciar-carrito" onclick="vaciarCarritoCompleto()">
                            🗑️ Vaciar Carrito
                        </button>
                        <a href="productos.php" class="btn-seguir-enlace">
                            + Agregar más productos
                        </a>
                    </div>
                </section>

                <!-- Columna Derecha: Resumen de Orden y Cupones -->
                <aside class="carrito-resumen-col">
                    <div class="resumen-card-modern">
                        <h3>Resumen del Pedido</h3>

                        <!-- Formulario de Cupón de Descuento -->
                        <div class="cupon-box-seccion">
                            <label for="input-codigo-cupon">¿Tienes un cupón de descuento?</label>
                            <div class="cupon-input-grupo">
                                <input type="text" 
                                       id="input-codigo-cupon" 
                                       placeholder="Ingresa tu cupón" 
                                       value="<?php echo htmlspecialchars($codigoCupon ?? ''); ?>"
                                       <?php echo !empty($carritoCalculado['cupon_aplicado']) ? 'readonly' : ''; ?>>
                                
                                <?php if (!empty($carritoCalculado['cupon_aplicado'])): ?>
                                    <button type="button" class="btn-remover-cupon" onclick="removerCuponDescuento()" title="Quitar cupón">✕</button>
                                <?php else: ?>
                                    <button type="button" class="btn-aplicar-cupon" onclick="aplicarCuponDescuento()">Aplicar</button>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($carritoCalculado['cupon_aplicado'])): ?>
                                <div class="cupon-aplicado-badge">
                                    <span>✓ Cupón <strong><?php echo htmlspecialchars($carritoCalculado['cupon_aplicado']['codigo']); ?></strong> activo</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="resumen-divider">

                        <!-- Desglose de Totales -->
                        <div class="resumen-filas-desglose">
                            <div class="desglose-fila">
                                <span>Subtotal Base:</span>
                                <span><?php echo formatearPrecio($carritoCalculado['subtotal_base']); ?></span>
                            </div>

                            <?php if ($carritoCalculado['descuento_promociones'] > 0): ?>
                                <div class="desglose-fila fila-descuento">
                                    <span>Ahorro en Promociones:</span>
                                    <span>-<?php echo formatearPrecio($carritoCalculado['descuento_promociones']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($carritoCalculado['descuento_cupon'] > 0): ?>
                                <div class="desglose-fila fila-descuento">
                                    <span>Descuento Cupón:</span>
                                    <span>-<?php echo formatearPrecio($carritoCalculado['descuento_cupon']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="desglose-fila fila-envio">
                                <span>Envío:</span>
                                <span class="envio-gratis">A convenir / Despacho nacional</span>
                            </div>

                            <hr class="resumen-divider-total">

                            <div class="desglose-fila fila-total-final">
                                <strong>Total a Pagar:</strong>
                                <strong class="monto-total-verde"><?php echo formatearPrecio($carritoCalculado['total']); ?></strong>
                            </div>
                        </div>

                        <!-- Botón de Finalizar Compra -->
                        <div class="resumen-checkout-acciones">
                            <button type="button" class="btn-proceder-checkout" onclick="iniciarCheckoutTienda()">
                                💳 Finalizar Compra
                            </button>
                            <p class="garantia-segura-txt">🔒 Compra 100% segura con respaldo y garantía AGRANDA</p>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Sincronizar localStorage inicial si la sesión estaba vacía pero el cliente tenía items
document.addEventListener('DOMContentLoaded', () => {
    const itemsLocal = JSON.parse(localStorage.getItem('carrito')) || [];
    const numItemsPHP = <?php echo count($carritoCalculado['items']); ?>;
    
    if (itemsLocal.length > 0 && numItemsPHP === 0) {
        // Enviar a sincronizar con servidor
        fetch('acciones_carrito.php?action=sincronizar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: itemsLocal })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.carrito.items.length > 0) {
                window.location.reload();
            }
        });
    }
});

function actualizarCantidadItem(idProducto, nuevaCantidad, stockMax) {
    if (nuevaCantidad > stockMax) {
        alert("No puedes agregar más de " + stockMax + " unidades (límite de inventario).");
        return;
    }
    
    fetch('acciones_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'actualizar',
            id_producto: idProducto,
            cantidad: nuevaCantidad
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Actualizar local storage para concordancia
            actualizarLocalStorageDesdeServidor(data.carrito);
            window.location.reload();
        } else {
            alert(data.error || "Error al actualizar cantidad");
        }
    });
}

function eliminarItemCarrito(idProducto) {
    if (!confirm("¿Deseas eliminar este producto del carrito?")) return;

    fetch('acciones_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'eliminar',
            id_producto: idProducto
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            actualizarLocalStorageDesdeServidor(data.carrito);
            window.location.reload();
        }
    });
}

function vaciarCarritoCompleto() {
    if (!confirm("¿Estás seguro de que deseas vaciar todo el carrito?")) return;

    fetch('acciones_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'vaciar' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            localStorage.removeItem('carrito');
            window.location.reload();
        }
    });
}

function aplicarCuponDescuento() {
    const input = document.getElementById('input-codigo-cupon');
    const codigo = input ? input.value.trim() : '';
    if (!codigo) {
        alert("Por favor ingresa un código de cupón.");
        return;
    }

    fetch('acciones_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'aplicar_cupon',
            codigo: codigo
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert(data.mensaje);
            window.location.reload();
        } else {
            alert(data.error || "Cupón inválido");
        }
    });
}

function removerCuponDescuento() {
    fetch('acciones_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remover_cupon' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            window.location.reload();
        }
    });
}

function actualizarLocalStorageDesdeServidor(carritoServidor) {
    if (!carritoServidor || !carritoServidor.items) return;
    const itemsLocal = carritoServidor.items.map(it => ({
        id: it.id_producto,
        nombre: it.nombre,
        precio: it.precio_unitario,
        imagen: it.imagen,
        cantidad: it.cantidad
    }));
    localStorage.setItem('carrito', JSON.stringify(itemsLocal));
    if (typeof UI !== 'undefined' && UI.actualizarContador) {
        UI.actualizarContador();
    }
}

function iniciarCheckoutTienda() {
    if (typeof abrirModalCheckout === 'function') {
        abrirModalCheckout();
    } else {
        alert("Redirigiendo a confirmación de pedido...");
    }
}
</script>

<?php include('includes/footer.php'); ?>
