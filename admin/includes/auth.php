<?php

function agranda_admin_autorizado(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $idUsuario = $_SESSION["id_usuario"] ?? null;

    if (!is_int($idUsuario) && !is_string($idUsuario)) {
        return false;
    }

    $idValido = filter_var(
        $idUsuario,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    return $idValido !== false
        && ($_SESSION["rol"] ?? null) === "administrador";
}
