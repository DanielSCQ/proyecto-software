<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
$productoId = filter_var($_GET['id_producto'] ?? null, FILTER_VALIDATE_INT);
$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
if (!$productoId || $productoId < 1 || !$pagina || $pagina < 1) {
    http_response_code(400); exit('El producto o la página solicitada no es válida.');
}
try {
    $productoResenas = agranda_resena_producto($conexion, $productoId);
    if (!$productoResenas) { http_response_code(404); exit('Producto no encontrado.'); }
    $resumenPublico = agranda_resena_resumen_publico($conexion, $productoId);
    $totalPaginas = max(1, (int) ceil($resumenPublico['total'] / 10));
    if ($pagina > $totalPaginas) { http_response_code(404); exit('Página no encontrada.'); }
    $resenasPublicas = agranda_resena_datos_publicos(agranda_resena_recientes_publicas($conexion, $productoId, 10, ($pagina - 1) * 10));
} catch (Throwable $error) {
    error_log('Listado de reseñas: ' . $error->getMessage());
    http_response_code(500); exit('No fue posible consultar las reseñas.');
}
$esc = static fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$dibujarEstrellas = static function (float $valor, string $etiqueta) use ($esc): string {
    $html = '<span class="resenas-estrellas-publicas" role="img" aria-label="' . $esc($etiqueta) . '">';
    for ($i = 0; $i < 5; $i++) {
        $relleno = number_format(max(0, min(1, $valor - $i)) * 100, 2, '.', '');
        $html .= '<span class="resenas-estrella-publica" aria-hidden="true" style="--relleno:' . $relleno . '%"></span>';
    }
    return $html . '</span>';
};
require __DIR__ . '/../includes/header.php';
$urlPagina = $base_url . 'resenas/index.php?id_producto=' . $productoId . '&pagina=';
?>
<link rel="stylesheet" href="<?= $esc($base_url . v_tienda('css/resenas.css')) ?>">
<main class="resena-page">
    <section class="resena-panel resenas-publicas-page" aria-labelledby="resenas-titulo">
        <a href="<?= $esc($base_url) ?>productos/">Volver a Productos</a>
        <h1 id="resenas-titulo">Reseñas de clientes</h1>
        <h2><?= $esc($productoResenas['nombre']) ?></h2>
        <p class="resena-referencia">Referencia: <?= $esc($productoResenas['codigo_producto']) ?></p>
        <div class="resenas-resumen">
            <?php if ($resumenPublico['total'] > 0): $promedioTexto = number_format($resumenPublico['promedio'], 1, '.', ''); ?>
                <?= $dibujarEstrellas($resumenPublico['promedio'], 'Calificación promedio: ' . $promedioTexto . ' de 5') ?>
                <span><?= $esc($promedioTexto) ?> · <?= (int) $resumenPublico['total'] ?> <?= $resumenPublico['total'] === 1 ? 'reseña' : 'reseñas' ?></span>
            <?php else: ?>
                <span>Sin calificaciones</span>
            <?php endif; ?>
        </div>
        <?php if (!$resenasPublicas): ?><p>Este producto todavía no tiene reseñas.</p><?php endif; ?>
        <div class="resenas-lista">
            <?php foreach ($resenasPublicas as $resenaPublica): ?>
                <article class="resena-publica">
                    <h3><?= $esc($resenaPublica['nombre_cliente']) ?></h3>
                    <?= $dibujarEstrellas($resenaPublica['calificacion'], 'Calificación: ' . $resenaPublica['calificacion'] . ' de 5') ?>
                    <time datetime="<?= $esc(str_replace(' ', 'T', $resenaPublica['fecha'])) ?>"><?= $esc(date('d/m/Y', strtotime($resenaPublica['fecha']))) ?></time>
                    <p class="resena-publica-comentario"><?= $esc($resenaPublica['comentario']) ?></p>
                    <?php if ($resenaPublica['editada']): ?><span class="resena-publica-editada">Editada</span><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPaginas > 1): ?>
            <nav class="resenas-paginacion" aria-label="Páginas de reseñas">
                <?php if ($pagina > 1): ?><a href="<?= $esc($urlPagina . ($pagina - 1)) ?>" rel="prev">Anterior</a><?php endif; ?>
                <span aria-current="page">Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span>
                <?php if ($pagina < $totalPaginas): ?><a href="<?= $esc($urlPagina . ($pagina + 1)) ?>" rel="next">Siguiente</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
