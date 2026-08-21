<?php
// procesar_contacto.php - Procesador de formulario de Contacto / PQR para AGRANDA (Fase 7)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
          isset($_GET['ajax']);

// Obtener datos ya sea por JSON o POST estándar
$inputJSON = json_decode(file_get_contents('php://input'), true);

$nombre = trim($inputJSON['nombre'] ?? $_POST['nombre'] ?? '');
$correo = trim($inputJSON['correo'] ?? $_POST['correo'] ?? '');
$telefono = trim($inputJSON['telefono'] ?? $_POST['telefono'] ?? '');
$asunto = trim($inputJSON['asunto'] ?? $_POST['asunto'] ?? '');
$mensaje = trim($inputJSON['mensaje'] ?? $_POST['mensaje'] ?? '');

// Obtener ID de usuario si está autenticado en sesión
$idUsuario = null;
if (isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] > 0) {
    $idUsuario = (int)$_SESSION['id_usuario'];
} elseif (isset($_SESSION['usuario']['id_usuario']) && (int)$_SESSION['usuario']['id_usuario'] > 0) {
    $idUsuario = (int)$_SESSION['usuario']['id_usuario'];
}

$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre es obligatorio.";
}

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Por favor ingresa un correo electrónico válido.";
}

if (empty($asunto)) {
    $errores[] = "El asunto es obligatorio.";
}

if (empty($mensaje)) {
    $errores[] = "El mensaje no puede estar vacío.";
}

if (!empty($errores)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errores' => $errores]);
        exit;
    } else {
        $_SESSION['contacto_errores'] = $errores;
        $_SESSION['contacto_datos'] = ['nombre' => $nombre, 'correo' => $correo, 'telefono' => $telefono, 'asunto' => $asunto, 'mensaje' => $mensaje];
        header("Location: contacto.php?status=error");
        exit;
    }
}

// Inserción en la tabla `contactos` usando prepared statement
$sql = "INSERT INTO contactos (id_usuario, nombre, correo, telefono, asunto, mensaje, estado, fecha_envio) 
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())";

$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("isssss", $idUsuario, $nombre, $correo, $telefono, $asunto, $mensaje);
    if ($stmt->execute()) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'mensaje' => '¡Tu mensaje ha sido enviado exitosamente! Nos pondremos en contacto contigo pronto.'
            ]);
            exit;
        } else {
            $_SESSION['contacto_exito'] = '¡Tu mensaje ha sido recibido con éxito! Un asesor técnico de AGRANDA te responderá a la brevedad.';
            header("Location: contacto.php?status=success");
            exit;
        }
    } else {
        $errorDB = "Error al guardar el mensaje en la base de datos: " . $stmt->error;
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => $errorDB]);
            exit;
        } else {
            $_SESSION['contacto_errores'] = [$errorDB];
            header("Location: contacto.php?status=error");
            exit;
        }
    }
} else {
    $errorPrep = "Error en la consulta preparada: " . $conexion->error;
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $errorPrep]);
        exit;
    } else {
        $_SESSION['contacto_errores'] = [$errorPrep];
        header("Location: contacto.php?status=error");
        exit;
    }
}
?>
