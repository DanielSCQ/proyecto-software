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
// CONFIGURACIÓN DE GOOGLE
// ==========================================
$configGoogle =
    require __DIR__ . "/../../config/google_oauth.php";

$clientId =
    $configGoogle["client_id"] ?? "";

$clientSecret =
    $configGoogle["client_secret"] ?? "";

$redirectUri =
    $configGoogle["redirect_uri"] ?? "";


// ==========================================
// URL BASE
// ==========================================
$scriptDir =
    str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"])
    );

$posTienda =
    strpos(
        $scriptDir,
        "/tienda"
    );

if ($posTienda !== false) {

    $base_url =
        substr(
            $scriptDir,
            0,
            $posTienda + strlen("/tienda")
        ) . "/";

} else {

    $base_url = "/tienda/";
}


// ==========================================
// FUNCIÓN DE ERROR
// ==========================================
function errorGoogle(
    string $mensaje,
    string $base_url
): void {

    $_SESSION["google_error"] =
        $mensaje;

    header(
        "Location: " .
        $base_url .
        "cuenta/login.php"
    );

    exit;
}


// ==========================================
// VALIDAR CONFIGURACIÓN
// ==========================================
if (
    !is_string($clientId) ||
    !is_string($clientSecret) ||
    !is_string($redirectUri) ||
    $clientId === "" ||
    $clientSecret === "" ||
    $redirectUri === ""
) {

    errorGoogle(
        "La configuración de Google no está disponible.",
        $base_url
    );
}


// ==========================================
// ERROR DEVUELTO POR GOOGLE
// ==========================================
if (isset($_GET["error"])) {

    errorGoogle(
        "El inicio de sesión con Google fue cancelado o no pudo completarse.",
        $base_url
    );
}


// ==========================================
// VALIDAR STATE
// ==========================================
$stateRecibido =
    $_GET["state"] ?? "";

$stateGuardado =
    $_SESSION["google_oauth_state"] ?? "";

unset(
    $_SESSION["google_oauth_state"]
);

if (
    !is_string($stateRecibido) ||
    !is_string($stateGuardado) ||
    $stateRecibido === "" ||
    $stateGuardado === "" ||
    !hash_equals(
        $stateGuardado,
        $stateRecibido
    )
) {

    errorGoogle(
        "La solicitud de Google no es válida. Inténtalo nuevamente.",
        $base_url
    );
}


// ==========================================
// RECIBIR CÓDIGO DE GOOGLE
// ==========================================
$codigo =
    $_GET["code"] ?? "";

if (
    !is_string($codigo) ||
    $codigo === ""
) {

    errorGoogle(
        "Google no devolvió un código de autorización válido.",
        $base_url
    );
}


// ==========================================
// VERIFICAR CURL
// ==========================================
if (!function_exists("curl_init")) {

    errorGoogle(
        "El servidor no permite completar el inicio de sesión con Google.",
        $base_url
    );
}


// ==========================================
// INTERCAMBIAR CÓDIGO POR TOKEN
// ==========================================
$curl =
    curl_init(
        "https://oauth2.googleapis.com/token"
    );

curl_setopt_array(
    $curl,
    [
        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS =>
            http_build_query(
                [
                    "code" =>
                        $codigo,

                    "client_id" =>
                        $clientId,

                    "client_secret" =>
                        $clientSecret,

                    "redirect_uri" =>
                        $redirectUri,

                    "grant_type" =>
                        "authorization_code"
                ],
                "",
                "&",
                PHP_QUERY_RFC3986
            ),

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_TIMEOUT =>
            15,

        CURLOPT_HTTPHEADER =>
            [
                "Content-Type: application/x-www-form-urlencoded"
            ]
    ]
);

$respuestaToken =
    curl_exec($curl);

$codigoHttp =
    curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

$errorCurl =
    curl_error($curl);

curl_close($curl);


if (
    $respuestaToken === false ||
    $errorCurl !== "" ||
    $codigoHttp < 200 ||
    $codigoHttp >= 300
) {

    errorGoogle(
        "No fue posible comunicarse correctamente con Google.",
        $base_url
    );
}


$datosToken =
    json_decode(
        $respuestaToken,
        true
    );

$accessToken =
    $datosToken["access_token"] ?? "";

