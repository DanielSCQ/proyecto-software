<?php
require_once __DIR__ . '/../../config/conexion.php';
$nosotros = [];
try {
    $id = 1;
    $stmt = $conexion->prepare('SELECT nosotros_titulo, nosotros_descripcion, nosotros_mision, nosotros_vision, nosotros_valores, nosotros_imagen_hero, nosotros_imagen_institucional FROM configuracion_tienda WHERE id_configuracion = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $nosotros = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
} catch (Throwable $error) {
    $nosotros = [];
}
foreach ($nosotros as $campo => $valor) { $nosotros[$campo] = trim((string) $valor); }
$valores = array_values(array_filter(array_map('trim', explode("\n", $nosotros['nosotros_valores'] ?? '')), static fn($valor) => $valor !== ''));
$imagenesNosotros = [];
foreach (['nosotros_imagen_hero', 'nosotros_imagen_institucional'] as $campo) {
    $ruta = $nosotros[$campo] ?? '';
    if (preg_match('#^uploads/tienda/nosotros_(hero|institucional)_[a-f0-9]{32}\.(jpg|png|webp)$#D', $ruta)) {
        $archivo = __DIR__ . '/../../' . $ruta;
        if (is_file($archivo) && !is_link($archivo) && dirname(realpath($archivo)) === realpath(__DIR__ . '/../../uploads/tienda')) {
            $dimensiones = @getimagesize($archivo);
            if ($dimensiones) { $imagenesNosotros[$campo] = ['ruta'=>$ruta, 'ancho'=>$dimensiones[0], 'alto'=>$dimensiones[1]]; }
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($base_url . v_tienda('css/nosotros.css'), ENT_QUOTES, 'UTF-8') ?>">
<main class="nosotros-page">
    <section class="nosotros-presentacion">
        <div class="nosotros-contenedor <?= isset($imagenesNosotros['nosotros_imagen_hero']) ? 'nosotros-hero-con-imagen' : '' ?>">
            <div class="nosotros-hero-texto">
            <span class="nosotros-etiqueta">NOSOTROS</span>
            <?php if (($nosotros['nosotros_titulo'] ?? '') !== ''): ?>
            <h1><?= htmlspecialchars($nosotros['nosotros_titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
            <?php else: ?>
            <h1>Nosotros</h1>
            <?php endif; ?>
            </div>
            <?php if (isset($imagenesNosotros['nosotros_imagen_hero'])): $imagen = $imagenesNosotros['nosotros_imagen_hero']; ?>
            <img class="nosotros-imagen-hero" src="<?= htmlspecialchars($base_url . '../' . $imagen['ruta'], ENT_QUOTES, 'UTF-8') ?>" width="<?= $imagen['ancho'] ?>" height="<?= $imagen['alto'] ?>" alt="">
            <?php endif; ?>
        </div>
    </section>
    <div class="nosotros-contenedor nosotros-secciones">
        <?php if (($nosotros['nosotros_descripcion'] ?? '') !== ''): ?>
        <section class="nosotros-tarjeta nosotros-descripcion <?= isset($imagenesNosotros['nosotros_imagen_institucional']) ? 'nosotros-descripcion-con-imagen' : '' ?>"><div><h2>Quiénes somos</h2><p><?= htmlspecialchars($nosotros['nosotros_descripcion'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <?php if (isset($imagenesNosotros['nosotros_imagen_institucional'])): $imagen = $imagenesNosotros['nosotros_imagen_institucional']; ?>
        <img class="nosotros-imagen-institucional" src="<?= htmlspecialchars($base_url . '../' . $imagen['ruta'], ENT_QUOTES, 'UTF-8') ?>" width="<?= $imagen['ancho'] ?>" height="<?= $imagen['alto'] ?>" loading="lazy" alt="">
        <?php endif; ?></section>
        <?php endif; ?>
        <?php if (($nosotros['nosotros_mision'] ?? '') !== '' || ($nosotros['nosotros_vision'] ?? '') !== ''): ?>
        <div class="nosotros-proposito">
            <?php foreach (['nosotros_mision'=>'Misión', 'nosotros_vision'=>'Visión'] as $campo => $titulo): ?>
            <?php if (($nosotros[$campo] ?? '') !== ''): ?>
            <section class="nosotros-tarjeta"><h2><?= $titulo ?></h2><p><?= htmlspecialchars($nosotros[$campo], ENT_QUOTES, 'UTF-8') ?></p></section>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($valores): ?>
        <section class="nosotros-tarjeta"><h2>Valores</h2><ul class="nosotros-valores">
            <?php foreach ($valores as $valor): ?><li><?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul></section>
        <?php endif; ?>
        <?php if (!array_filter($nosotros)): ?><p class="nosotros-aviso">La información de Nosotros estará disponible próximamente.</p><?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
