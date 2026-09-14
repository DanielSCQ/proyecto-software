<?php

// =========================================
// SESIÓN
// =========================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// =========================================
// CONEXIÓN
// =========================================

require_once("../../config/conexion.php");


// =========================================
// URL BASE
// =========================================

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


// =========================================
// SOLO CLIENTES
// =========================================

if (
    !isset($_SESSION["id_usuario"]) ||
    ($_SESSION["rol"] ?? "") !== "cliente"
) {

    header(
        "Location: " .
        $base_url .
        "productos/"
    );

    exit;
}


$idUsuario =
    (int) $_SESSION["id_usuario"];


if ($idUsuario < 1) {

    header(
        "Location: " .
        $base_url .
        "productos/"
    );

    exit;
}


// =========================================
// SOLO POST
// =========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// CSRF
// =========================================

$csrfRecibido =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_checkout"] ?? "";


if (
    !is_string($csrfRecibido) ||
    !is_string($csrfSesion) ||
    $csrfRecibido === "" ||
    $csrfSesion === "" ||
    !hash_equals(
        $csrfSesion,
        $csrfRecibido
    )
) {

    $_SESSION["checkout_error"] =
        "La solicitud de pago no es válida.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// TOKEN DEL CHECKOUT
// =========================================

$tokenCheckout =
    $_POST["token_checkout"] ?? "";

$tokenSesion =
    $_SESSION["token_checkout"] ?? "";


if (
    !is_string($tokenCheckout) ||
    !preg_match(
        '/^[a-f0-9]{64}$/',
        $tokenCheckout
    ) ||
    !is_string($tokenSesion) ||
    $tokenSesion === "" ||
    !hash_equals(
        $tokenSesion,
        $tokenCheckout
    )
) {

    $_SESSION["checkout_error"] =
        "Esta compra ya no está activa. Recarga el checkout e inténtalo nuevamente.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// VALIDAR CARRITO
// =========================================

if (
    !isset($_SESSION["carrito"]) ||
    !is_array($_SESSION["carrito"]) ||
    empty($_SESSION["carrito"])
) {

    header(
        "Location: " .
        $base_url .
        "carrito/"
    );

    exit;
}


// =========================================
// DIRECCIÓN
// =========================================

$idDireccion =
    filter_input(
        INPUT_POST,
        "direccion",
        FILTER_VALIDATE_INT
    );


if (
    $idDireccion === false ||
    $idDireccion === null ||
    $idDireccion < 1
) {

    $_SESSION["checkout_error"] =
        "Selecciona una dirección de entrega válida.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// COMPROBAR DIRECCIÓN DEL CLIENTE
// =========================================

$sqlDireccion = "
    SELECT id_direccion
    FROM direcciones
    WHERE id_direccion = ?
      AND id_usuario = ?
      AND estado = 1
    LIMIT 1
";


$stmtDireccion =
    $conexion->prepare(
        $sqlDireccion
    );


if (!$stmtDireccion) {

    $_SESSION["checkout_error"] =
        "No fue posible validar la dirección de entrega.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


$stmtDireccion->bind_param(
    "ii",
    $idDireccion,
    $idUsuario
);

$stmtDireccion->execute();

$direccionValida =
    $stmtDireccion
        ->get_result()
        ->fetch_assoc();

$stmtDireccion->close();


if (!$direccionValida) {

    $_SESSION["checkout_error"] =
        "La dirección seleccionada no pertenece a tu cuenta o ya no está disponible.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// MÉTODO DE PAGO
// =========================================

$metodoPago =
    trim(
        $_POST["metodo_pago"] ?? ""
    );


if ($metodoPago !== "Pago simulado") {

    $_SESSION["checkout_error"] =
        "El método de pago seleccionado no es válido.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// DATOS DEL FORMULARIO
// =========================================

$errores = [];

$nombreTitular =
    trim(
        $_POST["titular"] ?? ""
    );


$numeroTarjeta =
    preg_replace(
        '/\s+/',
        "",
        trim(
            $_POST["numero_tarjeta"] ?? ""
        )
    );


$vencimiento =
    trim(
        $_POST["vencimiento"] ?? ""
    );


$cvv =
    trim(
        $_POST["cvv"] ?? ""
    );


$procesarPago =
    isset(
        $_POST["procesar_pago"]
    );


// =========================================
// VALIDAR FORMULARIO DE PAGO
// =========================================

if ($procesarPago) {

    // -------------------------------------
    // NOMBRE DEL TITULAR
    // -------------------------------------

    if ($nombreTitular === "") {

        $errores[] =
            "Ingresa el nombre del titular.";

    } elseif (
        mb_strlen(
            $nombreTitular,
            "UTF-8"
        ) > 100
    ) {

        $errores[] =
            "El nombre del titular no puede superar los 100 caracteres.";

    } elseif (
        !preg_match(
            "/^[\p{L}\s.'-]+$/u",
            $nombreTitular
        )
    ) {

        $errores[] =
            "El nombre del titular contiene caracteres no válidos.";
    }


    // -------------------------------------
    // NÚMERO DE TARJETA
    // -------------------------------------

    if ($numeroTarjeta === "") {

        $errores[] =
            "Ingresa el número de tarjeta.";

    } elseif (
        !preg_match(
            '/^\d{13,19}$/',
            $numeroTarjeta
        )
    ) {

        $errores[] =
            "El número de tarjeta debe contener entre 13 y 19 dígitos.";
    }

    // -------------------------------------
    // VENCIMIENTO MM/AA
    // -------------------------------------

    if ($vencimiento === "") {

        $errores[] =
            "Ingresa la fecha de vencimiento.";

    } elseif (
        !preg_match(
            '/^(0[1-9]|1[0-2])\/\d{2}$/',
            $vencimiento
        )
    ) {

        $errores[] =
            "La fecha de vencimiento debe tener el formato MM/AA.";

    } else {

        [$mes, $anioCorto] =
            explode(
                "/",
                $vencimiento
            );


        $mes =
            (int) $mes;


        $anio =
            2000 +
            (int) $anioCorto;


        $anioActual =
            (int) date("Y");


        $mesActual =
            (int) date("n");


        if (
            $anio < $anioActual ||
            (
                $anio === $anioActual &&
                $mes < $mesActual
            )
        ) {

            $errores[] =
                "La fecha de vencimiento ya expiró.";
        }
    }


    // -------------------------------------
    // CVV
    // -------------------------------------

    if ($cvv === "") {

        $errores[] =
            "Ingresa el código de seguridad.";

    } elseif (
        !preg_match(
            '/^\d{3,4}$/',
            $cvv
        )
    ) {

        $errores[] =
            "El código de seguridad debe contener 3 o 4 dígitos.";
    }
}


// =========================================
// PAGO VALIDADO
// =========================================

if (
    $procesarPago &&
    empty($errores)
) {

    /*
     * El formulario superó las validaciones.
     *
     * IMPORTANTE:
     * NO guardamos en BD ni en sesión:
     *
     * - número de tarjeta
     * - CVV
     * - vencimiento
     * - nombre del titular
     *
     * Solamente guardamos una autorización
     * temporal para demostrar que el flujo
     * pasó correctamente por esta pantalla.
     */

    $_SESSION[
        "pago_simulado_aprobado"
    ] = [

        "token_checkout" =>
            $tokenCheckout,

        "direccion" =>
            $idDireccion,

        "id_usuario" =>
            $idUsuario,

        "fecha" =>
            time()

    ];


    // =====================================
    // CONTINUAR HACIA CONFIRMACIÓN
    // =====================================

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
            Procesando pago - AGRANDA
        </title>

    </head>


    <body>

        <form
            id="formPagoAprobado"
            action="confirmar.php"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?= htmlspecialchars(
                    $csrfSesion,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <input
                type="hidden"
                name="token_checkout"
                value="<?= htmlspecialchars(
                    $tokenCheckout,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <input
                type="hidden"
                name="direccion"
                value="<?= (int) $idDireccion ?>"
            >

            <input
                type="hidden"
                name="metodo_pago"
                value="Pago simulado"
            >

        </form>


        <script>

            document
                .getElementById(
                    "formPagoAprobado"
                )
                .submit();

        </script>

    </body>

    </html>

    <?php

    exit;
}


// =========================================
// HEADER
// =========================================

require_once("../includes/header.php");

?>

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        $base_url .
        "css/checkout.css",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<main class="checkout-page">

    <div class="checkout-container">


        <!-- =====================================
             ENCABEZADO
        ====================================== -->

        <section class="checkout-encabezado">

            <span class="checkout-etiqueta">
                PAGO EN LÍNEA
            </span>

            <h1>
                Realizar pago
            </h1>

            <p>
                Ingresa los datos solicitados para continuar con tu compra.
            </p>

        </section>


        <!-- =====================================
             AVISO
        ====================================== -->

        <div class="simulador-aviso">

            <strong>
                Entorno demostrativo
            </strong>

            <p>
                Esta versión no realiza cobros reales.
                No ingreses información bancaria real.
            </p>

        </div>


        <!-- =====================================
             ERRORES
        ====================================== -->

        <?php if (!empty($errores)): ?>

            <div
                class="checkout-form-error"
                role="alert"
            >

                <strong>
                    Revisa los datos:
                </strong>

                <ul>

                    <?php foreach (
                        $errores
                        as $error
                    ): ?>

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
             CONTENIDO
        ====================================== -->

        <div class="simulador-layout">


            <!-- =================================
                 FORMULARIO
            ================================== -->

            <section class="simulador-formulario">

                <h2>
                    Datos de pago
                </h2>


                <form
                    action="simulador.php"
                    method="POST"
                    autocomplete="off"
                >

                    <!-- CSRF -->

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= htmlspecialchars(
                            $csrfSesion,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >


                    <!-- TOKEN -->

                    <input
                        type="hidden"
                        name="token_checkout"
                        value="<?= htmlspecialchars(
                            $tokenCheckout,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >


                    <!-- DIRECCIÓN -->

                    <input
                        type="hidden"
                        name="direccion"
                        value="<?= (int) $idDireccion ?>"
                    >


                    <!-- MÉTODO -->

                    <input
                        type="hidden"
                        name="metodo_pago"
                        value="Pago simulado"
                    >


                    <!-- =========================
                         TITULAR
                    ========================== -->

                    <div class="checkout-campo">

                        <label for="titular">
                            Nombre del titular
                        </label>

                        <input
                            type="text"
                            id="titular"
                            name="titular"
                            maxlength="100"
                            required
                            autocomplete="off"
                            data-contador-checkout
                            value="<?= htmlspecialchars(
                                $nombreTitular,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            placeholder="Nombre completo"
                        >

                        <small class="checkout-contador">
                            <span>0</span>/100
                        </small>

                    </div>


                    <!-- =========================
                         TARJETA
                    ========================== -->

                    <div class="checkout-campo">

                        <label for="numeroTarjeta">
                            Número de tarjeta
                        </label>

                        <input
                            type="text"
                            id="numeroTarjeta"
                            name="numero_tarjeta"
                            inputmode="numeric"
                            maxlength="23"
                            required
                            autocomplete="off"
                            placeholder="0000 0000 0000 0000"
                        >

                        <small>
                            Ingresa entre 13 y 19 números.
                        </small>

                    </div>


                    <!-- =========================
                         VENCIMIENTO + CVV
                    ========================== -->

                    <div class="simulador-campos-cortos">


                        <div class="checkout-campo">

                            <label for="vencimiento">
                                Vencimiento
                            </label>

                            <input
                                type="text"
                                id="vencimiento"
                                name="vencimiento"
                                inputmode="numeric"
                                maxlength="5"
                                required
                                autocomplete="off"
                                placeholder="MM/AA"
                            >

                        </div>


                        <div class="checkout-campo">

                            <label for="cvv">
                                CVV
                            </label>

                            <input
                                type="password"
                                id="cvv"
                                name="cvv"
                                inputmode="numeric"
                                maxlength="4"
                                required
                                autocomplete="off"
                                placeholder="123"
                            >

                        </div>

                    </div>


                    <!-- =========================
                         BOTÓN
                    ========================== -->

                    <button
                        type="submit"
                        name="procesar_pago"
                        value="1"
                        class="checkout-confirmar"
                    >
                        Realizar pago
                    </button>

                </form>

            </section>


            <!-- =================================
                 INFORMACIÓN
            ================================== -->

            <aside class="simulador-pruebas">

                <span class="checkout-etiqueta">
                    PAGO SEGURO
                </span>

                <h2>
                    Información del pago
                </h2>


                <div class="simulador-prueba">

                    <strong>
                        Número de tarjeta
                    </strong>

                    <span>
                        Debe contener entre 13 y 19 dígitos.
                    </span>

                </div>


                <div class="simulador-prueba">

                    <strong>
                        Fecha de vencimiento
                    </strong>

                    <span>
                        Utiliza el formato MM/AA
                        y una fecha vigente.
                    </span>

                </div>


                <div class="simulador-prueba">

                    <strong>
                        Código de seguridad
                    </strong>

                    <span>
                        Debe contener 3 o 4 números.
                    </span>

                </div>


                <p>
                    Esta función forma parte del entorno
                    demostrativo de AGRANDA y no genera
                    cargos reales.
                </p>


                <a
                    href="<?= htmlspecialchars(
                        $base_url .
                        "checkout/",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    class="checkout-volver simulador-volver"
                >
                    Volver al checkout
                </a>

            </aside>

        </div>

    </div>

</main>


<?php

require_once("../includes/footer.php");

?>