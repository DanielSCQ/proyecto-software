<?php

function agranda_verificar_clave(
    string $clave,
    string $claveGuardada,
    string $rol
): bool {
    if (
        $claveGuardada === "" ||
        !in_array($rol, ["cliente", "administrador"], true)
    ) {
        return false;
    }

    if (password_get_info($claveGuardada)["algoName"] !== "unknown") {
        return password_verify($clave, $claveGuardada);
    }

    // Compatibilidad temporal con el acceso administrativo existente.
    // No modifica la contraseña almacenada; los clientes requieren un hash.
    return $rol === "administrador"
        && hash_equals($claveGuardada, trim($clave));
}
