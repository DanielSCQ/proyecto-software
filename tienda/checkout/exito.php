<?php
// Esta vista solo consulta una compra ya confirmada; nunca procesa pedidos.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$posTienda = strpos($scriptDir, '/tienda');
$base_url = $posTienda !== false
    ? substr($scriptDir, 0, $posTienda + strlen('/tienda')) . '/'
    : '/tienda/';

$clienteLogueado = isset($_SESSION['id_usuario'])
    && is_numeric($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0
    && in_array($_SESSION['rol'] ?? null, ['cliente', 'administrador'], true);

if (!$clienteLogueado) {
    header('Location: ' . $base_url . 'cuenta/login.php');
    exit;
}

header('Cache-Control: no-store, private');
$idPedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idPedido || $idPedido <= 0) {
    http_response_code(404);
    exit('El pedido solicitado no existe o no está disponible.');
}

require_once __DIR__ . '/../../config/conexion.php';
$idUsuario = (int) $_SESSION['id_usuario'];
try {
    // El usuario siempre procede de la sesión, incluso para administradores.
    $stmt = $conexion->prepare("SELECT p.id_pedido, p.metodo_pago,
        EXISTS (SELECT 1 FROM pagos pg WHERE pg.id_pedido = p.id_pedido
            AND pg.metodo_pago = 'Pago simulado' AND pg.estado = 'Aprobado') AS pago_aprobado
        FROM pedidos p WHERE p.id_pedido = ? AND p.id_usuario = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('No fue posible consultar el pedido.');
    }
    $stmt->bind_param('ii', $idPedido, $idUsuario);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Throwable $error) {
    http_response_code(500);
    exit('No fue posible consultar el pedido en este momento.');
}

if (!$pedido) {
    http_response_code(404);
    exit('El pedido solicitado no existe o no está disponible.');
}

$pagoSimuladoAprobado = $pedido['metodo_pago'] === 'Pago simulado'
    && (int) $pedido['pago_aprobado'] === 1;
$titulo = $pagoSimuladoAprobado ? 'Compra realizada con éxito' : 'Pedido realizado con éxito';
$mensaje = $pagoSimuladoAprobado
    ? 'Tu pago y tu pedido se han procesado correctamente.'
    : 'Tu pedido ha sido registrado correctamente.';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($base_url . v_tienda('css/checkout-exito.css'), ENT_QUOTES, 'UTF-8') ?>">
<main class="checkout-exito-page">
    <section class="checkout-exito-card" aria-labelledby="checkout-exito-titulo">
        <div class="checkout-exito-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"/></svg>
        </div>
        <div class="checkout-encabezado">
            <h1 id="checkout-exito-titulo"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <p class="checkout-exito-mensaje"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="checkout-exito-numero">Pedido <code>#<?= (int) $pedido['id_pedido'] ?></code></p>
        <a class="boton-checkout-exito" href="<?= htmlspecialchars($base_url . 'productos/', ENT_QUOTES, 'UTF-8') ?>">Seguir comprando</a>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
