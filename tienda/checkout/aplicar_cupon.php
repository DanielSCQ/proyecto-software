<?php
session_start();
if (!isset($_SESSION['id_usuario']) || (int) $_SESSION['id_usuario'] < 1 || !in_array($_SESSION['rol'] ?? null, ['cliente','administrador'], true)) { header('Location: ../cuenta/login.php'); exit(); }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: index.php'); exit(); }
$token = $_POST['csrf'] ?? null;
if (!is_string($token) || !is_string($_SESSION['csrf_checkout'] ?? null) || !hash_equals($_SESSION['csrf_checkout'], $token)) { $_SESSION['checkout_error'] = 'La solicitud no es válida. Recarga el checkout.'; header('Location: index.php'); exit(); }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/cupones.php';
try {
    $usuario = (int) $_SESSION['id_usuario'];
    $s = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND estado = 1 AND rol IN ('cliente','administrador')");
    $s->bind_param('i', $usuario); $s->execute(); $activo = $s->get_result()->fetch_assoc(); $s->close();
    if (!$activo) { throw new RuntimeException('La cuenta no está disponible.'); }
    if (($_POST['accion'] ?? '') === 'retirar') {
        unset($_SESSION['cupon_checkout'], $_SESSION['pago_simulado_aprobado']);
        $_SESSION['cupon_checkout_exito'] = 'Cupón retirado.';
    } elseif (($_POST['accion'] ?? '') === 'aplicar') {
        if (!is_string($_POST['codigo'] ?? null)) { throw new RuntimeException('Introduce un código válido.'); }
        $cupon = agranda_cupon_buscar($conexion, $_POST['codigo']);
        if (!$cupon) { throw new RuntimeException('No existe un cupón con ese código.'); }
        agranda_cupon_validar($conexion, $cupon, $usuario, agranda_cupon_subtotal_carrito($conexion, $_SESSION['carrito'] ?? []));
        $_SESSION['cupon_checkout'] = ['codigo'=>$cupon['codigo'], 'id_usuario'=>$usuario];
        unset($_SESSION['pago_simulado_aprobado']);
        $_SESSION['cupon_checkout_exito'] = 'Cupón aplicado. Se comprobará nuevamente al confirmar.';
    } else { throw new RuntimeException('La acción no es válida.'); }
} catch (RuntimeException | InvalidArgumentException $error) { $_SESSION['checkout_error'] = $error->getMessage(); }
catch (Throwable $error) { $_SESSION['checkout_error'] = 'No fue posible aplicar el cupón. Inténtalo nuevamente.'; }
header('Location: index.php'); exit();
