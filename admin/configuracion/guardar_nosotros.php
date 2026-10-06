<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) {
    header('Location: ../login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nosotros.php');
    exit();
}
function errorConfiguracionNosotros(string $mensaje, array $valores = []): void
{
    $_SESSION['config_nosotros_error'] = $mensaje;
    $_SESSION['config_nosotros_valores'] = $valores;
    header('Location: nosotros.php');
    exit();
}
$csrf = $_POST['csrf'] ?? null;
$token = $_SESSION['csrf_config_nosotros'] ?? null;
if (!is_string($csrf) || !is_string($token) || $token === '' || !hash_equals($token, $csrf)) {
    errorConfiguracionNosotros('La solicitud expiró o no es válida. Recarga la página e inténtalo nuevamente.');
}
$limites = ['nosotros_titulo'=>150, 'nosotros_descripcion'=>5000, 'nosotros_mision'=>2000, 'nosotros_vision'=>2000, 'nosotros_valores'=>1209];
$valores = [];
foreach ($limites as $campo => $limite) {
    $valor = $_POST[$campo] ?? null;
    if (!is_string($valor) || !mb_check_encoding($valor, 'UTF-8')) {
        errorConfiguracionNosotros('Todos los campos deben contener texto válido.', $valores);
    }
    $valores[$campo] = trim(str_replace(["\r\n", "\r"], "\n", $valor));
}
foreach ($limites as $campo => $limite) {
    $valor = $valores[$campo];
    if (mb_strlen($valor, 'UTF-8') > $limite) {
        errorConfiguracionNosotros('Se superó la longitud máxima de uno de los campos. Revisa los límites indicados.', $valores);
    }
    if ($campo !== 'nosotros_valores' && $valor === '') {
        errorConfiguracionNosotros('Completa el título, Quiénes somos, Misión y Visión.', $valores);
    }
    if (preg_match('/[<>]|[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $valor)) {
        errorConfiguracionNosotros('Utiliza texto plano, sin etiquetas HTML ni caracteres de control.', $valores);
    }
}
$lineas = array_values(array_filter(array_map('trim', explode("\n", $valores['nosotros_valores'])), static fn($valor) => $valor !== ''));
if (count($lineas) > 10) {
    errorConfiguracionNosotros('Puedes guardar como máximo 10 valores, uno por línea.', $valores);
}
foreach ($lineas as $linea) {
    if (mb_strlen($linea, 'UTF-8') > 120) {
        errorConfiguracionNosotros('Cada valor puede contener como máximo 120 caracteres.', $valores);
    }
}
$valores['nosotros_valores'] = implode("\n", $lineas);
require_once __DIR__ . '/../../config/conexion.php';
$carpeta = realpath(__DIR__ . '/../../uploads/tienda');
$archivosNuevos = [];
$archivosAntiguos = [];
$transaccion = false;
function rutaImagenNosotrosSegura(?string $ruta, string $carpeta): ?string
{
    if (!$ruta || !preg_match('#^uploads/tienda/nosotros_(hero|institucional)_[a-f0-9]{32}\.(jpg|png|webp)$#D', $ruta)) {
        return null;
    }
    $archivo = $carpeta . DIRECTORY_SEPARATOR . basename($ruta);
    $real = realpath($archivo);
    if ($real === false || is_link($archivo) || dirname($real) !== $carpeta || !is_file($real)) {
        return null;
    }
    return $real;
}
try {
    if ($carpeta === false) { throw new RuntimeException('La carpeta de imágenes no está disponible.'); }
    $imagenes = [];
    $tipos = ['image/jpeg'=>['jpg','jpeg'], 'image/png'=>['png'], 'image/webp'=>['webp']];
    foreach (['nosotros_imagen_hero'=>'hero', 'nosotros_imagen_institucional'=>'institucional'] as $campo => $prefijo) {
        $eliminar = $_POST['eliminar_' . $campo] ?? null;
        if ($eliminar !== null && $eliminar !== '1') { throw new RuntimeException('La opción de eliminación no es válida.'); }
        $archivo = $_FILES[$campo] ?? null;
        $hayArchivo = $archivo !== null && ($archivo['error'] ?? null) !== UPLOAD_ERR_NO_FILE;
        if ($eliminar && $hayArchivo) { throw new RuntimeException('No puedes eliminar y reemplazar la misma imagen simultáneamente.'); }
        if ($eliminar) { $imagenes[$campo] = null; continue; }
        if (!$hayArchivo) { continue; }
        if (!is_array($archivo) || !isset($archivo['error'], $archivo['tmp_name'], $archivo['name']) || !is_int($archivo['error']) || !is_string($archivo['tmp_name']) || !is_string($archivo['name'])) {
            throw new RuntimeException('El archivo recibido no es válido.');
        }
        if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) { throw new RuntimeException('Cada imagen puede pesar como máximo 2 MB.'); }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) { throw new RuntimeException('No fue posible recibir la imagen. Selecciónala nuevamente.'); }
        $tamano = filesize($archivo['tmp_name']);
        if ($tamano === false || $tamano < 1 || $tamano > 2 * 1024 * 1024) { throw new RuntimeException('Cada imagen puede pesar como máximo 2 MB.'); }
        if (!class_exists('finfo') || !function_exists('getimagesize')) {
            throw new RuntimeException('El servidor no dispone de las funciones necesarias para validar imágenes. Contacta al administrador del hosting.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!isset($tipos[$mime]) || !in_array($extension, $tipos[$mime], true)) { throw new RuntimeException('Solo se permiten JPG/JPEG, PNG y WEBP con extensión y contenido compatibles.'); }
        $dimensiones = @getimagesize($archivo['tmp_name']);
        if (!$dimensiones || ($dimensiones['mime'] ?? '') !== $mime || $dimensiones[0] < 1 || $dimensiones[1] < 1 || $dimensiones[0] > 8000 || $dimensiones[1] > 8000 || $dimensiones[0] * $dimensiones[1] > 20000000) {
            throw new RuntimeException('La imagen no es legible o sus dimensiones son excesivas (máximo 8000 px por lado y 20 megapíxeles).');
        }
        if (!extension_loaded('gd') || !function_exists('imagecreatefromstring') || !function_exists('imagedestroy') || !function_exists('gd_info')) {
            throw new RuntimeException('La subida de imágenes no está disponible: el servidor necesita GD para validar su contenido. Puedes continuar editando los textos.');
        }
        $soporte = ['image/jpeg'=>'JPEG Support', 'image/png'=>'PNG Support', 'image/webp'=>'WebP Support'];
        $capacidades = gd_info();
        if (empty($capacidades[$soporte[$mime]])) {
            throw new RuntimeException('El servidor no permite validar este formato de imagen. Utiliza otro formato admitido por GD o consulta al proveedor del hosting.');
        }
        $imagen = @imagecreatefromstring(file_get_contents($archivo['tmp_name']));
        if ($imagen === false) { throw new RuntimeException('La imagen está dañada o no es legible.'); }
        imagedestroy($imagen);
        // Reservar un nombre exclusivo evita sobrescribir incluso ante una colisión.
        $reserva = false;
        for ($intento = 0; $intento < 5; $intento++) {
            $nombre = 'nosotros_' . $prefijo . '_' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime][0];
            $destino = $carpeta . DIRECTORY_SEPARATOR . $nombre;
            $reserva = @fopen($destino, 'x');
            if ($reserva !== false) { break; }
        }
        if ($reserva === false) { throw new RuntimeException('No fue posible guardar la imagen en la carpeta autorizada.'); }
        fclose($reserva);
        $archivosNuevos[] = $destino;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) { throw new RuntimeException('No fue posible guardar la imagen.'); }
        $imagenes[$campo] = 'uploads/tienda/' . $nombre;
    }
    $conexion->begin_transaction();
    $transaccion = true;
    $id = 1;
    $stmt = $conexion->prepare('SELECT nosotros_imagen_hero, nosotros_imagen_institucional FROM configuracion_tienda WHERE id_configuracion = ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$existe) { throw new RuntimeException('No se encontró la configuración de la tienda.'); }
    foreach ($existe as $campo => $ruta) {
        if (!array_key_exists($campo, $imagenes)) { $imagenes[$campo] = $ruta; }
        elseif ($ruta !== $imagenes[$campo]) {
            $segura = rutaImagenNosotrosSegura($ruta, $carpeta);
            if ($segura !== null) { $archivosAntiguos[] = $segura; }
        }
    }
    $stmt = $conexion->prepare('UPDATE configuracion_tienda SET nosotros_titulo = ?, nosotros_descripcion = ?, nosotros_mision = ?, nosotros_vision = ?, nosotros_valores = ?, nosotros_imagen_hero = ?, nosotros_imagen_institucional = ? WHERE id_configuracion = ? LIMIT 1');
    $stmt->bind_param('sssssssi', $valores['nosotros_titulo'], $valores['nosotros_descripcion'], $valores['nosotros_mision'], $valores['nosotros_vision'], $valores['nosotros_valores'], $imagenes['nosotros_imagen_hero'], $imagenes['nosotros_imagen_institucional'], $id);
    if (!$stmt->execute()) { throw new RuntimeException('Guardado fallido'); }
    $stmt->close();
    $conexion->commit();
    $transaccion = false;
} catch (Throwable $error) {
    if ($transaccion) { $conexion->rollback(); }
    foreach ($archivosNuevos as $archivoNuevo) {
        if (is_file($archivoNuevo) && !is_link($archivoNuevo) && dirname(realpath($archivoNuevo)) === $carpeta) { unlink($archivoNuevo); }
    }
    $mensaje = $error instanceof RuntimeException ? $error->getMessage() : 'No fue posible guardar Nosotros. Inténtalo nuevamente.';
    errorConfiguracionNosotros($mensaje . ' Si seleccionaste archivos, vuelve a seleccionarlos.', $valores);
}
$limpiezaCorrecta = true;
foreach ($archivosAntiguos as $archivoAntiguo) {
    $ruta = 'uploads/tienda/' . basename($archivoAntiguo);
    // No eliminar un archivo todavía utilizado por la otra imagen.
    if (!in_array($ruta, $imagenes, true) && rutaImagenNosotrosSegura($ruta, $carpeta) !== null && !@unlink($archivoAntiguo)) { $limpiezaCorrecta = false; }
}
$_SESSION['csrf_config_nosotros'] = bin2hex(random_bytes(32));
$_SESSION['config_nosotros_exito'] = 'La información de Nosotros se guardó correctamente.';
if (!$limpiezaCorrecta) { $_SESSION['config_nosotros_exito'] .= ' No fue posible eliminar un archivo anterior; revisa los permisos de la carpeta.'; }
unset($_SESSION['config_nosotros_error'], $_SESSION['config_nosotros_valores']);
header('Location: nosotros.php');
exit();
