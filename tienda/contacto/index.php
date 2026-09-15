<?php

// =========================================
// SESIÓN
// =========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =========================================
// CONEXIÓN
// =========================================

require_once("../../config/conexion.php");


// =========================================
// VARIABLES
// =========================================

$errores = [];
$exito = false;

$nombre = "";
$correo = "";
$telefono = "";
$asunto = "";
$mensaje = "";


// =========================================
// CSRF
// =========================================

if (
    empty($_SESSION["csrf_contacto"]) ||
    empty($_SESSION["csrf_contacto_expira"]) ||
    time() > $_SESSION["csrf_contacto_expira"]
) {
    $_SESSION["csrf_contacto"] = bin2hex(random_bytes(32));
    $_SESSION["csrf_contacto_expira"] = time() + 3600;
}

$csrf_contacto = $_SESSION["csrf_contacto"];


// =========================================
// CLIENTE LOGUEADO
// =========================================

$id_usuario = null;

if (
    isset($_SESSION["id_usuario"]) &&
    isset($_SESSION["rol"]) &&
    $_SESSION["rol"] === "cliente"
) {

    $id_usuario = (int) $_SESSION["id_usuario"];


    // =====================================
    // CARGAR DATOS DEL CLIENTE
    // =====================================

    $sql_usuario = "
        SELECT
            nombre,
            apellido,
            correo,
            telefono
        FROM usuarios
        WHERE id_usuario = ?
          AND estado = 1
          AND rol = 'cliente'
        LIMIT 1
    ";

    $stmt_usuario = $conexion->prepare($sql_usuario);

    if ($stmt_usuario) {

        $stmt_usuario->bind_param(
            "i",
            $id_usuario
        );

        $stmt_usuario->execute();

        $resultado_usuario = $stmt_usuario->get_result();

        if ($resultado_usuario->num_rows === 1) {

            $usuario = $resultado_usuario->fetch_assoc();

            $nombre = trim(
                ($usuario["nombre"] ?? "") . " " .
                ($usuario["apellido"] ?? "")
            );

            $correo = trim(
                $usuario["correo"] ?? ""
            );

            $telefono = trim(
                $usuario["telefono"] ?? ""
            );
        }

        $stmt_usuario->close();
    }
}


