<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Método no permitido.');
}
$ruta = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$pos = strpos($ruta, '/tienda');
$base_url = $pos === false ? '/tienda/' : substr($ruta, 0, $pos + 7) . '/';
$idUsuario = filter_var($_SESSION['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if (!$idUsuario || !in_array($_SESSION['rol'] ?? null, ['cliente', 'administrador'], true)) {
    http_response_code(401); exit('Inicia sesión para guardar tu reseña.');
}
$token = $_POST['csrf_resenas'] ?? null;
if (!is_string($token) || !is_string($_SESSION['csrf_resenas'] ?? null)
    || !hash_equals($_SESSION['csrf_resenas'], $token)) {
    http_response_code(403); exit('La solicitud no es válida. Recarga el formulario.');
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
$producto = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT);
$modo = $_POST['modo'] ?? null;
$idResena = filter_var($_POST['id_resena'] ?? null, FILTER_VALIDATE_INT);
if (!$producto || $producto < 1 || !is_string($modo) || !in_array($modo, ['crear', 'editar'], true)
    || ($modo === 'editar' && (!$idResena || $idResena < 1))) {
    http_response_code(400); exit('La operación no es válida.');
}
try {
    $resultado = agranda_resena_guardar($conexion, $idUsuario, $producto, $_POST['calificacion'] ?? null, $_POST['comentario'] ?? null, $modo, $modo === 'editar' ? $idResena : null);
    $mensajes = ['creada' => 'Tu reseña fue publicada correctamente.', 'editada' => 'Tu reseña fue actualizada correctamente.',
        'existente' => 'Ya tienes una reseña de este producto. Puedes verla o editarla aquí.'];
    $_SESSION['resenas_aviso'][$producto] = ['mensaje' => $mensajes[$resultado]];
} catch (InvalidArgumentException $error) {
    $comentario = $_POST['comentario'] ?? '';
    $_SESSION['resenas_aviso'][$producto] = ['mensaje' => $error->getMessage(), 'contenido' => [
        'calificacion' => is_scalar($_POST['calificacion'] ?? null) ? (string) $_POST['calificacion'] : '',
        'comentario' => is_string($comentario) && mb_check_encoding($comentario, 'UTF-8') ? mb_substr($comentario, 0, 65535, 'UTF-8') : ''
    ]];
} catch (DomainException $error) {
    http_response_code(403); exit(htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
} catch (Throwable $error) {
    error_log('Reseñas: ' . $error->getMessage());
    http_response_code(500); exit('No fue posible guardar tu reseña. Intenta nuevamente.');
}
header('Location: ' . $base_url . 'resenas/formulario.php?id_producto=' . $producto, true, 303);
exit;
