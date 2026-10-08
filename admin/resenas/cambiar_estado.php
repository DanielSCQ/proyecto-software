<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Método no permitido.');
}
if (!agranda_admin_autorizado()) {
    http_response_code(403); exit('No tienes autorización para moderar reseñas.');
}
// Nunca aceptar una URL de retorno: reconstruir únicamente la ruta interna.
require_once __DIR__ . '/../productos/contexto_catalogo.php';
try { $contextoCatalogo = agranda_catalogo_contexto_enlace(agranda_catalogo_contexto($_POST)); }
catch (InvalidArgumentException $e) { http_response_code(400); exit('Los parámetros de regreso no son válidos.'); }
$idProducto = $_POST['id_producto'] ?? '';
if (!is_string($idProducto) || ($idProducto !== '' && (!preg_match('/^[1-9][0-9]*$/D', $idProducto)
    || filter_var($idProducto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false))) {
    http_response_code(400); exit('El identificador del producto no es válido.');
}
$filtros = [];
foreach (['busqueda', 'estado', 'calificacion', 'verificada', 'pagina'] as $campo) {
    if (isset($_POST[$campo]) && !is_string($_POST[$campo])) {
        http_response_code(400); exit('Los parámetros de búsqueda no son válidos.');
    }
}
if ($idProducto !== '') $filtros['id_producto'] = $idProducto;
$filtros += $contextoCatalogo;
$buscar = $_POST['busqueda'] ?? '';
if (is_string($buscar) && mb_check_encoding($buscar, 'UTF-8') && mb_strlen($buscar, 'UTF-8') <= 200) {
    $filtros['busqueda'] = trim($buscar);
}
foreach (['estado' => ['', '0', '1'], 'calificacion' => ['', '1', '2', '3', '4', '5'], 'verificada' => ['', '0', '1']] as $nombre => $permitidos) {
    $valor = $_POST[$nombre] ?? '';
    if (is_string($valor) && in_array($valor, $permitidos, true)) { $filtros[$nombre] = $valor; }
}
$pagina = $_POST['pagina'] ?? '1';
if (is_string($pagina) && preg_match('/^[1-9][0-9]{0,8}$/D', $pagina)) { $filtros['pagina'] = $pagina; }
$retorno = 'resenas.php?' . http_build_query($filtros);
function resenas_admin_resultado(string $texto, string $tipo, string $retorno): void {
    $_SESSION['resenas_admin_mensaje'] = ['texto' => $texto, 'tipo' => $tipo];
    header('Location: ' . $retorno, true, 303); exit;
}
$csrf = $_POST['csrf_resenas_admin'] ?? null;
if (!is_string($csrf) || !is_string($_SESSION['csrf_resenas_admin'] ?? null)
    || !hash_equals($_SESSION['csrf_resenas_admin'], $csrf)) {
    resenas_admin_resultado('La solicitud no es válida o expiró. Recarga el listado e intenta nuevamente.', 'error', $retorno);
}
$id = $_POST['id_resena'] ?? null;
$accion = $_POST['accion'] ?? null;
if (!is_string($id) || !preg_match('/^[1-9][0-9]*$/D', $id)
    || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false
    || !is_string($accion) || !in_array($accion, ['ocultar', 'publicar'], true)) {
    resenas_admin_resultado('La acción o la reseña indicada no es válida.', 'error', $retorno);
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/resenas.php';
$id = (int) $id; $nuevoEstado = $accion === 'publicar' ? 1 : 0;
try {
    $conexion->begin_transaction();
    $stmt = agranda_resena_preparar($conexion, 'SELECT estado, id_producto FROM resenas WHERE id_resena = ? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $resena = agranda_resena_ejecutar($stmt)->fetch_assoc(); $stmt->close();
    if (!$resena) {
        $conexion->rollback();
        resenas_admin_resultado('La reseña indicada no existe.', 'error', $retorno);
    }
    if ($idProducto !== '' && (int) $resena['id_producto'] !== (int) $idProducto) {
        $conexion->rollback();
        resenas_admin_resultado('La reseña no corresponde al producto consultado.', 'error', $retorno);
    }
    $estadoActual = (int) $resena['estado'];
    if (!in_array($estadoActual, [0, 1], true)) { throw new RuntimeException('Estado de reseña inválido.'); }
    if ($estadoActual !== $nuevoEstado) {
        // Moderación: no actualizar contenido, trazabilidad ni fechas del comprador.
        $stmt = agranda_resena_preparar($conexion, 'UPDATE resenas SET estado = ? WHERE id_resena = ?');
        $stmt->bind_param('ii', $nuevoEstado, $id); $stmt->execute(); $stmt->close();
    }
    $conexion->commit();
    $texto = $estadoActual === $nuevoEstado
        ? ($nuevoEstado === 1 ? 'La reseña ya estaba publicada.' : 'La reseña ya estaba oculta.')
        : ($nuevoEstado === 1 ? 'Reseña publicada correctamente. Será visible si su compra está verificada.' : 'Reseña ocultada correctamente.');
    resenas_admin_resultado($texto, 'exito', $retorno);
} catch (Throwable $error) {
    $conexion->rollback();
    error_log('Moderación de reseñas: ' . $error->getMessage());
    resenas_admin_resultado('No fue posible cambiar la publicación de la reseña. Intenta nuevamente.', 'error', $retorno);
}
