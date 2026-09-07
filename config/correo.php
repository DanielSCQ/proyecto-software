<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==========================================
// AUTOLOAD DE COMPOSER
// ==========================================
require_once __DIR__ . "/../vendor/autoload.php";


// ==========================================
// CONFIGURACIÓN LOCAL DEL CORREO
// ==========================================
$configCorreoPath = __DIR__ . "/correo.local.php";

if (!file_exists($configCorreoPath)) {
    throw new Exception(
        "No existe el archivo de configuración local del correo."
    );
}

$configCorreo = require $configCorreoPath;


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