<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ./");
    exit;
}


// =================================
// CSRF
// =================================

$csrf =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_carrito"] ?? "";


if (
    !is_string($csrf) ||
    !is_string($csrfSesion) ||
    $csrf === "" ||
    $csrfSesion === "" ||
    !hash_equals($csrfSesion, $csrf)
) {

    header("Location: ./");
    exit;
}


// =================================
// PRODUCTO
// =================================

$productoId =
    filter_input(
        INPUT_POST,
        "producto",
        FILTER_VALIDATE_INT
    );


if (
    $productoId !== false &&
    $productoId !== null &&
    $productoId > 0 &&
    isset($_SESSION["carrito"][$productoId])
) {

    unset(
        $_SESSION["carrito"][$productoId]
    );
}


header("Location: ./");
exit;