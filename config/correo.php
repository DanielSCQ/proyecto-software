<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==========================================
// AUTOLOAD DE COMPOSER
// ==========================================
require_once __DIR__ . "/../vendor/autoload.php";


// ==========================================
// DETECTAR ENTORNO
// ==========================================

$hostActual =
    strtolower($_SERVER["HTTP_HOST"] ?? "");

$esLocal =
    $hostActual === "localhost" ||
    $hostActual === "127.0.0.1" ||
    str_starts_with($hostActual, "localhost:") ||
    str_starts_with($hostActual, "127.0.0.1:");


// ==========================================
// CONFIGURACIÓN DE CORREO SEGÚN ENTORNO
// ==========================================

if ($esLocal) {

    // XAMPP / LOCAL
    $configCorreoPath =
        __DIR__ . "/correo.local.php";

} else {

    // HOSTING / INFINITYFREE
    $configCorreoPath =
        __DIR__ . "/correo.servidor.php";
}


// ==========================================
// COMPROBAR CONFIGURACIÓN
// ==========================================

if (!file_exists($configCorreoPath)) {

    throw new Exception(
        "No existe la configuración de correo para este entorno."
    );
}


// ==========================================
// CARGAR CONFIGURACIÓN
// ==========================================

$configCorreo =
    require $configCorreoPath;

// ==========================================
// FUNCIÓN PARA ENVIAR CORREO DE VERIFICACIÓN
// ==========================================
function enviarCorreoVerificacion(
    string $correoDestino,
    string $nombreDestino,
    string $urlVerificacion
): bool {

    global $configCorreo;

    $mail = new PHPMailer(true);

    try {

        // --------------------------------------
        // SMTP
        // --------------------------------------
        $mail->isSMTP();

        $mail->Host =
            $configCorreo["host"];

        $mail->SMTPAuth = true;

        $mail->Username =
            $configCorreo["usuario"];

        $mail->Password =
            $configCorreo["clave"];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            (int) $configCorreo["puerto"];


        // --------------------------------------
        // REMITENTE
        // --------------------------------------
        $mail->setFrom(
            $configCorreo["remitente_correo"],
            $configCorreo["remitente_nombre"]
        );


        // --------------------------------------
        // DESTINATARIO
        // --------------------------------------
        $mail->addAddress(
            $correoDestino,
            $nombreDestino
        );


        // --------------------------------------
        // CONTENIDO
        // --------------------------------------
        $mail->CharSet = "UTF-8";

        $mail->isHTML(true);

        $mail->Subject =
            "Verifica tu correo electrónico | AGRANDA";


        $nombreSeguro = htmlspecialchars(
            $nombreDestino,
            ENT_QUOTES,
            "UTF-8"
        );

        $urlSegura = htmlspecialchars(
            $urlVerificacion,
            ENT_QUOTES,
            "UTF-8"
        );


        $mail->Body = "
            <div
                style=\"
                    font-family: Arial, Helvetica, sans-serif;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 30px;
                    background: #ffffff;
                    color: #222222;
                \"
            >

                <h1
                    style=\"
                        color: #1f5f28;
                        margin-bottom: 10px;
                    \"
                >
                    AGRANDA
                </h1>

                <h2>
                    Verifica tu correo electrónico
                </h2>

                <p>
                    Hola {$nombreSeguro},
                </p>

                <p>
                    Gracias por crear tu cuenta en AGRANDA.
                    Para activar tu cuenta debes verificar
                    tu correo electrónico.
                </p>

                <p
                    style=\"
                        margin: 30px 0;
                    \"
                >

                    <a
                        href=\"{$urlSegura}\"
                        style=\"
                            display: inline-block;
                            padding: 14px 22px;
                            background: #1f5f28;
                            color: #ffffff;
                            text-decoration: none;
                            border-radius: 8px;
                            font-weight: bold;
                        \"
                    >
                        Verificar mi correo
                    </a>

                </p>

                <p>
                    Este enlace tendrá una validez limitada.
                </p>

                <p
                    style=\"
                        font-size: 13px;
                        color: #666666;
                    \"
                >
                    Si no creaste esta cuenta,
                    puedes ignorar este mensaje.
                </p>

            </div>
        ";


        // --------------------------------------
        // TEXTO PLANO
        // --------------------------------------
        $mail->AltBody =
            "Hola {$nombreDestino}. "
            . "Verifica tu correo de AGRANDA ingresando aquí: "
            . $urlVerificacion;


        // --------------------------------------
        // ENVIAR
        // --------------------------------------
        $mail->send();

        return true;

    } catch (Exception $e) {

        /*
         * No mostramos detalles técnicos
         * al usuario.
         *
         * Más adelante podemos registrarlos
         * en un log privado.
         */
        return false;
    }
}