// =========================================
// PROCESAR FORMULARIO
// =========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =====================================
    // RECIBIR DATOS
    // =====================================

    $token_recibido = $_POST["csrf_contacto"] ?? "";

    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $asunto = trim($_POST["asunto"] ?? "");
    $mensaje = trim($_POST["mensaje"] ?? "");


    // =====================================
    // VALIDAR CSRF
    // =====================================

    if (
        empty($token_recibido) ||
        empty($_SESSION["csrf_contacto"]) ||
        empty($_SESSION["csrf_contacto_expira"]) ||
        time() > $_SESSION["csrf_contacto_expira"] ||
        !hash_equals(
            $_SESSION["csrf_contacto"],
            $token_recibido
        )
    ) {
        $errores[] = "La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.";
    }


    // =====================================
    // VALIDAR NOMBRE
    // =====================================

    if ($nombre === "") {

        $errores[] = "El nombre es obligatorio.";

    } elseif (mb_strlen($nombre) < 2) {

        $errores[] = "El nombre debe tener al menos 2 caracteres.";

    } elseif (mb_strlen($nombre) > 100) {

        $errores[] = "El nombre no puede superar los 100 caracteres.";
    }


    // =====================================
    // VALIDAR CORREO
    // =====================================

    if ($correo === "") {

        $errores[] = "El correo electrónico es obligatorio.";

    } elseif (mb_strlen($correo) > 150) {

        $errores[] = "El correo no puede superar los 150 caracteres.";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $errores[] = "Ingresa un correo electrónico válido.";
    }


    // =====================================
    // VALIDAR TELÉFONO
    // =====================================

    if ($telefono !== "") {

        if (mb_strlen($telefono) > 20) {

            $errores[] = "El teléfono no puede superar los 20 caracteres.";

        } elseif (!preg_match('/^[0-9+\s()-]{7,20}$/', $telefono)) {

            $errores[] = "Ingresa un número de teléfono válido.";
        }
    }


    // =====================================
    // VALIDAR ASUNTO
    // =====================================

    if ($asunto === "") {

        $errores[] = "El asunto es obligatorio.";

    } elseif (mb_strlen($asunto) < 3) {

        $errores[] = "El asunto debe tener al menos 3 caracteres.";

    } elseif (mb_strlen($asunto) > 150) {

        $errores[] = "El asunto no puede superar los 150 caracteres.";
    }


    // =====================================
    // VALIDAR MENSAJE
    // =====================================

    if ($mensaje === "") {

        $errores[] = "El mensaje es obligatorio.";

    } elseif (mb_strlen($mensaje) < 10) {

        $errores[] = "El mensaje debe tener al menos 10 caracteres.";

    } elseif (mb_strlen($mensaje) > 1500) {

        $errores[] = "El mensaje no puede superar los 1500 caracteres.";
    }


    // =====================================
    // GUARDAR CONTACTO
    // =====================================

    if (empty($errores)) {

        $sql_insertar = "
            INSERT INTO contactos (
                id_usuario,
                nombre,
                correo,
                telefono,
                asunto,
                mensaje,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
        ";

        $stmt_insertar = $conexion->prepare($sql_insertar);

        if (!$stmt_insertar) {

            $errores[] = "No fue posible preparar el envío del mensaje.";

        } else {

            $stmt_insertar->bind_param(
                "isssss",
                $id_usuario,
                $nombre,
                $correo,
                $telefono,
                $asunto,
                $mensaje
            );

            if ($stmt_insertar->execute()) {

                $exito = true;

                // Renovar token CSRF después del envío
                $_SESSION["csrf_contacto"] = bin2hex(random_bytes(32));
                $_SESSION["csrf_contacto_expira"] = time() + 3600;

                $csrf_contacto = $_SESSION["csrf_contacto"];


                // Limpiar solamente asunto y mensaje
                // Si está logueado conservamos sus datos.
                $asunto = "";
                $mensaje = "";

                if ($id_usuario === null) {
                    $nombre = "";
                    $correo = "";
                    $telefono = "";
                }

            } else {

                $errores[] = "No fue posible enviar el mensaje. Inténtalo nuevamente.";
            }

            $stmt_insertar->close();
        }
    }
}


// =========================================
// HEADER
// =========================================

require_once("../includes/header.php");

?>

<link rel="stylesheet" href="<?= $base_url ?>css/contacto.css">

