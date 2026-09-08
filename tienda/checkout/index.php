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
// SOLO CLIENTES LOGUEADOS
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
// TOKEN CSRF CHECKOUT
// =========================================

if (
    empty($_SESSION["csrf_checkout"]) ||
    !is_string($_SESSION["csrf_checkout"])
) {

    $_SESSION["csrf_checkout"] =
        bin2hex(random_bytes(32));
}

// =========================================
// TOKEN ÚNICO DE ESTA COMPRA
// =========================================

if (
    empty($_SESSION["token_checkout"]) ||
    !is_string($_SESSION["token_checkout"]) ||
    !preg_match(
        '/^[a-f0-9]{64}$/',
        $_SESSION["token_checkout"]
    )
) {

    $_SESSION["token_checkout"] =
        bin2hex(random_bytes(32));
}

// =========================================
// OBTENER DATOS REALES DEL CLIENTE
// =========================================

$cliente = null;

$sqlCliente = "
    SELECT
        id_usuario,
        nombre,
        apellido,
        correo,
        telefono
    FROM usuarios
    WHERE id_usuario = ?
      AND rol = 'cliente'
      AND estado = 1
    LIMIT 1
";

$stmtCliente =
    $conexion->prepare(
        $sqlCliente
    );

if (!$stmtCliente) {

    die(
        "No fue posible cargar los datos del cliente."
    );
}


$stmtCliente->bind_param(
    "i",
    $idUsuario
);

$stmtCliente->execute();

$resultadoCliente =
    $stmtCliente->get_result();

$cliente =
    $resultadoCliente->fetch_assoc();

$stmtCliente->close();


if (!$cliente) {

    session_unset();
    session_destroy();

    header(
        "Location: " .
        $base_url .
        "index.php"
    );

    exit;
}


// =========================================
// DIRECCIONES DEL CLIENTE
// =========================================

$direcciones = [];

$sqlDirecciones = "
    SELECT
        id_direccion,
        nombre,
        telefono,
        receptor,
        direccion,
        barrio,
        municipio,
        departamento,
        referencia,
        principal
    FROM direcciones
    WHERE id_usuario = ?
      AND estado = 1
    ORDER BY principal DESC,
             id_direccion DESC
";

$stmtDirecciones =
    $conexion->prepare(
        $sqlDirecciones
    );


if ($stmtDirecciones) {

    $stmtDirecciones->bind_param(
        "i",
        $idUsuario
    );

    $stmtDirecciones->execute();

    $resultadoDirecciones =
        $stmtDirecciones->get_result();


    while (
        $direccion =
            $resultadoDirecciones->fetch_assoc()
    ) {

        $direcciones[] =
            $direccion;
    }


    $stmtDirecciones->close();
}

$idDireccionSeleccionada =
    isset(
        $_SESSION[
            "checkout_direccion_seleccionada"
        ]
    )
        ? (int) $_SESSION[
            "checkout_direccion_seleccionada"
        ]
        : 0;


unset(
    $_SESSION[
        "checkout_direccion_seleccionada"
    ]
);

// =========================================
// PRODUCTOS DEL CARRITO
// =========================================

$productosCheckout = [];

$totalCompra = 0;

$carritoValido = true;

$mensajeCarrito = "";


// =========================================
// CONSULTA SEGURA DE PRODUCTO
// =========================================

$sqlProducto = "
    SELECT
        p.id_producto,
        p.nombre,
        p.codigo_producto,
        p.precio,
        p.estado,
        c.estado AS categoria_estado,
        COALESCE(i.stock_actual, 0) AS stock_actual,
        img.ruta_imagen

    FROM productos p

    INNER JOIN categorias c
        ON c.id_categoria = p.id_categoria

    LEFT JOIN inventario i
        ON i.id_producto = p.id_producto

    LEFT JOIN imagenes_producto img
        ON img.id_producto = p.id_producto
        AND img.principal = 1
        AND img.estado = 1

    WHERE p.id_producto = ?

    LIMIT 1
";

$stmtProducto =
    $conexion->prepare(
        $sqlProducto
    );


if (!$stmtProducto) {

    die(
        "No fue posible validar los productos del carrito."
    );
}


// =========================================
// RECORRER CARRITO
// =========================================

