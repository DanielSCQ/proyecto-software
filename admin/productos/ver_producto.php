<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) { header('Location: ../login.php'); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET'); http_response_code(405); exit('Esta ficha solo admite consultas GET.');
}
require_once __DIR__ . '/contexto_catalogo.php';
$id = $_GET['id'] ?? null;
if (!is_string($id) || !preg_match('/^[1-9][0-9]*$/D', $id)
    || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
    http_response_code(400); exit('El identificador del producto no es válido.');
}
try { $regreso = agranda_catalogo_contexto($_GET); }
catch (InvalidArgumentException $e) { http_response_code(400); exit('Los parámetros de regreso no son válidos.'); }
$contexto = agranda_catalogo_contexto_enlace($regreso);
$volver = 'productos.php' . ($regreso ? '?' . http_build_query($regreso) : '');
$urlResenas = '../resenas/resenas.php?' . http_build_query(['id_producto' => $id] + $contexto);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
function ficha_escape($valor): string { return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function ficha_texto($valor): string { return $valor === null || trim((string) $valor) === '' ? 'Sin registrar' : ficha_escape($valor); }
function ficha_dinero($valor): string { return $valor === null ? 'Sin registrar' : '$' . number_format((float) $valor, 0, ',', '.'); }
function ficha_consultar(mysqli $db, string $sql, int $id): array {
    $stmt = $db->prepare($sql);
    try { $stmt->bind_param('i', $id); $stmt->execute(); return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); }
    finally { $stmt->close(); }
}
function ficha_imagen(?string $ruta): ?string {
    if (!$ruta) return null;
    $raiz = realpath(__DIR__ . '/../..'); $archivo = realpath($raiz . '/' . ltrim($ruta, '/\\'));
    if (!$archivo || !str_starts_with($archivo, $raiz . DIRECTORY_SEPARATOR) || !is_file($archivo)) return null;
    return '../../' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', ltrim($ruta, '/\\')))));
}
$id = (int) $id;
try {
    $producto = ficha_consultar($conexion, 'SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
        pr.id_promocion, pr.nombre AS nombre_promocion, pr.tipo AS tipo_promocion, pr.valor_descuento
        FROM productos p INNER JOIN categorias c ON c.id_categoria=p.id_categoria
        LEFT JOIN marcas m ON m.id_marca=p.id_marca
        LEFT JOIN promociones pr ON pr.id_promocion=(SELECT pp.id_promocion FROM promocion_producto pp
            INNER JOIN promociones pr2 ON pr2.id_promocion=pp.id_promocion
            WHERE pp.id_producto=p.id_producto AND pr2.estado=1 AND CURDATE() BETWEEN pr2.fecha_inicio AND pr2.fecha_fin
            ORDER BY pr2.id_promocion DESC LIMIT 1)
        WHERE p.id_producto=?', $id)[0] ?? null;
    if (!$producto) { http_response_code(404); exit('El producto no existe.'); }
    $imagenes = ficha_consultar($conexion, 'SELECT * FROM imagenes_producto WHERE id_producto=? ORDER BY principal DESC, orden, id_imagen', $id);
    $proveedores = ficha_consultar($conexion, 'SELECT pp.*, prov.nombre, prov.estado AS estado_proveedor FROM proveedor_producto pp
        INNER JOIN proveedores prov ON prov.id_proveedor=pp.id_proveedor WHERE pp.id_producto=? ORDER BY pp.id_proveedor_producto', $id);
    $inventario = ficha_consultar($conexion, 'SELECT stock_actual, stock_minimo FROM inventario WHERE id_producto=?', $id)[0] ?? null;
    $atributos = ficha_consultar($conexion, 'SELECT a.nombre, pa.valor FROM producto_atributo pa
        INNER JOIN atributos_producto a ON a.id_atributo=pa.id_atributo WHERE pa.id_producto=? ORDER BY a.nombre, pa.id_producto_atributo', $id);
    $compatibilidades = ficha_consultar($conexion, 'SELECT co.observaciones, co.estado, mo.nombre AS modelo, mo.anio_inicio, mo.anio_fin, ma.nombre AS marca
        FROM compatibilidades co INNER JOIN modelos mo ON mo.id_modelo=co.id_modelo
        INNER JOIN marcas ma ON ma.id_marca=mo.id_marca WHERE co.id_producto=? ORDER BY ma.nombre, mo.nombre, co.id_compatibilidad', $id);
    $valoracion = agranda_resena_valoracion_tarjeta(agranda_resena_resumen_publico($conexion, $id));
} catch (Throwable $e) {
    error_log('Ficha administrativa de producto: ' . $e->getMessage());
    http_response_code(500); exit('No fue posible consultar el producto. Intenta nuevamente.');
}
$principal = $imagenes && (int) $imagenes[0]['principal'] === 1 ? $imagenes[0] : null;
$imagenPrincipal = $principal ? ficha_imagen($principal['ruta_imagen']) : null;
$precioOriginal = (float) $producto['precio']; $precioFinal = $precioOriginal;
if ($producto['id_promocion']) {
    $precioFinal = $producto['tipo_promocion'] === 'Porcentaje'
        ? $precioOriginal - ($precioOriginal * ((float) $producto['valor_descuento'] / 100))
        : $precioOriginal - (float) $producto['valor_descuento'];
    $precioFinal = max(0, $precioFinal);
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ficha_escape($producto['nombre']) ?> | Producto · AGRANDA</title>
<link rel="stylesheet" href="<?= ficha_escape(v_admin('productos.css', __DIR__)) ?>">
</head><body class="catalogo-admin ficha-admin"><div class="contenedor-dashboard">
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
<main class="contenido catalogo-contenido">
    <header class="catalogo-encabezado"><div><p class="catalogo-etiqueta">CONSULTA ADMINISTRATIVA</p><h1>Detalles del producto</h1><p>Información comercial, inventario y valoraciones.</p></div>
        <div class="catalogo-sesion"><div class="catalogo-reloj"><span id="fecha"></span><br><span id="hora"></span></div>
            <span class="catalogo-usuario"><span aria-hidden="true">👤</span><span><?= ficha_escape($_SESSION['nombre'] ?? '') ?></span></span>
            <a class="catalogo-boton catalogo-salir" href="../cerrar_sesion.php">Cerrar sesión</a></div></header>
    <nav class="ficha-navegacion" aria-label="Navegación del producto"><a class="catalogo-boton catalogo-boton-secundario" href="<?= ficha_escape($volver) ?>">← Volver al catálogo</a><a class="catalogo-boton catalogo-boton-secundario" href="<?= ficha_escape($urlResenas) ?>">Ver reseñas</a></nav>
    <section class="ficha-bloque ficha-identificacion" aria-labelledby="ficha-nombre">
        <div class="ficha-imagen-principal"><?php if ($imagenPrincipal): ?><img src="<?= ficha_escape($imagenPrincipal) ?>" alt="<?= ficha_escape($producto['nombre']) ?>" width="500" height="320"><?php else: ?><div class="catalogo-sin-imagen"><span aria-hidden="true">▧</span><span>Sin imagen principal disponible</span></div><?php endif; ?></div>
        <div><h2 id="ficha-nombre"><?= ficha_escape($producto['nombre']) ?></h2>
            <div class="catalogo-insignias"><span class="catalogo-estado <?= $producto['estado'] ? 'es-activo' : 'es-inactivo' ?>"><?= $producto['estado'] ? 'Activo' : 'Inactivo' ?></span><?php if ($producto['destacado']): ?><span class="catalogo-destacado">★ Destacado</span><?php endif; ?></div>
            <dl class="ficha-datos"><div><dt>Código</dt><dd><?= ficha_texto($producto['codigo_producto']) ?></dd></div><div><dt>Categoría</dt><dd><?= ficha_texto($producto['categoria']) ?></dd></div><div><dt>Marca</dt><dd><?= ficha_texto($producto['marca']) ?></dd></div><div><dt>Destacado</dt><dd><?= $producto['destacado'] ? 'Sí' : 'No' ?></dd></div></dl>
        </div>
    </section>
    <?php if (count($imagenes) > ($principal ? 1 : 0)): ?><section class="ficha-bloque"><h2>Otras imágenes</h2><div class="ficha-galeria">
        <?php foreach ($imagenes as $numero => $imagen): if ($principal && $imagen['id_imagen'] === $principal['id_imagen']) continue; $ruta = ficha_imagen($imagen['ruta_imagen']); ?>
        <figure><?php if ($ruta): ?><img src="<?= ficha_escape($ruta) ?>" alt="<?= ficha_escape($producto['nombre']) ?> · Imagen <?= $numero + 1 ?>" width="240" height="160" loading="lazy"><?php else: ?><p>Imagen no disponible</p><?php endif; ?><figcaption><?= $imagen['principal'] ? 'Principal adicional' : 'Imagen adicional' ?><?= !$imagen['estado'] ? ' · Inactiva' : '' ?></figcaption></figure>
        <?php endforeach; ?></div></section><?php endif; ?>
    <div class="ficha-secciones">
        <section class="ficha-bloque"><h2>Información comercial</h2><dl class="ficha-datos">
            <div><dt>Precio de venta</dt><dd><?= ficha_dinero($precioOriginal) ?></dd></div>
            <?php if ($producto['id_promocion']): ?><div><dt>Precio promocional</dt><dd class="ficha-precio-final"><?= ficha_dinero($precioFinal) ?></dd></div><div><dt>Promoción vigente</dt><dd><?= ficha_escape($producto['nombre_promocion']) ?> · <?= $producto['tipo_promocion'] === 'Porcentaje' ? number_format((float) $producto['valor_descuento'], 0, ',', '.') . '%' : ficha_dinero($producto['valor_descuento']) ?> de descuento</dd></div><?php else: ?><div><dt>Promoción vigente</dt><dd>Sin promoción vigente</dd></div><?php endif; ?>
        </dl><h3>Proveedores y precios de compra</h3>
            <?php if (!$proveedores): ?><p>Sin registrar</p><?php endif; ?>
            <div class="ficha-proveedores"><?php foreach ($proveedores as $proveedor): ?><div class="ficha-relacion"><h4><?= ficha_texto($proveedor['nombre']) ?></h4><dl class="ficha-datos"><div><dt>Precio de compra</dt><dd><?= ficha_dinero($proveedor['precio_compra']) ?></dd></div><div><dt>Código proveedor</dt><dd><?= ficha_texto($proveedor['codigo_proveedor']) ?></dd></div><div><dt>Relación</dt><dd><?= $proveedor['estado'] ? 'Activa' : 'Inactiva' ?></dd></div></dl></div><?php endforeach; ?></div>
        </section>
        <section class="ficha-bloque"><h2>Inventario</h2>
            <?php if (!$inventario): ?><p>Sin registrar</p><?php else: ?><dl class="ficha-datos"><div><dt>Stock actual</dt><dd><?= (int) $inventario['stock_actual'] ?></dd></div><div><dt>Stock mínimo</dt><dd><?= (int) $inventario['stock_minimo'] ?></dd></div></dl>
            <?php if ((int) $inventario['stock_actual'] <= 0): ?><p class="ficha-alerta">Producto agotado</p><?php elseif ((int) $inventario['stock_actual'] <= (int) $inventario['stock_minimo']): ?><p class="ficha-alerta">Stock bajo: alcanzó el mínimo registrado.</p><?php else: ?><p class="ficha-stock">Stock disponible</p><?php endif; endif; ?>
        </section>
        <section class="ficha-bloque ficha-caracteristicas"><h2>Características</h2><h3>Descripción</h3><p class="ficha-descripcion"><?= ficha_texto($producto['descripcion']) ?></p><dl class="ficha-datos"><div><dt>Peso</dt><dd><?= $producto['peso'] === null ? 'Sin registrar' : ficha_escape($producto['peso']) . ' kg' ?></dd></div></dl><h3>Atributos registrados</h3>
            <?php if (!$atributos): ?><p>Sin registrar</p><?php else: ?><dl class="ficha-datos"><?php foreach ($atributos as $atributo): ?><div><dt><?= ficha_escape($atributo['nombre']) ?></dt><dd><?= ficha_texto($atributo['valor']) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
            <?php if ($compatibilidades): ?><h3>Compatibilidades registradas</h3><ul class="ficha-compatibilidades"><?php foreach ($compatibilidades as $compatibilidad): ?><li><strong><?= ficha_escape($compatibilidad['marca'] . ' · ' . $compatibilidad['modelo']) ?></strong><?php if ($compatibilidad['anio_inicio'] || $compatibilidad['anio_fin']): ?><p>Años: <?= ficha_texto($compatibilidad['anio_inicio']) ?> – <?= ficha_texto($compatibilidad['anio_fin']) ?></p><?php endif; ?><p><?= ficha_texto($compatibilidad['observaciones']) ?></p><?php if (!$compatibilidad['estado']): ?><p>Relación inactiva</p><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
        </section>
        <section class="ficha-bloque"><h2>Valoraciones públicas</h2><div class="catalogo-valoracion" role="img" aria-label="<?= ficha_escape($valoracion['etiqueta']) ?>">
            <?php if ($valoracion['promedio_texto'] !== null): ?><span class="catalogo-estrellas" aria-hidden="true"><?php foreach ($valoracion['rellenos'] as $relleno): ?><span class="catalogo-estrella">★<span style="width:<?= $relleno ?>%">★</span></span><?php endforeach; ?></span><span aria-hidden="true"><strong><?= $valoracion['promedio_texto'] ?></strong> (<?= ficha_escape($valoracion['cantidad_texto']) ?>)</span><?php else: ?><span>Sin calificaciones</span><?php endif; ?>
            </div><p class="ficha-ayuda">Solo cuentan reseñas públicas con compra verificada.</p><a class="catalogo-boton catalogo-boton-secundario" href="<?= ficha_escape($urlResenas) ?>">Ver reseñas del producto</a></section>
    </div>
</main></div><script src="<?= ficha_escape(v_admin('../dashboard/dashboard.js', __DIR__)) ?>"></script></body></html>
