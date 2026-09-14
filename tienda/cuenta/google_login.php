<?php

// ==========================================
// SESIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// CARGAR CONFIGURACIÓN DE GOOGLE
// ==========================================
$configGoogle = require __DIR__ . "/../../config/google_oauth.php";


// ==========================================
// VALIDAR CONFIGURACIÓN
// ==========================================
$clientId = $configGoogle["client_id"] ?? "";
$redirectUri = $configGoogle["redirect_uri"] ?? "";

if (
    !is_string($clientId) ||
    !is_string($redirectUri) ||
    $clientId === "" ||
    $redirectUri === ""
) {
    http_response_code(500);

    exit(
        "La configuración de Google no está disponible."
    );
}


// ==========================================
// GENERAR STATE DE SEGURIDAD
// ==========================================
$state = bin2hex(random_bytes(32));

$_SESSION["google_oauth_state"] = $state;


// ==========================================
// PARÁMETROS DE AUTORIZACIÓN
// ==========================================
$parametros = [

    "client_id" => $clientId,

    "redirect_uri" => $redirectUri,

    "response_type" => "code",

    "scope" => "openid email profile",

    "state" => $state,

    "prompt" => "select_account",

    "include_granted_scopes" => "true"
];


// ==========================================
// CONSTRUIR URL DE GOOGLE
// ==========================================
$urlGoogle =
    "https://accounts.google.com/o/oauth2/v2/auth?" .
    http_build_query(
        $parametros,
        "",
        "&",
        PHP_QUERY_RFC3986
    );


// ==========================================
// REDIRIGIR A GOOGLE
// ==========================================
header(
    "Location: " . $urlGoogle
);

exit;