// ==========================================
// FUNCIÓN PARA RESPONDER CONTACTOS
// ==========================================
function enviarRespuestaContacto(
    string $correoDestino,
    string $nombreDestino,
    string $asuntoOriginal,
    string $mensajeOriginal,
    string $respuesta
): bool {

    global $configCorreo;

    $mail = new PHPMailer(true);

    try {

        // --------------------------------------
        // SMTP
        // --------------------------------------
        $mail->isSMTP();

        $mail->Host =
            $configCorreo["host"];

        $mail->SMTPAuth = true;

        $mail->Username =
            $configCorreo["usuario"];

        $mail->Password =
            $configCorreo["clave"];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            (int) $configCorreo["puerto"];


        // --------------------------------------
        // REMITENTE
        // --------------------------------------
        $mail->setFrom(
            $configCorreo["remitente_correo"],
            $configCorreo["remitente_nombre"]
        );


        // --------------------------------------
        // DESTINATARIO
        // --------------------------------------
        $mail->addAddress(
            $correoDestino,
            $nombreDestino
        );


        // --------------------------------------
        // CONFIGURACIÓN DEL MENSAJE
        // --------------------------------------
        $mail->CharSet = "UTF-8";

        $mail->isHTML(true);

        $mail->Subject =
            "Respuesta a tu consulta | AGRANDA";


        // --------------------------------------
        // DATOS SEGUROS PARA HTML
        // --------------------------------------
        $nombreSeguro = htmlspecialchars(
            $nombreDestino,
            ENT_QUOTES,
            "UTF-8"
        );

        $asuntoSeguro = htmlspecialchars(
            $asuntoOriginal,
            ENT_QUOTES,
            "UTF-8"
        );

        $mensajeSeguro = nl2br(
            htmlspecialchars(
                $mensajeOriginal,
                ENT_QUOTES,
                "UTF-8"
            )
        );

        $respuestaSegura = nl2br(
            htmlspecialchars(
                $respuesta,
                ENT_QUOTES,
                "UTF-8"
            )
        );


        // --------------------------------------
        // CUERPO HTML
        // --------------------------------------
        $mail->Body = "
            <div
                style=\"
                    font-family: Arial, Helvetica, sans-serif;
                    max-width: 620px;
                    margin: 0 auto;
                    background: #ffffff;
                    color: #333333;
                \"
            >

                <div
                    style=\"
                        padding: 25px 30px;
                        background: #1f5f28;
                        color: #ffffff;
                    \"
                >
                    <h1
                        style=\"
                            margin: 0;
                            font-size: 28px;
                        \"
                    >
                        AGRANDA
                    </h1>

                    <p
                        style=\"
                            margin: 6px 0 0;
                            color: #d9f5dc;
                        \"
                    >
                        Tienda de repuestos agrícolas
                    </p>
                </div>


                <div
                    style=\"
                        padding: 30px;
                    \"
                >

                    <h2
                        style=\"
                            margin-top: 0;
                            color: #1f5f28;
                        \"
                    >
                        Respuesta a tu consulta
                    </h2>


                    <p>
                        Hola <strong>{$nombreSeguro}</strong>,
                    </p>


                    <p>
                        Hemos revisado el mensaje que enviaste
                        a AGRANDA y queremos darte respuesta.
                    </p>


                    <div
                        style=\"
                            margin: 25px 0;
                            padding: 18px;
                            background: #f4f6f4;
                            border-left: 4px solid #f28c28;
                            border-radius: 8px;
                        \"
                    >

                        <strong
                            style=\"
                                color: #1f5f28;
                            \"
                        >
                            Tu consulta
                        </strong>

                        <p
                            style=\"
                                margin: 10px 0 5px;
                            \"
                        >
                            <strong>Asunto:</strong>
                            {$asuntoSeguro}
                        </p>

                        <div
                            style=\"
                                margin-top: 10px;
                                line-height: 1.6;
                                color: #555555;
                            \"
                        >
                            {$mensajeSeguro}
                        </div>

                    </div>


                    <div
                        style=\"
                            margin: 25px 0;
                            padding: 20px;
                            background: #eef7ef;
                            border-radius: 8px;
                        \"
                    >

                        <strong
                            style=\"
                                color: #1f5f28;
                            \"
                        >
                            Respuesta de AGRANDA
                        </strong>

                        <div
                            style=\"
                                margin-top: 12px;
                                line-height: 1.7;
                            \"
                        >
                            {$respuestaSegura}
                        </div>

                    </div>


                    <p>
                        Gracias por comunicarte con nosotros.
                    </p>


                    <p
                        style=\"
                            margin-bottom: 0;
                            font-size: 13px;
                            color: #777777;
                        \"
                    >
                        Este mensaje fue enviado desde el
                        sistema de atención de AGRANDA.
                    </p>

                </div>

            </div>
        ";


        // --------------------------------------
        // VERSIÓN TEXTO PLANO
        // --------------------------------------
        $mail->AltBody =
            "Hola {$nombreDestino}.\n\n"
            . "Hemos revisado tu consulta en AGRANDA.\n\n"
            . "Asunto: {$asuntoOriginal}\n\n"
            . "Tu mensaje:\n{$mensajeOriginal}\n\n"
            . "Respuesta de AGRANDA:\n{$respuesta}\n\n"
            . "Gracias por comunicarte con nosotros.";


        // --------------------------------------
        // ENVIAR
        // --------------------------------------
        $mail->send();

        return true;

    } catch (Exception $e) {

        /*
         * No mostramos información técnica
         * ni credenciales al administrador.
         *
         * Más adelante podemos registrar
         * errores en un log privado.
         */
        return false;
    }
}