<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$json = strpos(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
header('Cache-Control: no-store, private');
function agranda_carrito_responder(bool $json, array $resultado, int $codigo): void
{
    if ($json) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($codigo);
        unset($resultado['codigo']);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } else {
        $_SESSION['carrito_mensaje'] = $resultado['message'] ?? '';
        if (isset($resultado['estado'])) {
            $_SESSION['carrito_avisos'] = array_values(array_unique(array_merge(
                $_SESSION['carrito_avisos'] ?? [], $resultado['estado']['ajustes'])));
        }
        header('Location: ./', true, 303);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    agranda_carrito_responder($json, ['success' => false, 'message' => 'Método no permitido.'], 405);
}
$csrf = $_POST['csrf'] ?? '';
$csrfSesion = $_SESSION['csrf_carrito'] ?? '';
if (!is_string($csrf) || !is_string($csrfSesion) || $csrf === '' || $csrfSesion === ''
    || !hash_equals($csrfSesion, $csrf)) {
    agranda_carrito_responder($json, ['success' => false,
        'message' => 'La solicitud no es válida. Recarga el carrito.'], 403);
}
$id = filter_input(INPUT_POST, 'producto', FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';
if (!in_array($accion, ['sumar', 'restar', 'consultar'], true)
    || ($accion !== 'consultar' && (!$id || $id < 1))) {
    agranda_carrito_responder($json, ['success' => false,
        'message' => 'La solicitud del carrito no es válida.'], 400);
}
$anterior = is_array($_SESSION['carrito'] ?? null) ? $_SESSION['carrito'] : [];
try {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/carrito.php';
    $_SESSION['carrito'] = $anterior;
    if ($accion === 'consultar') {
        // Recuperar estado sin repetir un incremento/decremento de resultado incierto.
        $resultado = ['success' => true, 'message' => '',
            'estado' => agranda_carrito_estado($conexion, $_SESSION['carrito']), 'codigo' => 200];
    } else {
        $resultado = agranda_carrito_cambiar($conexion, $_SESSION['carrito'], $id, $accion);
    }
} catch (Throwable $error) {
    $_SESSION['carrito'] = $anterior;
    agranda_carrito_responder($json, ['success' => false,
        'message' => 'No fue posible actualizar tu carrito. Inténtalo nuevamente.'], 503);
}
agranda_carrito_responder($json, $resultado, $resultado['codigo']);