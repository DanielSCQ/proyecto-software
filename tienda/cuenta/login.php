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
// DETECTAR SOLICITUD DEL MODAL
// ==========================================
$esAjax =
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["ajax"]) &&
    $_POST["ajax"] === "1";


// ==========================================
// FUNCIÓN PARA RESPONDER JSON
// ==========================================
function responderJson(
    bool $success,
    string $message = "",
    string $redirect = "",
    int $status = 200
): void {

    http_response_code($status);

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        [
            "success" => $success,
            "message" => $message,
            "redirect" => $redirect
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ==========================================
// SI YA ESTÁ LOGUEADO
// ==========================================
if (
    isset($_SESSION["id_usuario"]) &&
    ($_SESSION["rol"] ?? "") === "cliente"
) {

    if ($esAjax) {

        responderJson(
            true,
            "",
            $base_url . "cuenta/"
        );
    }

    header(
        "Location: " .
        $base_url .
        "cuenta/"
    );

    exit;
}


// ==========================================
// TOKEN CSRF
// ==========================================
if (
    !isset($_SESSION["csrf_login"]) ||
    !is_string($_SESSION["csrf_login"])
) {

    $_SESSION["csrf_login"] =
        bin2hex(random_bytes(32));
}


// ==========================================
// VARIABLES
// ==========================================
$correo = "";
$error = "";


// ==========================================
// ERROR DEVUELTO POR GOOGLE
// ==========================================
if (
    isset($_SESSION["google_error"]) &&
    is_string($_SESSION["google_error"]) &&
    $_SESSION["google_error"] !== ""
) {

    $error =
        $_SESSION["google_error"];

    unset(
        $_SESSION["google_error"]
    );
}

// ==========================================
// PROCESAR LOGIN
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --------------------------------------
    // VALIDAR TOKEN CSRF
    // --------------------------------------
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals(
            $_SESSION["csrf_login"],
            $csrf
        )
    ) {

        $error =
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";
    }


    // --------------------------------------
    // OBTENER DATOS
    // --------------------------------------
    $correo =
        strtolower(
            trim($_POST["correo"] ?? "")
        );

    $clave =
        $_POST["clave"] ?? "";


    // --------------------------------------
    // VALIDACIONES DEL SERVIDOR
    // --------------------------------------
    if ($error === "") {

        if (
            $correo === "" ||
            $clave === "" ||
            strlen($correo) > 150 ||
            strlen($clave) > 72 ||
            !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            // Mensaje genérico para no revelar
            // si un correo existe o no.
            $error =
                "Correo o contraseña incorrectos.";
        }
    }


    // --------------------------------------
    // CONSULTAR USUARIO
    // --------------------------------------
    if ($error === "") {

        $sql = "
            SELECT
                id_usuario,
                nombre,
                apellido,
                correo,
                clave,
                rol,
                estado
            FROM usuarios
            WHERE correo = ?
            LIMIT 1
        ";

        $stmt =
            $conexion->prepare($sql);

        if (!$stmt) {

            $error =
                "No fue posible iniciar sesión en este momento.";

        } else {

            $stmt->bind_param(
                "s",
                $correo
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            $usuario =
                $resultado->fetch_assoc();

            $stmt->close();


            // ==================================
            // VALIDAR CREDENCIALES
            // ==================================
            $credencialesCorrectas =
                $usuario !== null &&
                (int) $usuario["estado"] === 1 &&
                $usuario["rol"] === "cliente" &&
                !empty($usuario["clave"]) &&
                password_verify(
                    $clave,
                    $usuario["clave"]
                );


            // ==================================
            // CREDENCIALES INCORRECTAS
            // ==================================
            if (!$credencialesCorrectas) {

                $error =
                    "Correo o contraseña incorrectos.";

            } else {

                // ==================================
                // INICIAR SESIÓN
                // ==================================
                session_regenerate_id(true);

                $_SESSION["id_usuario"] =
                    (int) $usuario["id_usuario"];

                $_SESSION["nombre"] =
                    $usuario["nombre"];

                $_SESSION["apellido"] =
                    $usuario["apellido"];

                $_SESSION["correo"] =
                    $usuario["correo"];

                $_SESSION["rol"] =
                    "cliente";


                // ==================================
                // RENOVAR TOKEN CSRF
                // ==================================
                $_SESSION["csrf_login"] =
                    bin2hex(random_bytes(32));


                // ==================================
                // LOGIN DESDE EL MODAL
                // ==================================
                if ($esAjax) {

                    responderJson(
                        true,
                        "",
                        $base_url . "cuenta/"
                    );
                }


                // ==================================
                // LOGIN TRADICIONAL
                // ==================================
                header(
            "Location: " .
            $base_url .
            "cuenta/"
        );

        exit;

                    } // Cierra if (!$credencialesCorrectas)

                } // Cierra else de if (!$stmt)

            } // Cierra if ($error === "")


            // ==========================================
            // ERROR DESDE EL MODAL
            // ==========================================
            if ($esAjax) {

                responderJson(
                    false,
                    $error !== ""
                        ? $error
                        : "No fue posible iniciar sesión.",
                    "",
                    401
                );
            }

        } // Cierra if ($_SERVER["REQUEST_METHOD"] === "POST")
    


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar sesión | AGRANDA</title>

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

    <script
        src="<?= htmlspecialchars(
            $base_url,
            ENT_QUOTES,
            "UTF-8"
        ) ?>js/cuenta.js"
        defer
    ></script>

</head>

<body>

<main class="auth-page">

    <section class="auth-card">

        <a
            href="<?= htmlspecialchars(
                $base_url,
                ENT_QUOTES,
                "UTF-8"
            ) ?>index.php"
            class="auth-close"
            aria-label="Cancelar e ir al inicio"
            title="Cancelar"
        >
            ×
        </a>


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
                Bienvenido
            </h1>

            <p>
                Inicia sesión para continuar.
            </p>

        </div>


        <a
            href="<?= htmlspecialchars(
                $base_url,
                ENT_QUOTES,
                "UTF-8"
            ) ?>cuenta/google_login.php"
            class="google-button"
        >

            <span class="google-icon">
                G
            </span>

            Continuar con Google

        </a>


        <div class="auth-divider">
            <span>o</span>
        </div>


        <?php if ($error !== ""): ?>

            <div
                class="mensaje mensaje-error"
                role="alert"
            >

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            autocomplete="on"
            novalidate
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["csrf_login"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


            <div class="campo">

                <label for="correo">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    maxlength="150"
                    required
                    autocomplete="email"
                    inputmode="email"
                    value="<?= htmlspecialchars(
                        $correo,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


            <div class="campo">

                <label for="clave">
                    Contraseña
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        maxlength="72"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="mostrar-clave"
                        data-password-target="clave"
                        aria-label="Mostrar contraseña"
                    >
                        Mostrar
                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="auth-submit"
            >
                Iniciar sesión
            </button>

        </form>


        <p class="auth-footer-text">

            ¿No tienes cuenta?

            <a
                href="<?= htmlspecialchars(
                    $base_url,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>cuenta/registro.php"
            >
                Regístrate
            </a>

        </p>

    </section>

</main>

</body>
</html>