<main class="contacto-page">

    <!-- =====================================
         PRESENTACIÓN
    ====================================== -->

    <section class="contacto-hero">

        <div class="contacto-hero-contenido">

            <span class="contacto-etiqueta">
                CONTACTO
            </span>

            <h1>
                Estamos para ayudarte
            </h1>

            <p>
                ¿Tienes dudas sobre un repuesto, compatibilidad,
                disponibilidad o algún pedido? Envíanos tu consulta
                y nuestro equipo podrá ayudarte.
            </p>

        </div>

    </section>


    <!-- =====================================
         CONTENIDO
    ====================================== -->

    <section class="contacto-contenedor">

        <div class="contacto-grid">


            <!-- =================================
                 FORMULARIO
            ================================== -->

            <div class="contacto-formulario-card">

                <div class="contacto-formulario-header">

                    <span class="contacto-mini-etiqueta">
                        ESCRÍBENOS
                    </span>

                    <h2>
                        Envíanos un mensaje
                    </h2>

                    <p>
                        Completa los datos y cuéntanos en qué podemos ayudarte.
                    </p>

                </div>


                <?php if ($exito): ?>

                    <div
                        class="contacto-alerta contacto-alerta-exito"
                        role="status"
                    >
                        <strong>Mensaje enviado correctamente.</strong>

                        <span>
                            Hemos recibido tu consulta y será revisada
                            por nuestro equipo.
                        </span>
                    </div>

                <?php endif; ?>


                <?php if (!empty($errores)): ?>

                    <div
                        class="contacto-alerta contacto-alerta-error"
                        role="alert"
                    >

                        <strong>
                            Revisa la información:
                        </strong>

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


                <form
                    method="POST"
                    action=""
                    class="contacto-formulario"
                    id="formContacto"
                    novalidate
                >

                    <input
                        type="hidden"
                        name="csrf_contacto"
                        value="<?= htmlspecialchars(
                            $csrf_contacto,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >


                    <!-- NOMBRE Y CORREO -->

                    <div class="contacto-fila">

                        <div class="contacto-campo">

                            <div class="contacto-label-linea">

                                <label for="contactoNombre">
                                    Nombre completo
                                </label>

                                <span
                                    class="contacto-contador"
                                    data-contador="contactoNombre"
                                >
                                    0/100
                                </span>

                            </div>

                            <input
                                type="text"
                                id="contactoNombre"
                                name="nombre"
                                maxlength="100"
                                minlength="2"
                                required
                                autocomplete="name"
                                placeholder="Tu nombre completo"
                                value="<?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                        </div>


                        <div class="contacto-campo">

                            <div class="contacto-label-linea">

                                <label for="contactoCorreo">
                                    Correo electrónico
                                </label>

                                <span
                                    class="contacto-contador"
                                    data-contador="contactoCorreo"
                                >
                                    0/150
                                </span>

                            </div>

                            <input
                                type="email"
                                id="contactoCorreo"
                                name="correo"
                                maxlength="150"
                                required
                                autocomplete="email"
                                placeholder="correo@ejemplo.com"
                                value="<?= htmlspecialchars(
                                    $correo,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                        </div>

                    </div>


                    <!-- TELÉFONO -->

                    <div class="contacto-campo">

                        <div class="contacto-label-linea">

                            <label for="contactoTelefono">
                                Teléfono
                                <span class="contacto-opcional">
                                    (opcional)
                                </span>
                            </label>

                            <span
                                class="contacto-contador"
                                data-contador="contactoTelefono"
                            >
                                0/20
                            </span>

                        </div>

                        <input
                            type="tel"
                            id="contactoTelefono"
                            name="telefono"
                            maxlength="20"
                            autocomplete="tel"
                            placeholder="Ej. 300 000 0000"
                            value="<?= htmlspecialchars(
                                $telefono,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >

                    </div>


                    <!-- ASUNTO -->

                    <div class="contacto-campo">

                        <div class="contacto-label-linea">

                            <label for="contactoAsunto">
                                Asunto
                            </label>

                            <span
                                class="contacto-contador"
                                data-contador="contactoAsunto"
                            >
                                0/150
                            </span>

                        </div>

                        <input
                            type="text"
                            id="contactoAsunto"
                            name="asunto"
                            maxlength="150"
                            minlength="3"
                            required
                            placeholder="¿Sobre qué necesitas ayuda?"
                            value="<?= htmlspecialchars(
                                $asunto,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >

                    </div>


                    <!-- MENSAJE -->

                    <div class="contacto-campo">

                        <div class="contacto-label-linea">

                            <label for="contactoMensaje">
                                Mensaje
                            </label>

                            <span
                                class="contacto-contador"
                                data-contador="contactoMensaje"
                            >
                                0/1500
                            </span>

                        </div>

                        <textarea
                            id="contactoMensaje"
                            name="mensaje"
                            maxlength="1500"
                            minlength="10"
                            required
                            rows="7"
                            placeholder="Describe tu consulta con el mayor detalle posible..."
                        ><?= htmlspecialchars(
                            $mensaje,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?></textarea>

                    </div>


                    <!-- BOTÓN -->

                    <div class="contacto-acciones">

                        <button
                            type="submit"
                            class="contacto-btn-enviar"
                        >
                            Enviar mensaje
                        </button>

                        <p class="contacto-seguridad">
                            Tu información será utilizada únicamente
                            para atender tu consulta.
                        </p>

                    </div>

                </form>

            </div>


            <!-- =================================
                 INFORMACIÓN / WHATSAPP
            ================================== -->

            <aside class="contacto-info">


                <div class="contacto-info-card">

                    <span class="contacto-mini-etiqueta">
                        ATENCIÓN
                    </span>

                    <h2>
                        ¿Cómo podemos ayudarte?
                    </h2>

                    <p>
                        Puedes utilizar este formulario para consultar
                        sobre productos, pedidos, disponibilidad,
                        compatibilidad de repuestos u otras inquietudes.
                    </p>

                    <div class="contacto-info-items">

                        <div class="contacto-info-item">

                            <strong>
                                Productos
                            </strong>

                            <span>
                                Consulta disponibilidad y características.
                            </span>

                        </div>


                        <div class="contacto-info-item">

                            <strong>
                                Pedidos
                            </strong>

                            <span>
                                Pregunta por el estado o información
                                relacionada con tu compra.
                            </span>

                        </div>


                        <div class="contacto-info-item">

                            <strong>
                                Repuestos
                            </strong>

                            <span>
                                Podemos orientarte sobre referencias
                                y compatibilidad.
                            </span>

                        </div>

                    </div>

                </div>


                <!-- WHATSAPP -->

                <div class="contacto-whatsapp-card">

                    <div class="contacto-whatsapp-icono">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.2 1.6 6L.2 24l6.4-1.7a11.8 11.8 0 0 0 5.5 1.4h.1C18.7 23.7 24 18.4 24 11.9c0-3.2-1.2-6.1-3.5-8.4ZM12.2 21.7h-.1a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 0 1-1.5-5.2c0-5.4 4.4-9.8 9.9-9.8 2.6 0 5.1 1 7 2.9a9.8 9.8 0 0 1 2.9 7c0 5.4-4.4 9.8-9.8 9.8Zm5.4-7.4c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-1.7-.8-2.8-1.5-3.9-3.4-.3-.5.3-.5.8-1.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.4 1.4 3.6c.2.2 2.4 3.7 5.9 5.2.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.2-.6-.4Z"
                            />
                        </svg>

                    </div>

                    <div>

                        <span class="contacto-mini-etiqueta">
                            WHATSAPP BUSINESS
                        </span>

                        <h3>
                            Atención rápida
                        </h3>

                        <p>
                            Próximamente podrás comunicarte directamente
                            con AGRANDA mediante WhatsApp.
                        </p>

                    </div>

                    <!--
                        IMPORTANTE:
                        Cuando configuremos el número oficial de
                        WhatsApp Business, este bloque se convierte
                        en el botón real.

                        No colocamos todavía un número inventado.
                    -->

                    <button
                        type="button"
                        class="contacto-btn-whatsapp"
                        disabled
                    >
                        WhatsApp próximamente
                    </button>

                </div>

            </aside>

        </div>

    </section>

</main>


<script>
document.addEventListener("DOMContentLoaded", function () {

    // =========================================
    // CONTADORES DE CARACTERES
    // =========================================

    const campos = document.querySelectorAll(
        "#formContacto [maxlength]"
    );

    campos.forEach(function (campo) {

        const contador = document.querySelector(
            '[data-contador="' + campo.id + '"]'
        );

        if (!contador) {
            return;
        }

        const limite = parseInt(
            campo.getAttribute("maxlength"),
            10
        );


        function actualizarContador() {

            const actual = campo.value.length;

            contador.textContent =
                actual + "/" + limite;

            if (actual >= limite) {
                contador.classList.add("limite");
            } else {
                contador.classList.remove("limite");
            }
        }


        campo.addEventListener(
            "input",
            actualizarContador
        );

        actualizarContador();
    });

});
</script>


<?php

require_once("../includes/footer.php");

?>