if (
    !is_string($accessToken) ||
    $accessToken === ""
) {

    errorGoogle(
        "Google no devolvió una autorización válida.",
        $base_url
    );
}


// ==========================================
// OBTENER DATOS DEL USUARIO
// ==========================================
$curl =
    curl_init(
        "https://openidconnect.googleapis.com/v1/userinfo"
    );

curl_setopt_array(
    $curl,
    [
        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_TIMEOUT =>
            15,

        CURLOPT_HTTPHEADER =>
            [
                "Authorization: Bearer " .
                $accessToken
            ]
    ]
);

$respuestaUsuario =
    curl_exec($curl);

$codigoHttp =
    curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

$errorCurl =
    curl_error($curl);

curl_close($curl);


if (
    $respuestaUsuario === false ||
    $errorCurl !== "" ||
    $codigoHttp < 200 ||
    $codigoHttp >= 300
) {

    errorGoogle(
        "No fue posible obtener los datos de la cuenta de Google.",
        $base_url
    );
}


$datosGoogle =
    json_decode(
        $respuestaUsuario,
        true
    );


// ==========================================
// DATOS DE GOOGLE
// ==========================================
$googleId =
    trim(
        (string) (
            $datosGoogle["sub"] ?? ""
        )
    );

$correo =
    strtolower(
        trim(
            (string) (
                $datosGoogle["email"] ?? ""
            )
        )
    );

$emailVerificadoGoogle =
    ($datosGoogle["email_verified"] ?? false)
        === true;

$nombre =
    trim(
        (string) (
            $datosGoogle["given_name"] ?? ""
        )
    );

$apellido =
    trim(
        (string) (
            $datosGoogle["family_name"] ?? ""
        )
    );


// ==========================================
// VALIDAR IDENTIDAD
// ==========================================
if (
    $googleId === "" ||
    strlen($googleId) > 255 ||
    $correo === "" ||
    strlen($correo) > 150 ||
    !filter_var(
        $correo,
        FILTER_VALIDATE_EMAIL
    ) ||
    !$emailVerificadoGoogle
) {

    errorGoogle(
        "Google no proporcionó una cuenta de correo válida y verificada.",
        $base_url
    );
}


// ==========================================
// AJUSTAR NOMBRE Y APELLIDO
// ==========================================
if ($nombre === "") {
    $nombre = "Usuario";
}

if (function_exists("mb_substr")) {

    $nombre =
        mb_substr(
            $nombre,
            0,
            100,
            "UTF-8"
        );

    $apellido =
        mb_substr(
            $apellido,
            0,
            100,
            "UTF-8"
        );

} else {

    $nombre =
        substr(
            $nombre,
            0,
            100
        );

    $apellido =
        substr(
            $apellido,
            0,
            100
        );
}


// ==========================================
// BUSCAR PRIMERO POR GOOGLE ID
// ==========================================
$sqlGoogle = "
    SELECT
        id_usuario,
        nombre,
        apellido,
        correo,
        google_id,
        rol,
        estado
    FROM usuarios
    WHERE google_id = ?
    LIMIT 1
";

$stmtGoogle =
    $conexion->prepare(
        $sqlGoogle
    );

if (!$stmtGoogle) {

    errorGoogle(
        "No fue posible procesar el inicio de sesión.",
        $base_url
    );
}

$stmtGoogle->bind_param(
    "s",
    $googleId
);

$stmtGoogle->execute();

$resultadoGoogle =
    $stmtGoogle->get_result();

$usuario =
    $resultadoGoogle->fetch_assoc();

$stmtGoogle->close();


