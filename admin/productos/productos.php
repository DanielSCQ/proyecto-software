<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) {
    header('Location: ../login.php');
    exit();
}
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/contexto_catalogo.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';

function catalogo_escape($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
function catalogo_id_get(string $nombre): ?int {
    $valor = $_GET[$nombre] ?? '';
    if (!is_string($valor) || $valor === '') return null;
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}
function catalogo_consultar(mysqli $db, string $sql, string $tipos = '', array $valores = []): array {
    $stmt = $db->prepare($sql);
    try {
        if ($tipos !== '') $stmt->bind_param($tipos, ...$valores);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } finally { $stmt->close(); }
}
function catalogo_url(array $parametros, int $pagina): string {
    return 'productos.php?' . http_build_query(array_merge($parametros, ['pagina' => $pagina]));
}
function catalogo_imagen(?string $ruta): ?string {
    if (!$ruta) return null;
    $raiz = realpath(__DIR__ . '/../..');
    $archivo = realpath($raiz . '/' . ltrim($ruta, '/\\'));
    if (!$archivo || !str_starts_with($archivo, $raiz . DIRECTORY_SEPARATOR) || !is_file($archivo)) return null;
    return '../../' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', ltrim($ruta, '/\\')))));
}

$busqueda = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$busqueda = mb_substr($busqueda, 0, 200, 'UTF-8');
$estado = is_string($_GET['estado'] ?? null) && in_array($_GET['estado'], ['0', '1'], true) ? $_GET['estado'] : '';
$categoria = catalogo_id_get('categoria');
$marca = catalogo_id_get('marca');
$categorias = catalogo_consultar($conexion, 'SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
$marcas = catalogo_consultar($conexion, 'SELECT id_marca, nombre FROM marcas ORDER BY nombre');
if ($categoria !== null && !in_array($categoria, array_map('intval', array_column($categorias, 'id_categoria')), true)) $categoria = null;
if ($marca !== null && !in_array($marca, array_map('intval', array_column($marcas, 'id_marca')), true)) $marca = null;
$condiciones = []; $tipos = ''; $valores = []; $parametros = [];
if ($busqueda !== '') {
    // ! es el escape SQL: % y _ escritos por el usuario se buscan literalmente.
    $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda) . '%';
    $condiciones[] = "(p.nombre LIKE ? ESCAPE '!' OR p.codigo_producto LIKE ? ESCAPE '!')";
    $tipos .= 'ss'; $valores[] = $patron; $valores[] = $patron; $parametros['q'] = $busqueda;
}
if ($estado !== '') { $condiciones[] = 'p.estado = ?'; $tipos .= 'i'; $valores[] = (int) $estado; $parametros['estado'] = $estado; }
if ($categoria !== null) { $condiciones[] = 'p.id_categoria = ?'; $tipos .= 'i'; $valores[] = $categoria; $parametros['categoria'] = $categoria; }
if ($marca !== null) { $condiciones[] = 'p.id_marca = ?'; $tipos .= 'i'; $valores[] = $marca; $parametros['marca'] = $marca; }
$where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
$estadisticas = catalogo_consultar($conexion, 'SELECT COUNT(*) AS total,
    COALESCE(SUM(p.estado = 1), 0) AS activos,
    COALESCE(SUM(EXISTS(SELECT 1 FROM resenas r WHERE r.id_producto = p.id_producto AND '
    . agranda_resena_filtro_publico() . ')), 0) AS con_resenas FROM productos p')[0];
$totalResultados = (int) catalogo_consultar($conexion, 'SELECT COUNT(*) AS total FROM productos p' . $where, $tipos, $valores)[0]['total'];
$totalPaginas = max(1, (int) ceil($totalResultados / 12));
$pagina = min(catalogo_id_get('pagina') ?? 1, $totalPaginas);
$desplazamiento = ($pagina - 1) * 12;
// Primero se pagina productos. Las relaciones posteriores son de una sola fila.
$productos = catalogo_consultar($conexion, 'SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
    img.ruta_imagen, prov.nombre AS proveedor,
    pr.id_promocion, pr.nombre AS nombre_promocion, pr.tipo AS tipo_promocion, pr.valor_descuento
    FROM (SELECT * FROM productos p' . $where . ' ORDER BY p.id_producto DESC LIMIT ? OFFSET ?) p
    INNER JOIN categorias c ON c.id_categoria = p.id_categoria
    LEFT JOIN marcas m ON m.id_marca = p.id_marca
    LEFT JOIN imagenes_producto img ON img.id_imagen = (
        SELECT im.id_imagen FROM imagenes_producto im WHERE im.id_producto = p.id_producto AND im.principal = 1
        ORDER BY im.orden ASC, im.id_imagen ASC LIMIT 1)
    LEFT JOIN proveedor_producto pp ON pp.id_proveedor_producto = (
        SELECT pv.id_proveedor_producto FROM proveedor_producto pv WHERE pv.id_producto = p.id_producto
        ORDER BY pv.id_proveedor_producto ASC LIMIT 1)
    LEFT JOIN proveedores prov ON prov.id_proveedor = pp.id_proveedor
    LEFT JOIN promociones pr ON pr.id_promocion = (
        SELECT pp2.id_promocion FROM promocion_producto pp2
        INNER JOIN promociones pr2 ON pp2.id_promocion = pr2.id_promocion
        WHERE pp2.id_producto = p.id_producto AND pr2.estado = 1
        AND CURDATE() BETWEEN pr2.fecha_inicio AND pr2.fecha_fin
        ORDER BY pr2.id_promocion DESC LIMIT 1)
    ORDER BY p.id_producto DESC', $tipos . 'ii', array_merge($valores, [12, $desplazamiento]));
$resumenes = agranda_resena_resumenes_publicos($conexion, array_column($productos, 'id_producto'));
$contextoCatalogo = agranda_catalogo_contexto_enlace($parametros + ['pagina' => $pagina]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de productos | AGRANDA</title>
    <link rel="stylesheet" href="<?= catalogo_escape(v_admin('productos.css', __DIR__)) ?>">
</head>
<body class="catalogo-admin">
<div class="contenedor-dashboard">
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
    <header class="catalogo-encabezado">
        <div><p class="catalogo-etiqueta">CATÁLOGO ADMINISTRATIVO</p><h1>Gestión de productos</h1>
            <p>Administra tu catálogo, precios y valoraciones.</p></div>
        <div class="catalogo-sesion"><div class="catalogo-reloj"><span id="fecha"></span><br><span id="hora"></span></div>
            <span class="catalogo-usuario"><span class="catalogo-usuario-icono" aria-hidden="true">👤</span><span><?= catalogo_escape($_SESSION['nombre'] ?? '') ?></span></span>
            <a class="catalogo-boton catalogo-salir" href="../cerrar_sesion.php">Cerrar sesión</a></div>
    </header>
    <?php if (isset($_SESSION['productos_mensaje'])): ?>
        <p class="catalogo-mensaje" role="status"><?= catalogo_escape($_SESSION['productos_mensaje']) ?></p>
        <?php unset($_SESSION['productos_mensaje']); endif; ?>
    <section class="catalogo-estadisticas" aria-label="Estadísticas globales del catálogo">
        <?php foreach ([['total', 'Total de productos', '📦'], ['activos', 'Productos activos', '✓'], ['con_resenas', 'Productos con reseñas', '★']] as [$clave, $titulo, $icono]): ?>
        <div class="catalogo-estadistica"><span class="catalogo-icono" aria-hidden="true"><?= $icono ?></span>
            <div><span><?= $titulo ?></span><strong data-estadistica="<?= $clave ?>"><?= (int) $estadisticas[$clave] ?></strong></div></div>
        <?php endforeach; ?>
    </section>
    <div class="catalogo-herramientas"><h2>Tu catálogo</h2><div class="catalogo-accesos">
        <a class="catalogo-boton" href="agregar_producto.php">+ Nuevo Producto</a>
        <a class="catalogo-boton catalogo-boton-secundario" href="atributos.php">Atributos</a>
    </div></div>
    <form method="GET" action="productos.php" class="catalogo-filtros" role="search" aria-label="Buscar y filtrar productos">
        <div class="catalogo-busqueda"><label for="catalogo-q">Nombre o código</label><input id="catalogo-q" name="q" type="search" maxlength="200" placeholder="Buscar productos…" value="<?= catalogo_escape($busqueda) ?>"></div>
        <div><label for="catalogo-estado">Estado</label><select id="catalogo-estado" name="estado">
            <option value="">Todos</option><option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option><option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option></select></div>
        <div><label for="catalogo-categoria">Categoría</label><select id="catalogo-categoria" name="categoria"><option value="">Todas</option>
            <?php foreach ($categorias as $fila): ?><option value="<?= (int) $fila['id_categoria'] ?>" <?= $categoria === (int) $fila['id_categoria'] ? 'selected' : '' ?>><?= catalogo_escape($fila['nombre']) ?></option><?php endforeach; ?></select></div>
        <div><label for="catalogo-marca">Marca</label><select id="catalogo-marca" name="marca"><option value="">Todas</option>
            <?php foreach ($marcas as $fila): ?><option value="<?= (int) $fila['id_marca'] ?>" <?= $marca === (int) $fila['id_marca'] ? 'selected' : '' ?>><?= catalogo_escape($fila['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="catalogo-filtro-acciones"><button class="catalogo-boton" type="submit">Buscar</button><a href="productos.php" class="catalogo-limpiar">Limpiar filtros</a></div>
    </form>
    <p class="catalogo-resultados" role="status"><?= $totalResultados ?> <?= $totalResultados === 1 ? 'producto encontrado' : 'productos encontrados' ?> · Página <?= $pagina ?> de <?= $totalPaginas ?></p>
    <?php if (!$productos): ?>
        <div class="catalogo-vacio"><h2>No se encontraron productos</h2><p>Prueba otro nombre, código o combinación de filtros.</p><a href="productos.php" class="catalogo-boton catalogo-boton-secundario">Limpiar filtros</a></div>
    <?php else: ?>
    <section class="catalogo-grid" aria-label="Productos">
    <?php foreach ($productos as $producto):
        $id = (int) $producto['id_producto'];
        $imagen = catalogo_imagen($producto['ruta_imagen']);
        $valoracion = agranda_resena_valoracion_tarjeta($resumenes[$id]);
        $precioOriginal = (float) $producto['precio']; $precioFinal = $precioOriginal;
        if (!empty($producto['id_promocion'])) {
            if ($producto['tipo_promocion'] === 'Porcentaje') {
                $precioFinal = $precioOriginal - ($precioOriginal * ((float) $producto['valor_descuento'] / 100));
            } else { $precioFinal = $precioOriginal - (float) $producto['valor_descuento']; }
            $precioFinal = max(0, $precioFinal);
        }
    ?>
        <article class="catalogo-card" data-producto="<?= $id ?>">
            <div class="catalogo-imagen">
                <?php if ($imagen): ?><img src="<?= catalogo_escape($imagen) ?>" alt="<?= catalogo_escape($producto['nombre']) ?>" loading="lazy" width="420" height="280">
                <?php else: ?><div class="catalogo-sin-imagen"><span aria-hidden="true">▧</span><span>Sin imagen</span></div><?php endif; ?>
            </div>
            <div class="catalogo-card-cuerpo">
                <div class="catalogo-insignias"><span class="catalogo-estado <?= $producto['estado'] ? 'es-activo' : 'es-inactivo' ?>"><?= $producto['estado'] ? 'Activo' : 'Inactivo' ?></span>
                    <?php if ($producto['destacado']): ?><span class="catalogo-destacado">★ Destacado</span><?php endif; ?></div>
                <h3><?= catalogo_escape($producto['nombre']) ?></h3>
                <p class="catalogo-codigo">Código: <?= catalogo_escape($producto['codigo_producto']) ?></p>
                <p class="catalogo-clasificacion"><?= catalogo_escape($producto['marca'] ?: $producto['categoria']) ?></p>
                <div class="catalogo-valoracion" role="img" aria-label="<?= catalogo_escape($valoracion['etiqueta']) ?>">
                    <?php if ($valoracion['promedio_texto'] !== null): ?>
                    <span class="catalogo-estrellas" aria-hidden="true"><?php foreach ($valoracion['rellenos'] as $relleno): ?><span class="catalogo-estrella">★<span style="width:<?= catalogo_escape($relleno) ?>%">★</span></span><?php endforeach; ?></span>
                    <span aria-hidden="true"><strong><?= $valoracion['promedio_texto'] ?></strong> (<?= catalogo_escape($valoracion['cantidad_texto']) ?>)</span>
                    <?php else: ?><span aria-hidden="true">Sin calificaciones</span><?php endif; ?>
                </div>
                <div class="catalogo-precios">
                    <?php if (!empty($producto['id_promocion'])): ?><del>$<?= number_format($precioOriginal, 0, ',', '.') ?></del><?php endif; ?>
                    <strong>$<?= number_format($precioFinal, 0, ',', '.') ?></strong>
                    <?php if (!empty($producto['id_promocion'])): ?><span class="catalogo-promocion"><?= catalogo_escape($producto['nombre_promocion']) ?> · <?= $producto['tipo_promocion'] === 'Porcentaje' ? number_format((float) $producto['valor_descuento'], 0, ',', '.') . '%' : '$' . number_format((float) $producto['valor_descuento'], 0, ',', '.') ?> de descuento</span><?php endif; ?>
                </div>
                <div class="catalogo-card-acciones">
                    <a class="catalogo-boton catalogo-boton-secundario" href="<?= catalogo_escape('ver_producto.php?' . http_build_query(['id' => $id] + $contextoCatalogo)) ?>">Ver detalles</a>
                    <a class="catalogo-boton catalogo-boton-secundario" href="<?= catalogo_escape('../resenas/resenas.php?' . http_build_query(['id_producto' => $id] + $contextoCatalogo)) ?>">Ver reseñas</a>
                    <a class="catalogo-boton catalogo-boton-secundario" href="editar_producto.php?id=<?= $id ?>" aria-label="Editar <?= catalogo_escape($producto['nombre']) ?>"><span class="catalogo-accion-icono" aria-hidden="true">✎</span>Editar</a>
                    <form method="POST" action="eliminar_producto.php" onsubmit="return confirm('¿Está seguro de eliminar este producto?');">
                        <?= agranda_productos_campo_csrf() ?><input type="hidden" name="id" value="<?= $id ?>">
                        <button class="catalogo-boton catalogo-boton-eliminar" type="submit" aria-label="Eliminar <?= catalogo_escape($producto['nombre']) ?>"><span class="catalogo-accion-icono" aria-hidden="true">🗑</span>Eliminar</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </section>
    <?php endif; ?>
    <?php if ($totalPaginas > 1): ?>
    <nav class="catalogo-paginacion" aria-label="Páginas de productos">
        <?php if ($pagina > 1): ?><a href="<?= catalogo_escape(catalogo_url($parametros, $pagina - 1)) ?>" rel="prev">Anterior</a><?php endif; ?>
        <?php $paginasVisibles = array_unique(array_merge([1], range(max(1, $pagina - 2), min($totalPaginas, $pagina + 2)), [$totalPaginas])); $anterior = 0;
        foreach ($paginasVisibles as $numero): if ($anterior && $numero > $anterior + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
            <?php if ($numero === $pagina): ?><span aria-current="page"><?= $numero ?></span><?php else: ?><a href="<?= catalogo_escape(catalogo_url($parametros, $numero)) ?>" aria-label="Página <?= $numero ?>"><?= $numero ?></a><?php endif; $anterior = $numero; endforeach; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="<?= catalogo_escape(catalogo_url($parametros, $pagina + 1)) ?>" rel="next">Siguiente</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
</div>
<script src="<?= catalogo_escape(v_admin('../dashboard/dashboard.js', __DIR__)) ?>"></script>
</body>
</html>