foreach (
    $_SESSION["carrito"]
    as $idProducto => $item
) {

    $idProducto =
        filter_var(
            $idProducto,
            FILTER_VALIDATE_INT
        );

    $cantidad =
        (int) (
            $item["cantidad"] ?? 0
        );


    // -------------------------------------
    // VALIDAR DATOS DE SESIÓN
    // -------------------------------------

    if (
        $idProducto === false ||
        $idProducto < 1 ||
        $cantidad < 1
    ) {

        $carritoValido = false;

        continue;
    }


    // -------------------------------------
    // CONSULTAR PRODUCTO REAL
    // -------------------------------------

    $stmtProducto->bind_param(
        "i",
        $idProducto
    );

    $stmtProducto->execute();

    $resultadoProducto =
        $stmtProducto->get_result();

    $producto =
        $resultadoProducto->fetch_assoc();


    if (!$producto) {

        $carritoValido = false;

        continue;
    }


    $stock =
        (int) $producto["stock_actual"];

    $precio =
        (float) $producto["precio"];


    // -------------------------------------
    // VALIDAR DISPONIBILIDAD
    // -------------------------------------

    $disponible =
        (int) $producto["estado"] === 1 &&
        (int) $producto["categoria_estado"] === 1 &&
        $stock > 0;


    if (!$disponible) {

        $carritoValido = false;

        $productosCheckout[] = [
            "id_producto" =>
                (int) $producto["id_producto"],

            "nombre" =>
                $producto["nombre"],

            "codigo" =>
                $producto["codigo_producto"],

            "precio" =>
                $precio,

            "cantidad" =>
                $cantidad,

            "stock" =>
                $stock,

            "subtotal" =>
                0,

            "imagen" =>
                $producto["ruta_imagen"],

            "disponible" =>
                false,

            "problema" =>
                "Este producto ya no está disponible."
        ];

        continue;
    }


    // -------------------------------------
    // VALIDAR STOCK
    // -------------------------------------

    if ($cantidad > $stock) {

        $carritoValido = false;

        $productosCheckout[] = [
            "id_producto" =>
                (int) $producto["id_producto"],

            "nombre" =>
                $producto["nombre"],

            "codigo" =>
                $producto["codigo_producto"],

            "precio" =>
                $precio,

            "cantidad" =>
                $cantidad,

            "stock" =>
                $stock,

            "subtotal" =>
                $precio * $stock,

            "imagen" =>
                $producto["ruta_imagen"],

            "disponible" =>
                false,

            "problema" =>
                "La cantidad solicitada supera el stock disponible."
        ];

        continue;
    }


    // -------------------------------------
    // SUBTOTAL REAL
    // -------------------------------------

    $subtotal =
        $precio * $cantidad;


    $totalCompra +=
        $subtotal;


    $productosCheckout[] = [
        "id_producto" =>
            (int) $producto["id_producto"],

        "nombre" =>
            $producto["nombre"],

        "codigo" =>
            $producto["codigo_producto"],

        "precio" =>
            $precio,

        "cantidad" =>
            $cantidad,

        "stock" =>
            $stock,

        "subtotal" =>
            $subtotal,

        "imagen" =>
            $producto["ruta_imagen"],

        "disponible" =>
            true,

        "problema" =>
            ""
    ];
}


$stmtProducto->close();


// =========================================
// SI NO QUEDÓ NINGÚN PRODUCTO
// =========================================

if (empty($productosCheckout)) {

    header(
        "Location: " .
        $base_url .
        "carrito/"
    );

    exit;
}


// =========================================
// MENSAJE DE SEGURIDAD
// =========================================

if (!$carritoValido) {

    $mensajeCarrito =
        "Algunos productos cambiaron de disponibilidad o stock. Revisa tu carrito antes de confirmar la compra.";
}


// =========================================
// MENSAJES DEL FORMULARIO DE DIRECCIÓN
// =========================================

$erroresDireccion =
    $_SESSION["checkout_errores"]
    ?? [];

$errorDireccion =
    $_SESSION["checkout_error"]
    ?? "";

$exitoDireccion =
    $_SESSION["checkout_exito"]
    ?? "";

$datosDireccion =
    $_SESSION["checkout_direccion_form"]
    ?? [];


unset(
    $_SESSION["checkout_errores"],
    $_SESSION["checkout_error"],
    $_SESSION["checkout_exito"],
    $_SESSION["checkout_direccion_form"]
);

// =========================================
// HEADER
// =========================================

