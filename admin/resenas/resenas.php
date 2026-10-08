<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) { header('Location: ../login.php'); exit; }
// El listado solo acepta GET; la moderación usa un controlador POST separado.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET'); http_response_code(405); exit('Método no permitido.');
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
require_once __DIR__ . '/../productos/contexto_catalogo.php';
try { $regresoCatalogo = agranda_catalogo_contexto($_GET); }
catch (InvalidArgumentException $e) { http_response_code(400); exit('Los parámetros de regreso no son válidos.'); }
$contextoCatalogo = agranda_catalogo_contexto_enlace($regresoCatalogo);
$idProducto = $_GET['id_producto'] ?? '';
if (!is_string($idProducto) || ($idProducto !== '' && (!preg_match('/^[1-9][0-9]*$/D', $idProducto)
    || filter_var($idProducto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false))) {
    http_response_code(400); exit('El identificador del producto no es válido.');
}
$productoConsultado = null;
if ($idProducto !== '') {
    try {
    $stmtProducto = agranda_resena_preparar($conexion, 'SELECT nombre, codigo_producto FROM productos WHERE id_producto=?');
    $idProducto = (int) $idProducto; $stmtProducto->bind_param('i', $idProducto);
    $productoConsultado = agranda_resena_ejecutar($stmtProducto)->fetch_assoc(); $stmtProducto->close();
    if (!$productoConsultado) { http_response_code(404); exit('El producto no existe.'); }
    } catch (Throwable $e) {
        error_log('Producto del listado de reseñas: ' . $e->getMessage());
        http_response_code(500); exit('No fue posible consultar el producto. Intenta nuevamente.');
    }
}
if (!is_string($_SESSION['csrf_resenas_admin'] ?? null)) {
    $_SESSION['csrf_resenas_admin'] = bin2hex(random_bytes(32));
}
$mensajeModeracion = $_SESSION['resenas_admin_mensaje'] ?? null;
unset($_SESSION['resenas_admin_mensaje']);
function resenas_admin_escape($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$calificacion = $_GET['calificacion'] ?? '';
$verificada = $_GET['verificada'] ?? '';
$pagina = $_GET['pagina'] ?? '1';
if (!is_string($busqueda) || !mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda, 'UTF-8') > 200
    || !is_string($estado) || !in_array($estado, ['', '0', '1'], true)
    || !is_string($calificacion) || !in_array($calificacion, ['', '1', '2', '3', '4', '5'], true)
    || !is_string($verificada) || !in_array($verificada, ['', '0', '1'], true)
    || !is_string($pagina) || !preg_match('/^[1-9][0-9]{0,8}$/D', $pagina)) {
    http_response_code(400); exit('Los parámetros de búsqueda no son válidos.');
}
$busqueda = trim($busqueda);
$pagina = (int) $pagina;
$condiciones = []; $valores = []; $tipos = '';
if ($idProducto !== '') { $condiciones[] = 'r.id_producto = ?'; $valores[] = $idProducto; $tipos .= 'i'; }
if ($busqueda !== '') {
    // Los comodines escritos por el usuario se buscan como texto literal.
    $buscar = '%' . strtr($busqueda, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    $condiciones[] = "(p.nombre LIKE ? ESCAPE '!' OR p.codigo_producto LIKE ? ESCAPE '!' OR CONCAT_WS(' ', u.nombre, u.apellido) LIKE ? ESCAPE '!')";
    array_push($valores, $buscar, $buscar, $buscar); $tipos .= 'sss';
}
foreach (['r.estado' => $estado, 'r.calificacion' => $calificacion, 'r.compra_verificada' => $verificada] as $campo => $valor) {
    if ($valor !== '') { $condiciones[] = "$campo = ?"; $valores[] = (int) $valor; $tipos .= 'i'; }
}
$desde = ' FROM resenas r INNER JOIN productos p ON p.id_producto = r.id_producto INNER JOIN usuarios u ON u.id_usuario = r.id_usuario';
$donde = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
$porPagina = 15;
try {
    $stmt = agranda_resena_preparar($conexion, 'SELECT COUNT(*) AS total' . $desde . $donde);
    if ($tipos !== '') { $stmt->bind_param($tipos, ...$valores); }
    $total = (int) agranda_resena_ejecutar($stmt)->fetch_assoc()['total']; $stmt->close();
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $paginas); $offset = ($pagina - 1) * $porPagina;
    $stmt = agranda_resena_preparar($conexion,
        'SELECT r.id_resena, r.calificacion, r.comentario, r.fecha, r.fecha_actualizacion, r.estado, r.compra_verificada,
         p.id_producto, p.nombre AS producto, p.codigo_producto, u.id_usuario,
         CONCAT_WS(" ", u.nombre, u.apellido) AS autor, u.rol' . $desde . $donde .
        ' ORDER BY r.fecha DESC, r.id_resena DESC LIMIT ? OFFSET ?');
    $valores[] = $porPagina; $valores[] = $offset; $tipos .= 'ii';
    $stmt->bind_param($tipos, ...$valores);
    $resenas = agranda_resena_ejecutar($stmt)->fetch_all(MYSQLI_ASSOC); $stmt->close();
} catch (Throwable $error) {
    error_log('Listado administrativo de reseñas: ' . $error->getMessage());
    http_response_code(500); exit('No fue posible consultar las reseñas. Intenta nuevamente.');
}
$filtros = ['busqueda' => $busqueda, 'estado' => $estado, 'calificacion' => $calificacion, 'verificada' => $verificada];
if ($idProducto !== '') $filtros['id_producto'] = (string) $idProducto;
$filtros += $contextoCatalogo;
$filtrosProducto = $contextoCatalogo + ($idProducto !== '' ? ['id_producto' => (string) $idProducto] : []);
$volverCatalogo = '../productos/productos.php' . ($regresoCatalogo ? '?' . http_build_query($regresoCatalogo) : '');
function resenas_admin_pagina(array $filtros, int $pagina): string {
    return 'resenas.php?' . http_build_query($filtros + ['pagina' => $pagina]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas | Administración AGRANDA</title>
    <link rel="stylesheet" href="<?= resenas_admin_escape(v_admin('../dashboard/dashboard.css', __DIR__)) ?>">
    <link rel="stylesheet" href="<?= resenas_admin_escape(v_admin('resenas.css', __DIR__)) ?>">
</head>
<body class="admin-resenas">
<div class="contenedor-dashboard">
    <aside class="menu-lateral">
        <div class="logo-panel"><h1>AGRANDA</h1><p>Administración</p></div>
        <nav aria-label="Administración"><ul>
            <li><a href="../dashboard/dashboard.php">Dashboard</a></li>
            <li><a href="../productos/productos.php">Productos</a></li>
            <li><a href="../categorias/categorias.php">Categorías</a></li>
            <li><a href="../marcas/marcas.php">Marcas</a></li>
            <li><a href="../proveedores/proveedores.php">Proveedores</a></li>
            <li><a href="../inventario/inventario.php">Inventario</a></li>
            <li><a href="../pedidos/pedidos.php">Pedidos</a></li>
            <li><a href="../clientes/clientes.php">Clientes</a></li>
            <li><a href="resenas.php" class="activo" aria-current="page">★ Reseñas</a></li>
            <li><a href="../contactos/contactos.php">Contactos</a></li>
            <li><a href="../promociones/promociones.php">Promociones</a></li>
            <li><a href="../configuracion/configuracion.php">Configuración</a></li>
        </ul></nav>
    </aside>
    <main class="contenido">
        <header class="resenas-cabecera"><div><p>Panel de Administración AGRANDA</p><h1>Reseñas de compradores</h1></div><a href="../cerrar_sesion.php">Cerrar sesión</a></header>
        <p class="resenas-intro">Consulta las opiniones de los compradores y su estado de publicación.</p>
        <?php if ($productoConsultado): ?><section class="resenas-producto-contexto" aria-label="Producto consultado">
            <h2><?= resenas_admin_escape($productoConsultado['nombre']) ?></h2><p>Código: <?= resenas_admin_escape($productoConsultado['codigo_producto']) ?></p>
            <nav aria-label="Regresar al producto"><a href="<?= resenas_admin_escape($volverCatalogo) ?>">Volver al catálogo</a><a href="<?= resenas_admin_escape('../productos/ver_producto.php?' . http_build_query(['id' => $idProducto] + $contextoCatalogo)) ?>">Ver detalles del producto</a></nav>
        </section><?php endif; ?>
        <?php if (is_array($mensajeModeracion) && is_string($mensajeModeracion['texto'] ?? null)): ?>
            <p class="resenas-mensaje <?= ($mensajeModeracion['tipo'] ?? '') === 'exito' ? 'exito' : 'error' ?>" role="status"><?= resenas_admin_escape($mensajeModeracion['texto']) ?></p>
        <?php endif; ?>
        <form class="resenas-filtros" method="get" action="resenas.php">
            <?php foreach ($filtrosProducto as $nombre => $valor): ?><input type="hidden" name="<?= $nombre ?>" value="<?= resenas_admin_escape($valor) ?>"><?php endforeach; ?>
            <label class="resenas-buscador">Buscar<input type="search" name="busqueda" maxlength="200" value="<?= resenas_admin_escape($busqueda) ?>" placeholder="Producto, código o nombre del comprador"></label>
            <label>Publicación<select name="estado"><?php foreach (['' => 'Todas', '1' => 'Publicadas', '0' => 'Ocultas'] as $valor => $texto): ?><option value="<?= $valor ?>" <?= (string) $valor === $estado ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach; ?></select></label>
            <label>Calificación<select name="calificacion"><option value="">Todas</option><?php for ($n = 1; $n <= 5; $n++): ?><option value="<?= $n ?>" <?= (string) $n === $calificacion ? 'selected' : '' ?>><?= $n ?> <?= $n === 1 ? 'estrella' : 'estrellas' ?></option><?php endfor; ?></select></label>
            <label>Compra<select name="verificada"><?php foreach (['' => 'Todas', '1' => 'Verificada', '0' => 'No verificada'] as $valor => $texto): ?><option value="<?= $valor ?>" <?= (string) $valor === $verificada ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach; ?></select></label>
            <div class="resenas-filtro-acciones"><button type="submit">Buscar</button><a href="<?= resenas_admin_escape('resenas.php' . ($filtrosProducto ? '?' . http_build_query($filtrosProducto) : '')) ?>">Limpiar filtros</a></div>
        </form>
        <p class="resenas-resultados"><?= $total ?> <?= $total === 1 ? 'reseña encontrada' : 'reseñas encontradas' ?> · Página <?= $pagina ?> de <?= $paginas ?></p>
        <section class="resenas-listado" aria-label="Resultados de reseñas">
        <?php if (!$resenas): ?><p class="resenas-vacias">No hay reseñas que coincidan con esta búsqueda.</p><?php endif; ?>
        <?php foreach ($resenas as $resena): ?>
            <article class="resena-admin">
                <div class="resena-admin-cabecera">
                    <div><p class="resena-admin-codigo"><?= resenas_admin_escape($resena['codigo_producto']) ?></p><h2><?= resenas_admin_escape($resena['producto']) ?></h2><p class="resena-admin-autor">Por <?= resenas_admin_escape($resena['autor']) ?><?= $resena['rol'] === 'administrador' ? ' · Administrador comprador' : '' ?></p></div>
                    <div class="resena-admin-etiquetas"><span class="resena-admin-estado <?= (int) $resena['estado'] === 1 ? 'publicada' : 'oculta' ?>"><?= (int) $resena['estado'] === 1 ? 'Publicada' : 'Oculta' ?></span><span><?= (int) $resena['compra_verificada'] === 1 ? 'Compra verificada' : 'Compra no verificada' ?></span></div>
                </div>
                <p class="resena-admin-calificacion"><span aria-hidden="true"><?= str_repeat('★', (int) $resena['calificacion']) . str_repeat('☆', 5 - (int) $resena['calificacion']) ?></span><span><?= (int) $resena['calificacion'] ?> de 5 estrellas</span></p>
                <p class="resena-admin-comentario"><?= resenas_admin_escape($resena['comentario']) ?></p>
                <div class="resena-admin-pie"><div class="resena-admin-fechas"><p>Publicada: <time datetime="<?= resenas_admin_escape(str_replace(' ', 'T', $resena['fecha'])) ?>"><?= resenas_admin_escape(date('d/m/Y H:i', strtotime($resena['fecha']))) ?></time></p><?php if ($resena['fecha_actualizacion']): ?><p>Actualizada: <time datetime="<?= resenas_admin_escape(str_replace(' ', 'T', $resena['fecha_actualizacion'])) ?>"><?= resenas_admin_escape(date('d/m/Y H:i', strtotime($resena['fecha_actualizacion']))) ?></time></p><?php endif; ?></div>
                    <nav class="resena-admin-acciones" aria-label="Consultas de <?= resenas_admin_escape($resena['producto']) ?>"><a href="<?= resenas_admin_escape('../productos/ver_producto.php?' . http_build_query(['id' => (int) $resena['id_producto']] + $contextoCatalogo)) ?>">Ver producto</a><a href="../clientes/ver_clientes.php?id=<?= (int) $resena['id_usuario'] ?>">Ver cliente</a></nav>
                </div>
                <details class="resena-moderacion <?= (int) $resena['estado'] === 1 ? 'ocultar' : 'publicar' ?>">
                    <summary><?= (int) $resena['estado'] === 1 ? 'Ocultar reseña' : 'Publicar reseña' ?></summary>
                    <div class="resena-moderacion-confirmacion">
                        <p><?= (int) $resena['estado'] === 1
                            ? '¿Deseas ocultar esta reseña? Dejará de aparecer públicamente y no contará en el promedio del producto.'
                            : '¿Deseas publicar esta reseña? Aparecerá públicamente si cumple las condiciones de compra verificada.' ?></p>
                        <p class="resena-moderacion-ayuda">Puedes cancelar cerrando esta confirmación. El contenido de la reseña se conservará.</p>
                        <form method="post" action="cambiar_estado.php">
                            <input type="hidden" name="csrf_resenas_admin" value="<?= resenas_admin_escape($_SESSION['csrf_resenas_admin']) ?>">
                            <input type="hidden" name="id_resena" value="<?= (int) $resena['id_resena'] ?>">
                            <input type="hidden" name="accion" value="<?= (int) $resena['estado'] === 1 ? 'ocultar' : 'publicar' ?>">
                            <?php foreach ($filtros + ['pagina' => $pagina] as $nombre => $valor): ?>
                                <input type="hidden" name="<?= $nombre ?>" value="<?= resenas_admin_escape($valor) ?>">
                            <?php endforeach; ?>
                            <button type="submit"><?= (int) $resena['estado'] === 1 ? 'Confirmar ocultación' : 'Confirmar publicación' ?></button>
                        </form>
                    </div>
                </details>
            </article>
        <?php endforeach; ?>
        </section>
        <?php if ($paginas > 1): ?><nav class="resenas-paginacion-admin" aria-label="Paginación de reseñas">
            <?php if ($pagina > 1): ?><a href="<?= resenas_admin_escape(resenas_admin_pagina($filtros, $pagina - 1)) ?>">← Anterior</a><?php endif; ?>
            <span>Página <?= $pagina ?> de <?= $paginas ?></span>
            <?php if ($pagina < $paginas): ?><a href="<?= resenas_admin_escape(resenas_admin_pagina($filtros, $pagina + 1)) ?>">Siguiente →</a><?php endif; ?>
        </nav><?php endif; ?>
    </main>
</div>
</body></html>
