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
require_once __DIR__ . "/../../config/correo.php";

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
// DETECTAR SOLICITUD DESDE EL MODAL
// ==========================================
$esAjax =
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["ajax"]) &&
    $_POST["ajax"] === "1";


// ==========================================
// RESPUESTA JSON
// ==========================================
function responderJson(
    bool $success,
    string $message = "",
    array $errors = [],
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
            "errors" => $errors
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ==========================================
// SI YA ESTÁ LOGUEADO COMO CLIENTE
// ==========================================
if (
    isset($_SESSION["id_usuario"]) &&
    ($_SESSION["rol"] ?? "") === "cliente"
) {

    if ($esAjax) {

        responderJson(
            true,
            "Ya tienes una sesión iniciada."
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
    !isset($_SESSION["csrf_registro"]) ||
    !is_string($_SESSION["csrf_registro"])
) {

    $_SESSION["csrf_registro"] =
        bin2hex(random_bytes(32));
}


// ==========================================
// VARIABLES
// ==========================================
$nombre = "";
$apellido = "";
$correo = "";
$telefono = "";

$errores = [];
$registroExitoso = false;

$statusError = 422;


// ==========================================
// FUNCIONES
// ==========================================
function longitudTexto(string $texto): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($texto, "UTF-8")
        : strlen($texto);
}


// ==========================================
// PROCESAR REGISTRO
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ------------------------------------------
    // VALIDAR CSRF
    // ------------------------------------------
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals(
            $_SESSION["csrf_registro"],
            $csrf
        )
    ) {

        $errores[] =
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";

        $statusError = 403;
    }


    // ------------------------------------------
    // RECIBIR DATOS
    // ------------------------------------------
    $nombre =
        trim($_POST["nombre"] ?? "");

    $apellido =
        trim($_POST["apellido"] ?? "");

    $correo =
        strtolower(
            trim($_POST["correo"] ?? "")
        );

    $telefono =
        trim($_POST["telefono"] ?? "");

    $clave =
        $_POST["clave"] ?? "";

    $confirmarClave =
        $_POST["confirmar_clave"] ?? "";


    // ==========================================
    // VALIDAR NOMBRE
    // ==========================================
    if ($nombre === "") {

        $errores[] =
            "El nombre es obligatorio.";

    } elseif (
        longitudTexto($nombre) > 100
    ) {

        $errores[] =
            "El nombre no puede superar los 100 caracteres.";

    } elseif (
        !preg_match(
            "/^[\p{L}\p{M}\s'-]+$/u",
            $nombre
        )
    ) {

        $errores[] =
            "El nombre contiene caracteres no permitidos.";
    }


    // ==========================================
    // VALIDAR APELLIDO
    // ==========================================
    if ($apellido === "") {

        $errores[] =
            "El apellido es obligatorio.";

    } elseif (
        longitudTexto($apellido) > 100
    ) {

        $errores[] =
            "El apellido no puede superar los 100 caracteres.";

    } elseif (
        !preg_match(
            "/^[\p{L}\p{M}\s'-]+$/u",
            $apellido
        )
    ) {

        $errores[] =
            "El apellido contiene caracteres no permitidos.";
    }


    // ==========================================
    // VALIDAR CORREO
    // ==========================================
    if ($correo === "") {

        $errores[] =
            "El correo electrónico es obligatorio.";

    } elseif (
        longitudTexto($correo) > 150
    ) {

        $errores[] =
            "El correo no puede superar los 150 caracteres.";

    } elseif (
        !filter_var(
            $correo,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errores[] =
            "Ingresa un correo electrónico válido.";
    }


    // ==========================================
    // VALIDAR TELÉFONO
    // ==========================================
    if ($telefono !== "") {

        if (
            longitudTexto($telefono) > 20
        ) {

            $errores[] =
                "El teléfono no puede superar los 20 caracteres.";

        } elseif (
            !preg_match(
                "/^[0-9+\s()-]+$/",
                $telefono
            )
        ) {

            $errores[] =
                "El teléfono contiene caracteres no permitidos.";
        }
    }


    // ==========================================
    // VALIDAR CONTRASEÑA
    // ==========================================
    if ($clave === "") {

        $errores[] =
            "La contraseña es obligatoria.";

    } elseif (
        strlen($clave) < 8
    ) {

        $errores[] =
            "La contraseña debe tener mínimo 8 caracteres.";

    } elseif (
        strlen($clave) > 72
    ) {

        $errores[] =
            "La contraseña no puede superar los 72 caracteres.";

    } elseif (
        !preg_match(
            "/[A-Z]/",
            $clave
        )
    ) {

        $errores[] =
            "La contraseña debe contener al menos una letra mayúscula.";

    } elseif (
        !preg_match(
            "/[a-z]/",
            $clave
        )
    ) {

        $errores[] =
            "La contraseña debe contener al menos una letra minúscula.";

    } elseif (
        !preg_match(
            "/[0-9]/",
            $clave
        )
    ) {

        $errores[] =
            "La contraseña debe contener al menos un número.";
    }


    // ==========================================
    // CONFIRMAR CONTRASEÑA
    // ==========================================
    if ($confirmarClave === "") {

        $errores[] =
            "Confirma tu contraseña.";

    } elseif (
        !hash_equals(
            $clave,
            $confirmarClave
        )
    ) {

        $errores[] =
            "Las contraseñas no coinciden.";
    }


    // ==========================================
    // COMPROBAR CORREO DUPLICADO
    // ==========================================
    if (empty($errores)) {

        $sqlExiste = "
            SELECT
                id_usuario
            FROM usuarios
            WHERE correo = ?
            LIMIT 1
        ";

        $stmtExiste =
            $conexion->prepare($sqlExiste);

        if (!$stmtExiste) {

            $errores[] =
                "No fue posible procesar el registro en este momento.";

            $statusError = 500;

        } else {

            $stmtExiste->bind_param(
                "s",
                $correo
            );

            $stmtExiste->execute();

            $resultadoExiste =
                $stmtExiste->get_result();

            if (
                $resultadoExiste->num_rows > 0
            ) {

                $errores[] =
                    "Ya existe una cuenta asociada a ese correo electrónico.";
            }

            $stmtExiste->close();
        }
    }


    // ==========================================
    // CREAR USUARIO
    // ==========================================
    if (empty($errores)) {

        // --------------------------------------
        // HASH DE CONTRASEÑA
        // --------------------------------------
        $claveHash =
            password_hash(
                $clave,
                PASSWORD_DEFAULT
            );

        if ($claveHash === false) {

            $errores[] =
                "No fue posible procesar la contraseña.";

            $statusError = 500;

        } else {

            // ==================================
            // TOKEN DE VERIFICACIÓN
            // ==================================

            /*
             * Este es el token REAL.
             *
             * Más adelante se enviará al correo
             * del usuario mediante un enlace.
             */
            $tokenVerificacion =
                bin2hex(
                    random_bytes(32)
                );


            /*
             * En la base de datos NO guardamos
             * el token real.
             *
             * Solamente guardamos su hash.
             */
            $tokenHash =
                hash(
                    "sha256",
                    $tokenVerificacion
                );


            // ==================================
            // DATOS FIJOS DEL CLIENTE
            // ==================================
            $rol = "cliente";
            $estado = 1;

            /*
             * Correo pendiente de verificar.
             */
            $emailVerificado = 0;


            // ==================================
            // TELÉFONO OPCIONAL
            // ==================================
            $telefonoGuardar =
                $telefono !== ""
                    ? $telefono
                    : null;


            // ==================================
            // INSERTAR USUARIO
            // ==================================
            /*
             * La expiración la calcula MySQL
             * usando su propio reloj.
             *
             * Así evitamos diferencias entre
             * la zona horaria de PHP y MySQL.
             */
            $sqlInsertar = "
                INSERT INTO usuarios
                (
                    nombre,
                    apellido,
                    correo,
                    google_id,
                    email_verificado,
                    token_verificacion_hash,
                    token_expira,
                    clave,
                    telefono,
                    rol,
                    estado
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    NULL,
                    ?,
                    ?,
                    DATE_ADD(NOW(), INTERVAL 1 HOUR),
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmtInsertar =
                $conexion->prepare(
                    $sqlInsertar
                );


            if (!$stmtInsertar) {

                $errores[] =
                    "No fue posible procesar el registro.";

                $statusError = 500;

            } else {

                $stmtInsertar->bind_param(
                    "sssissssi",
                    $nombre,
                    $apellido,
                    $correo,
                    $emailVerificado,
                    $tokenHash,
                    $claveHash,
                    $telefonoGuardar,
                    $rol,
                    $estado
                );


                if ($stmtInsertar->execute()) {

                // ==========================================
                // CONSTRUIR URL DE VERIFICACIÓN
                // ==========================================
                $host =
                    $_SERVER["HTTP_HOST"] ?? "localhost";

                $esHttps =
                    !empty($_SERVER["HTTPS"]) &&
                    $_SERVER["HTTPS"] !== "off";

                $protocolo =
                    $esHttps ? "https" : "http";

                $urlVerificacion =
                    $protocolo
                    . "://"
                    . $host
                    . $base_url
                    . "cuenta/verificar_correo.php?token="
                    . urlencode($tokenVerificacion);


                // ==========================================
                // ENVIAR CORREO DE VERIFICACIÓN
                // ==========================================
                $correoEnviado =
                    enviarCorreoVerificacion(
                        $correo,
                        $nombre,
                        $urlVerificacion
                    );


                // ==========================================
                // CORREO NO ENVIADO
                // ==========================================
                if (!$correoEnviado) {

                    /*
                    * El usuario ya fue creado en la BD,
                    * pero permanece sin verificar.
                    *
                    * Luego crearemos el sistema para
                    * reenviar la verificación.
                    */
                    $errores[] =
                        "La cuenta fue creada, pero no fue posible enviar el correo de verificación.";

                    $statusError = 500;

                } else {

                    // ======================================
                    // REGISTRO COMPLETADO
                    // ======================================
                    $registroExitoso = true;

                    // Renovar token CSRF
                    $_SESSION["csrf_registro"] =
                        bin2hex(random_bytes(32));


                    // ======================================
                    // RESPUESTA PARA EL MODAL
                    // ======================================
                    if ($esAjax) {

                        responderJson(
                            true,
                            "La cuenta fue creada. Revisa tu correo electrónico para verificarla."
                        );
                    }


                    // ======================================
                    // LIMPIAR FORMULARIO TRADICIONAL
                    // ======================================
                    $nombre = "";
                    $apellido = "";
                    $correo = "";
                    $telefono = "";
                }

            } else {

                $errores[] =
                    "No fue posible crear la cuenta. Inténtalo nuevamente.";

                $statusError = 500;
}

                $stmtInsertar->close();
            }
        }
    }


    // ==========================================
    // ERRORES PARA EL MODAL
    // ==========================================
    if (
        $esAjax &&
        !empty($errores)
    ) {

        responderJson(
            false,
            "Revisa los datos del formulario.",
            $errores,
            $statusError
        );
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

    <title>Crear cuenta | AGRANDA</title>

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
                Crear cuenta
            </h1>

            <p>
                Regístrate para guardar favoritos,
                realizar compras y consultar tus pedidos.
            </p>

        </div>


        <!-- =====================================
             GOOGLE
        ====================================== -->

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


        <!-- =====================================
             REGISTRO EXITOSO
        ====================================== -->

        <?php if ($registroExitoso): ?>

            <div
                class="mensaje mensaje-exito"
                role="status"
            >

                <strong>
                    Cuenta creada.
                </strong>

                <p>
                    Debes verificar tu correo electrónico
                    antes de poder iniciar sesión.
                </p>

            </div>

        <?php endif; ?>


        <!-- =====================================
             ERRORES
        ====================================== -->

        <?php if (!empty($errores)): ?>

            <div
                class="mensaje mensaje-error"
                role="alert"
            >

                <ul>

                    <?php foreach ($errores as $error): ?>

                        <li>

                            <?= htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- =====================================
             FORMULARIO
        ====================================== -->

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
                    $_SESSION["csrf_registro"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


            <div class="campo-grid">

                <!-- NOMBRE -->
                <div class="campo">

                    <label for="nombre">
                        Nombre
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        required
                        autocomplete="given-name"
                        value="<?= htmlspecialchars(
                            $nombre,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        data-contador
                    >

                    <div class="contador">
                        <span class="contador-actual">0</span>/100
                    </div>

                </div>


                <!-- APELLIDO -->
                <div class="campo">

                    <label for="apellido">
                        Apellido
                    </label>

                    <input
                        type="text"
                        id="apellido"
                        name="apellido"
                        maxlength="100"
                        required
                        autocomplete="family-name"
                        value="<?= htmlspecialchars(
                            $apellido,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        data-contador
                    >

                    <div class="contador">
                        <span class="contador-actual">0</span>/100
                    </div>

                </div>

            </div>


            <!-- CORREO -->
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
                    data-contador
                >

                <div class="contador">
                    <span class="contador-actual">0</span>/150
                </div>

            </div>


            <!-- TELÉFONO -->
            <div class="campo">

                <label for="telefono">

                    Teléfono

                    <span class="opcional">
                        (opcional)
                    </span>

                </label>

                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    maxlength="20"
                    autocomplete="tel"
                    inputmode="tel"
                    value="<?= htmlspecialchars(
                        $telefono,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    data-contador
                >

                <div class="contador">
                    <span class="contador-actual">0</span>/20
                </div>

            </div>


            <!-- CONTRASEÑA -->
            <div class="campo">

                <label for="clave">
                    Contraseña
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        minlength="8"
                        maxlength="72"
                        required
                        autocomplete="new-password"
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

                <small class="ayuda-campo">

                    Mínimo 8 caracteres,
                    una mayúscula,
                    una minúscula
                    y un número.

                </small>

            </div>


            <!-- CONFIRMAR CONTRASEÑA -->
            <div class="campo">

                <label for="confirmar_clave">
                    Confirmar contraseña
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirmar_clave"
                        name="confirmar_clave"
                        minlength="8"
                        maxlength="72"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        class="mostrar-clave"
                        data-password-target="confirmar_clave"
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
                Crear cuenta
            </button>

        </form>


        <p class="auth-footer-text">

            ¿Ya tienes una cuenta?

            <a
                href="<?= htmlspecialchars(
                    $base_url,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>cuenta/login.php"
            >
                Inicia sesión
            </a>

        </p>

    </section>

</main>

</body>

</html>