require_once("../includes/header.php");

?>

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        $base_url . "css/checkout.css",
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
                FINALIZAR COMPRA
            </span>

            <h1>
                Checkout
            </h1>

            <p>
                Revisa tus datos, selecciona la dirección
                de entrega y confirma tu pedido.
            </p>

        </section>

        <?php if ($errorDireccion !== ""): ?>

            <div
                class="checkout-alerta"
                role="alert"
            >

                <strong>
                    No fue posible confirmar el pedido
                </strong>

                <p>
                    <?= htmlspecialchars(
                        $errorDireccion,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

            </div>

        <?php endif; ?>


        <?php if ($mensajeCarrito !== ""): ?>

            <div
                class="checkout-alerta"
                role="alert"
            >

                <strong>
                    Revisa tu carrito
                </strong>

                <p>
                    <?= htmlspecialchars(
                        $mensajeCarrito,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <a
                    href="<?= htmlspecialchars(
                        $base_url . "carrito/",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >
                    Volver al carrito
                </a>

            </div>

        <?php endif; ?>


        <div class="checkout-layout">


            <!-- =====================================
                 COLUMNA PRINCIPAL
            ====================================== -->

            <div class="checkout-principal">


                <!-- =================================
                     DATOS DEL CLIENTE
                ================================== -->

                <section class="checkout-bloque">

                    <div class="checkout-bloque-titulo">

                        <span>
                            1
                        </span>

                        <div>

                            <h2>
                                Datos del cliente
                            </h2>

                            <p>
                                Información asociada a tu cuenta.
                            </p>

                        </div>

                    </div>


                    <div class="checkout-datos-grid">

                        <div class="checkout-dato">

                            <small>
                                Nombre
                            </small>

                            <strong>
                                <?= htmlspecialchars(
                                    trim(
                                        $cliente["nombre"] .
                                        " " .
                                        $cliente["apellido"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                        </div>


                        <div class="checkout-dato">

                            <small>
                                Correo electrónico
                            </small>

                            <strong>
                                <?= htmlspecialchars(
                                    $cliente["correo"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                        </div>


                        <div class="checkout-dato">

                            <small>
                                Teléfono
                            </small>

                            <strong>
                                <?= !empty($cliente["telefono"])
                                    ? htmlspecialchars(
                                        $cliente["telefono"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                    : "No registrado"
                                ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <!-- =================================
                     DIRECCIÓN
                ================================== -->

                <section class="checkout-bloque">

                    <div class="checkout-bloque-titulo">

                        <span>
                            2
                        </span>

                        <div>

                            <h2>
                                Dirección de entrega
                            </h2>

                            <p>
                                Selecciona dónde deseas recibir tu pedido.
                            </p>

                        </div>

                    </div>


                    <?php if (!empty($direcciones)): ?>


                        <form
                            action="confirmar.php"
                            method="POST"
                            id="formCheckout"
                        >

                            <input
                                type="hidden"
                                name="token_checkout"
                                value="<?= htmlspecialchars(
                                    $_SESSION["token_checkout"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= htmlspecialchars(
                                    $_SESSION["csrf_checkout"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >


                            <div class="checkout-direcciones">


                                <?php foreach (
                                    $direcciones
                                    as $indice => $direccion
                                ): ?>


                                    <label class="checkout-direccion-card">

                                        <input
                                            type="radio"
                                            name="direccion"
                                            value="<?= (int) $direccion["id_direccion"] ?>"
                                            <?= (
                                                (int) $direccion["principal"] === 1 ||
                                                (
                                                    $indice === 0 &&
                                                    !array_filter(
                                                        $direcciones,
                                                        static function ($item) {
                                                            return
                                                                (int) $item["principal"] === 1;
                                                        }
                                                    )
                                                )
                                            ) ? "checked" : "" ?>
                                            required
                                        >


                                        <div class="checkout-direccion-info">

                                            <div class="checkout-direccion-top">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $direccion["nombre"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </strong>


                                                <?php if (
                                                    (int) $direccion["principal"] === 1
                                                ): ?>

                                                    <span>
                                                        Principal
                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <p>
                                                <?= htmlspecialchars(
                                                    $direccion["receptor"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>


                                            <p>
                                                <?= htmlspecialchars(
                                                    $direccion["direccion"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>


                                            <?php if (
                                                !empty($direccion["barrio"])
                                            ): ?>

                                                <p>
                                                    Barrio:
                                                    <?= htmlspecialchars(
                                                        $direccion["barrio"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </p>

                                            <?php endif; ?>


                                            <p>
                                                <?= htmlspecialchars(
                                                    $direccion["municipio"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>,
                                                <?= htmlspecialchars(
                                                    $direccion["departamento"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>


                                            <p>
                                                Tel.
                                                <?= htmlspecialchars(
                                                    $direccion["telefono"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>


                                            <?php if (
                                                !empty($direccion["referencia"])
                                            ): ?>

                                                <small>
                                                    Referencia:
                                                    <?= htmlspecialchars(
                                                        $direccion["referencia"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </label>


                                <?php endforeach; ?>


                            </div>


                            <!-- =================================
                                 MÉTODO DE PAGO
                            ================================== -->

                            <div class="checkout-pago">

                                <h3>
                                    Método de pago
                                </h3>

                                <p>
                                    Por ahora utilizaremos un método
                                    provisional mientras integramos
                                    el pago de prueba.
                                </p>


                                <label class="checkout-pago-opcion">

                                    <input
                                        type="radio"
                                        name="metodo_pago"
                                        value="Contra entrega"
                                        checked
                                    >

                                    <span>
                                        Pago contra entrega
                                    </span>

                                </label>

                            </div>


                        </form>


                    <?php else: ?>

                        <div class="checkout-nueva-direccion">


                            <div class="checkout-sin-direccion">

                                <strong>
                                    No tienes una dirección registrada.
                                </strong>

                                <p>
                                    Registra dónde deseas recibir tu pedido.
                                </p>

                            </div>


                            <?php if (!empty($erroresDireccion)): ?>

                                <div
                                    class="checkout-form-error"
                                    role="alert"
                                >

                                    <strong>
                                        Revisa los siguientes datos:
                                    </strong>

                                    <ul>

                                        <?php foreach (
                                            $erroresDireccion
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


                            <?php if ($errorDireccion !== ""): ?>

                                <div
                                    class="checkout-form-error"
                                    role="alert"
                                >

                                    <?= htmlspecialchars(
                                        $errorDireccion,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <form
                                action="guardar_direccion.php"
                                method="POST"
                                class="checkout-direccion-form"
                                autocomplete="on"
                            >


                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["csrf_checkout"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >


                                <!-- =============================
                                    NOMBRE DIRECCIÓN
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="nombreDireccion">
                                        Nombre de la dirección *
                                    </label>

                                    <input
                                        type="text"
                                        id="nombreDireccion"
                                        name="nombre"
                                        maxlength="100"
                                        required
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["nombre"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        placeholder="Ej. Casa, finca, oficina"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/100
                                    </small>

                                </div>


                                <!-- =============================
                                    RECEPTOR
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="receptorDireccion">
                                        Persona que recibe *
                                    </label>

                                    <input
                                        type="text"
                                        id="receptorDireccion"
                                        name="receptor"
                                        maxlength="200"
                                        required
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["receptor"]
                                            ?? trim(
                                                $cliente["nombre"] .
                                                " " .
                                                $cliente["apellido"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/200
                                    </small>

                                </div>


                                <!-- =============================
                                    TELÉFONO
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="telefonoDireccion">
                                        Teléfono *
                                    </label>

                                    <input
                                        type="tel"
                                        id="telefonoDireccion"
                                        name="telefono"
                                        maxlength="20"
                                        required
                                        inputmode="tel"
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["telefono"]
                                            ?? $cliente["telefono"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/20
                                    </small>

                                </div>


                                <!-- =============================
                                    DIRECCIÓN
                                ============================== -->

                                <div class="checkout-campo checkout-campo-completo">

                                    <label for="direccionEntrega">
                                        Dirección *
                                    </label>

                                    <input
                                        type="text"
                                        id="direccionEntrega"
                                        name="direccion"
                                        maxlength="200"
                                        required
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["direccion"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        placeholder="Ej. Carrera 5 # 10-25"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/200
                                    </small>

                                </div>


                                <!-- =============================
                                    BARRIO
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="barrioDireccion">
                                        Barrio
                                    </label>

                                    <input
                                        type="text"
                                        id="barrioDireccion"
                                        name="barrio"
                                        maxlength="100"
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["barrio"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/100
                                    </small>

                                </div>


                                <!-- =============================
                                    MUNICIPIO
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="municipioDireccion">
                                        Municipio *
                                    </label>

                                    <input
                                        type="text"
                                        id="municipioDireccion"
                                        name="municipio"
                                        maxlength="100"
                                        required
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["municipio"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/100
                                    </small>

                                </div>


                                <!-- =============================
                                    DEPARTAMENTO
                                ============================== -->

                                <div class="checkout-campo">

                                    <label for="departamentoDireccion">
                                        Departamento *
                                    </label>

                                    <input
                                        type="text"
                                        id="departamentoDireccion"
                                        name="departamento"
                                        maxlength="100"
                                        required
                                        data-contador-checkout
                                        value="<?= htmlspecialchars(
                                            $datosDireccion["departamento"]
                                            ?? "Tolima",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <small class="checkout-contador">
                                        <span>0</span>/100
                                    </small>

                                </div>


                                <!-- =============================
                                    REFERENCIA
                                ============================== -->

                                <div class="checkout-campo checkout-campo-completo">

                                    <label for="referenciaDireccion">
                                        Referencia
                                    </label>

                                    <textarea
                                        id="referenciaDireccion"
                                        name="referencia"
                                        maxlength="500"
                                        rows="3"
                                        data-contador-checkout
                                        placeholder="Ej. Casa de portón verde, frente al parque"
                                    ><?= htmlspecialchars(
                                        $datosDireccion["referencia"]
                                        ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?></textarea>

                                    <small class="checkout-contador">
                                        <span>0</span>/500
                                    </small>

                                </div>


                                <!-- =============================
                                    PRINCIPAL
                                ============================== -->

                                <label class="checkout-principal-check">

                                    <input
                                        type="checkbox"
                                        name="principal"
                                        value="1"
                                        checked
                                    >

                                    <span>
                                        Guardar como dirección principal
                                    </span>

                                </label>


                                <div class="checkout-form-acciones">

                                    <button
                                        type="submit"
                                        class="checkout-guardar-direccion"
                                    >
                                        Guardar dirección
                                    </button>

                                </div>


                            </form>

                        </div>


                    <?php endif; ?>

                </section>

            </div>


            <!-- =====================================
                 RESUMEN
            ====================================== -->

            <aside class="checkout-resumen">

                <span class="checkout-etiqueta">
                    TU PEDIDO
                </span>

                <h2>
                    Resumen
                </h2>


                <div class="checkout-productos">


                    <?php foreach (
                        $productosCheckout
                        as $producto
                    ): ?>


                        <article class="checkout-producto">


                            <div class="checkout-producto-imagen">

                                <?php if (
                                    !empty($producto["imagen"])
                                ): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $base_url .
                                            "../" .
                                            $producto["imagen"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $producto["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        Sin imagen
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="checkout-producto-info">

                                <strong>
                                    <?= htmlspecialchars(
                                        $producto["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>

                                <small>
                                    Cantidad:
                                    <?= (int) $producto["cantidad"] ?>
                                </small>


                                <?php if (
                                    $producto["disponible"]
                                ): ?>

                                    <span>
                                        $<?= number_format(
                                            $producto["subtotal"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="checkout-producto-error">
                                        <?= htmlspecialchars(
                                            $producto["problema"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        </article>


                    <?php endforeach; ?>


                </div>


                <div class="checkout-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        $<?= number_format(
                            $totalCompra,
                            0,
                            ",",
                            "."
                        ) ?>
                    </strong>

                </div>


                <?php if (
                    !empty($direcciones) &&
                    $carritoValido
                ): ?>

                    <button
                        type="submit"
                        form="formCheckout"
                        class="checkout-confirmar"
                    >
                        Confirmar pedido
                    </button>

                <?php else: ?>

                    <button
                        type="button"
                        class="checkout-confirmar"
                        disabled
                    >
                        Confirmar pedido
                    </button>

                <?php endif; ?>


                <a
                    href="<?= htmlspecialchars(
                        $base_url . "carrito/",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    class="checkout-volver"
                >
                    Volver al carrito
                </a>


                <p class="checkout-seguridad">
                    El precio, stock, dirección y total serán
                    verificados nuevamente antes de crear el pedido.
                </p>

            </aside>

        </div>

    </div>

</main>


<?php

require_once("../includes/footer.php");

?>