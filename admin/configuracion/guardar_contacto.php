<?php

session_start();
require_once __DIR__ . "/../includes/auth.php";

if (!agranda_admin_autorizado()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contacto.php");
    exit();
}

function errorConfiguracionContacto(string $mensaje, ?string $valor = null): void
{
    $_SESSION["config_contacto_error"] = $mensaje;
    if ($valor !== null) {
        $_SESSION["config_contacto_valor"] = $valor;
    }
    header("Location: contacto.php");
    exit();
}

$csrf = $_POST["csrf"] ?? null;
$csrfSesion = $_SESSION["csrf_config_contacto"] ?? null;

if (
    !is_string($csrf) || !is_string($csrfSesion) ||
    $csrfSesion === "" || !hash_equals($csrfSesion, $csrf)
) {
    errorConfiguracionContacto("La solicitud expiró o no es válida. Recarga la página e inténtalo nuevamente.");
}

$telefonoContacto = $_POST["telefono_contacto"] ?? null;

if (!is_string($telefonoContacto) || strlen($telefonoContacto) > 40) {
    errorConfiguracionContacto("Ingresa un celular colombiano válido, con o sin el código 57.");
}

$telefonoContacto = trim($telefonoContacto);
$numeroWhatsApp = null;

if ($telefonoContacto !== "") {
    if (!preg_match('/^\+?[0-9 ()-]+$/D', $telefonoContacto)) {
        errorConfiguracionContacto("El número solo puede contener dígitos, espacios, guiones y paréntesis; el signo + solo se permite al inicio.", $telefonoContacto);
    }

    $digitos = preg_replace('/[ ()+\-]/', '', $telefonoContacto);
    $conPrefijo = str_starts_with($telefonoContacto, "+");

    if (preg_match('/^573[0-9]{9}$/D', $digitos)) {
        $numeroWhatsApp = $digitos;
    } elseif (!$conPrefijo && preg_match('/^3[0-9]{9}$/D', $digitos)) {
        $numeroWhatsApp = "57" . $digitos;
    } else {
        errorConfiguracionContacto("Ingresa un celular colombiano de 10 dígitos que comience por 3, o su formato internacional con +57.", $telefonoContacto);
    }
}

require_once __DIR__ . "/../../config/conexion.php";

try {
    $idConfiguracion = 1;
    $stmt = $conexion->prepare("SELECT id_configuracion FROM configuracion_tienda WHERE id_configuracion = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("No fue posible consultar la configuración.");
    }
    $stmt->bind_param("i", $idConfiguracion);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existe) {
        errorConfiguracionContacto("No se encontró la configuración de la tienda.", $telefonoContacto);
    }

    $stmt = $conexion->prepare("UPDATE configuracion_tienda SET telefono_contacto = ? WHERE id_configuracion = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("No fue posible preparar el guardado.");
    }
    $stmt->bind_param("si", $numeroWhatsApp, $idConfiguracion);
    if (!$stmt->execute()) {
        throw new RuntimeException("No fue posible guardar el teléfono.");
    }
    $stmt->close();
} catch (Throwable $error) {
    errorConfiguracionContacto("No fue posible guardar el teléfono. Inténtalo nuevamente.", $telefonoContacto);
}

$_SESSION["csrf_config_contacto"] = bin2hex(random_bytes(32));
$_SESSION["config_contacto_exito"] = $numeroWhatsApp === null
    ? "Configuración guardada. La atención por WhatsApp está desactivada."
    : "Teléfono de WhatsApp guardado correctamente.";
unset($_SESSION["config_contacto_error"], $_SESSION["config_contacto_valor"]);

header("Location: contacto.php");
exit();