// ==========================================
// SI NO EXISTE GOOGLE ID, BUSCAR POR CORREO
// ==========================================
if (!$usuario) {

    $sqlCorreo = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            google_id,
            rol,
            estado
        FROM usuarios
        WHERE correo = ?
        LIMIT 1
    ";

    $stmtCorreo =
        $conexion->prepare(
            $sqlCorreo
        );

    if (!$stmtCorreo) {

        errorGoogle(
            "No fue posible procesar el inicio de sesión.",
            $base_url
        );
    }

    $stmtCorreo->bind_param(
        "s",
        $correo
    );

    $stmtCorreo->execute();

    $resultadoCorreo =
        $stmtCorreo->get_result();

    $usuario =
        $resultadoCorreo->fetch_assoc();

    $stmtCorreo->close();


    // ======================================
    // YA EXISTE CUENTA CON ESE CORREO
    // ======================================
    if ($usuario) {

        // ----------------------------------
        // SOLO CLIENTES
        // ----------------------------------
        if (
            $usuario["rol"] !== "cliente"
        ) {

            errorGoogle(
                "Esta cuenta no puede iniciar sesión como cliente.",
                $base_url
            );
        }


        // ----------------------------------
        // CUENTA DESACTIVADA
        // ----------------------------------
        if (
            (int) $usuario["estado"] !== 1
        ) {

            errorGoogle(
                "Esta cuenta se encuentra desactivada.",
                $base_url
            );
        }


        // ----------------------------------
        // EVITAR CONFLICTO DE GOOGLE ID
        // ----------------------------------
        if (
            !empty($usuario["google_id"]) &&
            !hash_equals(
                (string) $usuario["google_id"],
                $googleId
            )
        ) {

            errorGoogle(
                "La cuenta ya está vinculada a otro acceso de Google.",
                $base_url
            );
        }


        // ==================================
        // VINCULAR GOOGLE
        // ==================================
        $sqlVincular = "
            UPDATE usuarios
            SET
                google_id = ?,
                email_verificado = 1,
                token_verificacion_hash = NULL,
                token_expira = NULL
            WHERE id_usuario = ?
              AND rol = 'cliente'
              AND estado = 1
            LIMIT 1
        ";

        $stmtVincular =
            $conexion->prepare(
                $sqlVincular
            );

        if (!$stmtVincular) {

            errorGoogle(
                "No fue posible vincular la cuenta de Google.",
                $base_url
            );
        }

        $idUsuario =
            (int) $usuario["id_usuario"];

        $stmtVincular->bind_param(
            "si",
            $googleId,
            $idUsuario
        );

        if (!$stmtVincular->execute()) {

            $stmtVincular->close();

            errorGoogle(
                "No fue posible vincular la cuenta de Google.",
                $base_url
            );
        }

        $stmtVincular->close();

        $usuario["google_id"] =
            $googleId;

    } else {

        // ==================================
        // CREAR CLIENTE NUEVO CON GOOGLE
        // ==================================
        $rol =
            "cliente";

        $estado =
            1;

        $emailVerificado =
            1;


        $sqlCrear = "
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
                ?,
                ?,
                NULL,
                NULL,
                NULL,
                NULL,
                ?,
                ?
            )
        ";

        $stmtCrear =
            $conexion->prepare(
                $sqlCrear
            );

        if (!$stmtCrear) {

            errorGoogle(
                "No fue posible crear la cuenta con Google.",
                $base_url
            );
        }

        $stmtCrear->bind_param(
            "ssssisi",
            $nombre,
            $apellido,
            $correo,
            $googleId,
            $emailVerificado,
            $rol,
            $estado
        );


        if (!$stmtCrear->execute()) {

            $stmtCrear->close();

            errorGoogle(
                "No fue posible crear la cuenta con Google.",
                $base_url
            );
        }


        $idUsuario =
            (int) $conexion->insert_id;

        $stmtCrear->close();


        $usuario = [
            "id_usuario" =>
                $idUsuario,

            "nombre" =>
                $nombre,

            "apellido" =>
                $apellido,

            "correo" =>
                $correo,

            "google_id" =>
                $googleId,

            "rol" =>
                "cliente",

            "estado" =>
                1
        ];
    }
}


// ==========================================
// VALIDACIÓN FINAL DEL USUARIO
// ==========================================
if (
    !$usuario ||
    $usuario["rol"] !== "cliente" ||
    (int) $usuario["estado"] !== 1
) {

    errorGoogle(
        "No fue posible iniciar sesión con esta cuenta.",
        $base_url
    );
}


// ==========================================
// INICIAR SESIÓN DE AGRANDA
// ==========================================
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


// ==========================================
// LIMPIAR DATOS TEMPORALES
// ==========================================
unset(
    $_SESSION["google_error"]
);


// ==========================================
// REDIRIGIR A MI CUENTA
// ==========================================
header(
    "Location: " .
    $base_url .
    "cuenta/"
);

exit;