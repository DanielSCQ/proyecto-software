<?php

// ==========================================
// SESIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// CONEXIÓN
// ==========================================
require_once __DIR__ . "/../../config/conexion.php";


// ==========================================
// URL BASE
// ==========================================
$scriptDir = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$posTienda = strpos($scriptDir, "/tienda");

if ($posTienda !== false) {

    $base_url = substr(
        $scriptDir,
        0,
        $posTienda + strlen("/tienda")
    ) . "/";

} else {

    $base_url = "/tienda/";
}


// ==========================================
// VARIABLES
// ==========================================
$exito = false;
$mensaje = "";


// ==========================================
// OBTENER TOKEN
// ==========================================
$token = $_GET["token"] ?? "";


// ==========================================
// VALIDAR TOKEN BÁSICO
// ==========================================
if (
    !is_string($token) ||
    strlen($token) !== 64 ||
    !ctype_xdigit($token)
) {

    $mensaje =
        "El enlace de verificación no es válido.";

} else {

    // ======================================
    // CREAR HASH DEL TOKEN RECIBIDO
    // ======================================
    $tokenHash =
        hash(
            "sha256",
            $token
        );


    // ======================================
    // BUSCAR TOKEN VÁLIDO
    // ======================================
    $sqlBuscar = "
        SELECT
            id_usuario,
            email_verificado,
            token_expira
        FROM usuarios
        WHERE token_verificacion_hash = ?
        LIMIT 1
    ";

    $stmtBuscar =
        $conexion->prepare(
            $sqlBuscar
        );


    if (!$stmtBuscar) {

        $mensaje =
            "No fue posible verificar la cuenta en este momento.";

    } else {

        $stmtBuscar->bind_param(
            "s",
            $tokenHash
        );

        $stmtBuscar->execute();

        $resultado =
            $stmtBuscar->get_result();

        $usuario =
            $resultado->fetch_assoc();

        $stmtBuscar->close();


        // ==================================
        // TOKEN NO EXISTE
        // ==================================
        if (!$usuario) {

            $mensaje =
                "El enlace de verificación no es válido o ya fue utilizado.";

        } elseif (
            (int) $usuario["email_verificado"] === 1
        ) {

            $mensaje =
                "Este correo electrónico ya fue verificado.";

        } else {

            // ==================================
            // COMPROBAR EXPIRACIÓN
            // ==================================
            $sqlExpirado = "
                SELECT
                    CASE
                        WHEN ? < NOW()
                        THEN 1
                        ELSE 0
                    END AS expirado
            ";

            $stmtExpira =
                $conexion->prepare(
                    $sqlExpirado
                );

            if (!$stmtExpira) {

                $mensaje =
                    "No fue posible verificar la cuenta en este momento.";

            } else {

                $stmtExpira->bind_param(
                    "s",
                    $usuario["token_expira"]
                );

                $stmtExpira->execute();

                $resultadoExpira =
                    $stmtExpira
                        ->get_result()
                        ->fetch_assoc();

                $stmtExpira->close();


                if (
                    (int) $resultadoExpira["expirado"] === 1
                ) {

                    $mensaje =
                        "El enlace de verificación ha expirado.";

                } else {

                    // ==================================
                    // ACTIVAR CORREO
                    // ==================================
                    $sqlVerificar = "
                        UPDATE usuarios
                        SET
                            email_verificado = 1,
                            token_verificacion_hash = NULL,
                            token_expira = NULL
                        WHERE id_usuario = ?
                          AND email_verificado = 0
                        LIMIT 1
                    ";

                    $stmtVerificar =
                        $conexion->prepare(
                            $sqlVerificar
                        );


                    if (!$stmtVerificar) {

                        $mensaje =
                            "No fue posible verificar la cuenta en este momento.";

                    } else {

                        $stmtVerificar->bind_param(
                            "i",
                            $usuario["id_usuario"]
                        );


                        if (
                            $stmtVerificar->execute() &&
                            $stmtVerificar->affected_rows === 1
                        ) {

                            $exito = true;

                            $mensaje =
                                "Tu correo electrónico fue verificado correctamente.";

                        } else {

                            $mensaje =
                                "No fue posible completar la verificación.";
                        }

                        $stmtVerificar->close();
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Verificar correo | AGRANDA
    </title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            $base_url,
            ENT_QUOTES,
            "UTF-8"
        ) ?>css/global.css"
    >

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            $base_url,
            ENT_QUOTES,
            "UTF-8"
        ) ?>css/cuenta.css"
    >

</head>

<body>

<main class="auth-page">

    <section class="auth-card">

        <div class="auth-header">

            <a
                href="<?= htmlspecialchars(
                    $base_url,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>index.php"
                class="auth-brand"
            >
                AGRANDA
            </a>

            <h1>
                Verificación de correo
            </h1>

        </div>


        <?php if ($exito): ?>

            <div
                class="mensaje mensaje-exito"
                role="status"
            >

                <?= htmlspecialchars(
                    $mensaje,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

            <a
                href="<?= htmlspecialchars(
                    $base_url,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>cuenta/login.php"
                class="auth-submit"
                style="
                    display:block;
                    text-align:center;
                    text-decoration:none;
                    margin-top:20px;
                "
            >
                Iniciar sesión
            </a>

        <?php else: ?>

            <div
                class="mensaje mensaje-error"
                role="alert"
            >

                <?= htmlspecialchars(
                    $mensaje,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

            <a
                href="<?= htmlspecialchars(
                    $base_url,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>index.php"
                class="auth-submit"
                style="
                    display:block;
                    text-align:center;
                    text-decoration:none;
                    margin-top:20px;
                "
            >
                Volver al inicio
            </a>

        <?php endif; ?>

    </section>

</main>

</body>

</html>