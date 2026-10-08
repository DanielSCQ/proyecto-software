<?php
session_start();
$ruta = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$pos = strpos($ruta, '/tienda');
$base_url = $pos === false ? '/tienda/' : substr($ruta, 0, $pos + 7) . '/';
$idUsuario = filter_var($_SESSION['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if (!$idUsuario || !in_array($_SESSION['rol'] ?? null, ['cliente', 'administrador'], true)) {
    header('Location: ' . $base_url . 'cuenta/login.php'); exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
$productoId = filter_var($_GET['id_producto'] ?? null, FILTER_VALIDATE_INT);
if (!agranda_resena_usuario_activo($conexion, $idUsuario)) { http_response_code(403); exit('No tienes acceso a esta sección.'); }
if (!$productoId || $productoId < 1 || !($producto = agranda_resena_producto($conexion, $productoId))) {
    http_response_code(404); exit('Producto no encontrado.');
}
$elegibilidad = agranda_resena_elegibilidad($conexion, $idUsuario, $productoId);
$resena = $elegibilidad['resena'];
if (!$resena && !$elegibilidad['puede_crear']) {
    http_response_code(403); exit('Solo puedes calificar productos de pedidos entregados.');
}
$editable = $elegibilidad['puede_crear'] || $elegibilidad['puede_editar'];
if (!isset($_SESSION['csrf_resenas']) || !is_string($_SESSION['csrf_resenas'])) {
    $_SESSION['csrf_resenas'] = bin2hex(random_bytes(32));
}
$aviso = $_SESSION['resenas_aviso'][$productoId] ?? null;
unset($_SESSION['resenas_aviso'][$productoId]);
$calificacion = $resena['calificacion'] ?? '';
$comentario = $resena['comentario'] ?? '';
if (isset($aviso['contenido']) && $editable) {
    $calificacion = $aviso['contenido']['calificacion'];
    $comentario = $aviso['contenido']['comentario'];
}
$esc = static fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
require __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= $esc($base_url . v_tienda('css/resenas.css')) ?>">
<main class="resena-page resena-form-page">
    <section class="resena-panel" aria-labelledby="resena-titulo">
        <a class="resena-volver" href="<?= $esc($base_url) ?>cuenta/"><span aria-hidden="true">←</span> Volver a Mi cuenta</a>
        <p class="resena-etiqueta">TU EXPERIENCIA CUENTA</p>
        <h1 id="resena-titulo"><?= $resena ? 'Tu reseña' : 'Calificar producto' ?></h1>
        <div class="resena-producto"><h2><?= $esc($producto['nombre']) ?></h2>
        <p class="resena-referencia">Referencia: <?= $esc($producto['codigo_producto']) ?></p>
        <p class="resena-verificada"><?= !$resena || (int) $resena['compra_verificada'] === 1 ? 'Compra verificada' : 'Tu reseña de este producto' ?></p></div>
        <?php if ($aviso): ?><p class="resena-aviso <?= isset($aviso['contenido']) ? 'resena-aviso-error' : 'resena-aviso-exito' ?>" role="status"><?= $esc($aviso['mensaje']) ?></p><?php endif; ?>
        <?php if ($resena): ?>
            <p class="resena-fechas">Creada: <?= $esc(date('d/m/Y H:i', strtotime($resena['fecha']))) ?>
                <?php if ($resena['fecha_actualizacion']): ?><br>Actualizada: <?= $esc(date('d/m/Y H:i', strtotime($resena['fecha_actualizacion']))) ?><?php endif; ?>
            </p>
            <p><?= (int) $resena['estado'] === 1 ? 'Reseña publicada.' : 'Reseña oculta. Editarla no la vuelve a publicar.' ?></p>
        <?php endif; ?>
        <?php if (!$editable): ?>
            <p class="resena-aviso">No puedes editar esta reseña porque actualmente no tienes una compra entregada válida de este producto.</p>
        <?php endif; ?>
        <?php if ($resena && mb_strlen($resena['comentario'], 'UTF-8') > 150): ?>
            <p class="resena-aviso">Tu comentario anterior se conserva completo. Para guardar cambios, ajústalo a un máximo de 150 caracteres.</p>
        <?php endif; ?>
        <form action="<?= $esc($base_url) ?>resenas/guardar.php" method="post">
            <input type="hidden" name="csrf_resenas" value="<?= $esc($_SESSION['csrf_resenas']) ?>">
            <input type="hidden" name="id_producto" value="<?= (int) $productoId ?>">
            <input type="hidden" name="modo" value="<?= $resena ? 'editar' : 'crear' ?>">
            <?php if ($resena): ?><input type="hidden" name="id_resena" value="<?= (int) $resena['id_resena'] ?>"><?php endif; ?>
            <fieldset class="resena-calificacion" <?= $editable ? '' : 'disabled' ?>>
                <legend>Calificación (obligatoria)</legend>
                <div class="resena-estrellas">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="resena-estrella">
                            <input type="radio" name="calificacion" value="<?= $i ?>" required <?= (string) $calificacion === (string) $i ? 'checked' : '' ?>>
                            <span aria-hidden="true"></span><span class="resena-sr"><?= $i ?> <?= $i === 1 ? 'estrella' : 'estrellas' ?></span>
                        </label>
                    <?php endfor; ?>
                </div>
            </fieldset>
            <label for="resena-comentario">Comentario (obligatorio)</label>
            <!-- HTML cuenta unidades UTF-16: 300 admite 150 emojis; PHP y JS validan 150 caracteres Unicode. -->
            <textarea id="resena-comentario" name="comentario" rows="5" maxlength="300" data-max-caracteres="150" aria-describedby="resena-ayuda resena-limite" required <?= $editable ? '' : 'readonly' ?>><?= $esc($comentario) ?></textarea>
            <p class="resena-ayuda" id="resena-ayuda">Comparte tu experiencia en un máximo de 150 caracteres.</p>
            <p class="resena-contador" id="resena-limite"><output id="resena-contador" for="resena-comentario"><?= mb_strlen($comentario, 'UTF-8') ?></output> / 150</p>
            <?php if ($editable): ?><button class="resena-guardar" type="submit"><?= $resena ? 'Guardar cambios' : 'Publicar reseña' ?></button><?php endif; ?>
        </form>
    </section>
</main>
<script>
(() => {
    const comentario = document.getElementById('resena-comentario');
    const contador = document.getElementById('resena-contador');
    const actualizar = () => {
        const longitud = Array.from(comentario.value).length;
        contador.textContent = longitud;
        comentario.setCustomValidity(longitud > 150 ? 'Escribe un comentario de 1 a 150 caracteres.' : '');
        document.getElementById('resena-limite').classList.toggle('resena-limite-excedido', longitud > 150);
    };
    comentario.addEventListener('input', actualizar);
    actualizar();
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
