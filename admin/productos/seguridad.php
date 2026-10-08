<?php
// Token exclusivo del módulo; la autorización administrativa se comprueba en cada ruta.
function agranda_productos_csrf(): string {
    if (!is_string($_SESSION['csrf_productos_admin'] ?? null)) {
        $_SESSION['csrf_productos_admin'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_productos_admin'];
}
function agranda_productos_validar_csrf(): void {
    $token = $_POST['csrf_productos_admin'] ?? null;
    $sesion = $_SESSION['csrf_productos_admin'] ?? null;
    if (!is_string($token) || !is_string($sesion) || !hash_equals($sesion, $token)) {
        http_response_code(403);
        exit('La solicitud no es válida. Vuelve al formulario e inténtalo nuevamente.');
    }
    // Rechaza arrays manipulados antes de trim, mb_strlen o conversiones.
    foreach ($_POST as $campo => $valor) {
        if ($campo === 'atributo_id' || $campo === 'atributo_valor') {
            if (!is_array($valor) || array_filter($valor, fn($v) => !is_string($v))) {
                http_response_code(400);
                exit('Los datos de las características no son válidos.');
            }
        } elseif (!is_string($valor)) {
            http_response_code(400);
            exit('Los datos del formulario no son válidos.');
        }
    }
}
function agranda_productos_exigir_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Esta operación requiere un formulario POST.');
    }
    agranda_productos_validar_csrf();
}
function agranda_productos_campo_csrf(): string {
    return '<input type="hidden" name="csrf_productos_admin" value="'
        . htmlspecialchars(agranda_productos_csrf(), ENT_QUOTES, 'UTF-8') . '">';
}
