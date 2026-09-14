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
// CLIENTE LOGUEADO
// =========================================

if (
    !isset($_SESSION["id_usuario"]) ||
    ($_SESSION["rol"] ?? "") !== "cliente"
) {

    header(
        "Location: " .
        $base_url .
        "index.php"
    );

    exit;
}


$idUsuario =
    (int) $_SESSION["id_usuario"];


if ($idUsuario < 1) {

    header(
        "Location: " .
        $base_url .
        "index.php"
    );

    exit;
}

// =========================================
// TOKEN ÚNICO DE LA COMPRA
// =========================================

$tokenCheckout =
    $_POST["token_checkout"] ?? "";


if (
    !is_string($tokenCheckout) ||
    !preg_match(
        '/^[a-f0-9]{64}$/',
        $tokenCheckout
    )
) {

    $_SESSION["checkout_error"] =
        "La identificación de la compra no es válida. Recarga el checkout e inténtalo nuevamente.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// ¿ESTA COMPRA YA FUE PROCESADA?
// =========================================
//
// Esto se comprueba ANTES del CSRF.
//
// No crea ni modifica nada. Solamente permite
// recuperar un pedido ya realizado si, por
// ejemplo, se perdió la conexión después
// de que el servidor lo confirmó.
//

$sqlPedidoExistente = "
    SELECT id_pedido
    FROM pedidos
    WHERE token_checkout = ?
      AND id_usuario = ?
    LIMIT 1
";


$stmtPedidoExistente =
    $conexion->prepare(
        $sqlPedidoExistente
    );


if (!$stmtPedidoExistente) {

    $_SESSION["checkout_error"] =
        "No fue posible verificar el estado de la compra.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


$stmtPedidoExistente->bind_param(
    "si",
    $tokenCheckout,
    $idUsuario
);

$stmtPedidoExistente->execute();

$pedidoExistente =
    $stmtPedidoExistente
        ->get_result()
        ->fetch_assoc();

$stmtPedidoExistente->close();


if ($pedidoExistente) {

    $idPedidoExistente =
        (int) $pedidoExistente["id_pedido"];


    header(
        "Location: " .
        $base_url .
        "pedidos/?confirmado=" .
        $idPedidoExistente
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
        "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}

// =========================================
// VALIDAR TOKEN CONTRA LA SESIÓN
// =========================================

$tokenSesion =
    $_SESSION["token_checkout"] ?? "";


if (
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

    $_SESSION["checkout_error"] =
        "Tu carrito está vacío.";

    header(
        "Location: " .
        $base_url .
        "carrito/"
    );

    exit;
}


// =========================================
// VALIDAR DIRECCIÓN
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
// VALIDAR MÉTODO DE PAGO
// =========================================

$metodoPago =
    trim(
        $_POST["metodo_pago"] ?? ""
    );


$metodosPermitidos = [
    "Contra entrega",
    "Pago simulado"
];


if (
    !in_array(
        $metodoPago,
        $metodosPermitidos,
        true
    )
) {

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
// COMPROBAR DIRECCIÓN DEL CLIENTE
// =========================================

$sqlDireccion = "
    SELECT
        id_direccion,
        nombre,
        receptor,
        telefono,
        direccion,
        barrio,
        municipio,
        departamento,
        referencia
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
        "No fue posible validar la dirección.";

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
// PROTECCIÓN CONTRA ENVÍOS MUY RÁPIDOS
// =========================================

$ahora = time();

$ultimoIntentoConfirmacion =
    (int) (
        $_SESSION["ultimo_intento_confirmacion"] ?? 0
    );


if (
    $ultimoIntentoConfirmacion > 0 &&
    ($ahora - $ultimoIntentoConfirmacion) < 2
) {

    $_SESSION["checkout_error"] =
        "La solicitud se está procesando demasiado rápido. Espera un momento e inténtalo nuevamente.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


$_SESSION["ultimo_intento_confirmacion"] =
    $ahora;


// =========================================
// VALIDAR PAGO EN LÍNEA
// =========================================

if ($metodoPago === "Pago simulado") {

    $autorizacionPago =
        $_SESSION["pago_simulado_aprobado"] ?? null;


    // -------------------------------------
    // DEBE EXISTIR AUTORIZACIÓN
    // -------------------------------------

    if (!is_array($autorizacionPago)) {

        $_SESSION["checkout_error"] =
            "El pago en línea no pudo ser validado. Inténtalo nuevamente.";

        header(
            "Location: " .
            $base_url .
            "checkout/"
        );

        exit;
    }


    // -------------------------------------
    // DATOS DE LA AUTORIZACIÓN
    // -------------------------------------

    $usuarioAutorizado =
        (int) (
            $autorizacionPago["id_usuario"] ?? 0
        );

    $direccionAutorizada =
        (int) (
            $autorizacionPago["direccion"] ?? 0
        );

    $tokenAutorizado =
        $autorizacionPago["token_checkout"] ?? "";

    $fechaAutorizacion =
        (int) (
            $autorizacionPago["fecha"] ?? 0
        );


    // -------------------------------------
    // USUARIO
    // -------------------------------------

    if ($usuarioAutorizado !== $idUsuario) {

        unset(
            $_SESSION["pago_simulado_aprobado"]
        );

        $_SESSION["checkout_error"] =
            "La autorización del pago no corresponde a esta cuenta.";

        header(
            "Location: " .
            $base_url .
            "checkout/"
        );

        exit;
    }


    // -------------------------------------
    // DIRECCIÓN
    // -------------------------------------

    if ($direccionAutorizada !== $idDireccion) {

        unset(
            $_SESSION["pago_simulado_aprobado"]
        );

        $_SESSION["checkout_error"] =
            "La autorización del pago no corresponde a la dirección seleccionada.";

        header(
            "Location: " .
            $base_url .
            "checkout/"
        );

        exit;
    }


    // -------------------------------------
    // TOKEN
    // -------------------------------------

    if (
        !is_string($tokenAutorizado) ||
        $tokenAutorizado === "" ||
        !hash_equals(
            $tokenCheckout,
            $tokenAutorizado
        )
    ) {

        unset(
            $_SESSION["pago_simulado_aprobado"]
        );

        $_SESSION["checkout_error"] =
            "La autorización del pago ya no es válida.";

        header(
            "Location: " .
            $base_url .
            "checkout/"
        );

        exit;
    }


    // -------------------------------------
    // TIEMPO MÁXIMO: 5 MINUTOS
    // -------------------------------------

    if (
        $fechaAutorizacion < 1 ||
        $fechaAutorizacion > $ahora ||
        ($ahora - $fechaAutorizacion) > 300
    ) {

        unset(
            $_SESSION["pago_simulado_aprobado"]
        );

        $_SESSION["checkout_error"] =
            "La autorización del pago expiró. Realiza nuevamente el proceso de pago.";

        header(
            "Location: " .
            $base_url .
            "checkout/"
        );

        exit;
    }


    // -------------------------------------
    // AUTORIZACIÓN DE UN SOLO USO
    // -------------------------------------
    //
    // Ya comprobamos usuario, dirección,
    // token y tiempo.
    //
    // Se elimina antes de crear el pedido
    // para que no pueda reutilizarse.
    //

    unset(
        $_SESSION["pago_simulado_aprobado"]
    );
}

// =========================================
// COMENZAR TRANSACCIÓN
// =========================================

$conexion->begin_transaction();


try {

    // =====================================
    // VALIDAR CLIENTE DENTRO DE LA TRANSACCIÓN
    // =====================================

    $sqlUsuario = "
        SELECT id_usuario
        FROM usuarios
        WHERE id_usuario = ?
          AND rol = 'cliente'
          AND estado = 1
        LIMIT 1
        FOR UPDATE
    ";


    $stmtUsuario =
        $conexion->prepare(
            $sqlUsuario
        );


    if (!$stmtUsuario) {

        throw new Exception(
            "No fue posible validar el cliente."
        );
    }


    $stmtUsuario->bind_param(
        "i",
        $idUsuario
    );

    $stmtUsuario->execute();

    $usuarioValido =
        $stmtUsuario
            ->get_result()
            ->fetch_assoc();

    $stmtUsuario->close();


    if (!$usuarioValido) {

        throw new Exception(
            "La cuenta del cliente ya no está disponible."
        );
    }


    // =====================================
    // BUSCAR ESTADO PENDIENTE
    // =====================================

    $sqlEstado = "
        SELECT id_estado
        FROM estado_pedido
        WHERE nombre = 'Pendiente'
        LIMIT 1
    ";


    $resultadoEstado =
        $conexion->query(
            $sqlEstado
        );


    if (!$resultadoEstado) {

        throw new Exception(
            "No fue posible obtener el estado inicial del pedido."
        );
    }


    $estado =
        $resultadoEstado->fetch_assoc();


    if (!$estado) {

        throw new Exception(
            "No existe el estado Pendiente."
        );
    }


    $idEstadoPendiente =
        (int) $estado["id_estado"];


    // =====================================
    // VALIDAR TODOS LOS PRODUCTOS
    // =====================================

    $productosPedido = [];

    $totalPedido = 0.00;


    $sqlProducto = "
        SELECT
            p.id_producto,
            p.nombre,
            p.precio,
            p.estado,
            c.estado AS categoria_estado,
            i.stock_actual

        FROM productos p

        INNER JOIN categorias c
            ON c.id_categoria = p.id_categoria

        INNER JOIN inventario i
            ON i.id_producto = p.id_producto

        WHERE p.id_producto = ?

        LIMIT 1

        FOR UPDATE
    ";


    $stmtProducto =
        $conexion->prepare(
            $sqlProducto
        );


    if (!$stmtProducto) {

        throw new Exception(
            "No fue posible validar los productos del carrito."
        );
    }


    foreach (
        $_SESSION["carrito"]
        as $idProductoSesion => $item
    ) {

        $idProducto =
            filter_var(
                $idProductoSesion,
                FILTER_VALIDATE_INT
            );


        $cantidad =
            (int) (
                $item["cantidad"] ?? 0
            );


        if (
            $idProducto === false ||
            $idProducto < 1 ||
            $cantidad < 1
        ) {

            throw new Exception(
                "El carrito contiene datos no válidos."
            );
        }


        // ---------------------------------
        // EVITAR CANTIDADES EXTREMAS
        // ---------------------------------

        if ($cantidad > 1000000) {

            throw new Exception(
                "La cantidad solicitada no es válida."
            );
        }


        $stmtProducto->bind_param(
            "i",
            $idProducto
        );

        $stmtProducto->execute();

        $producto =
            $stmtProducto
                ->get_result()
                ->fetch_assoc();


        if (!$producto) {

            throw new Exception(
                "Uno de los productos del carrito ya no existe."
            );
        }


        if (
            (int) $producto["estado"] !== 1 ||
            (int) $producto["categoria_estado"] !== 1
        ) {

            throw new Exception(
                "Uno de los productos ya no está disponible."
            );
        }


        $stockActual =
            (int) $producto["stock_actual"];


        if ($stockActual < $cantidad) {

            throw new Exception(
                "No hay stock suficiente de " .
                $producto["nombre"] .
                ". Disponible: " .
                $stockActual .
                "."
            );
        }


        $precioUnitario =
            (float) $producto["precio"];


        if (
            !is_finite($precioUnitario) ||
            $precioUnitario < 0
        ) {

            throw new Exception(
                "Uno de los productos tiene un precio no válido."
            );
        }


        $subtotal =
            $precioUnitario * $cantidad;


        $totalPedido +=
            $subtotal;


        // ---------------------------------
        // PROTEGER DECIMAL(10,2)
        // ---------------------------------

        if (
            $subtotal > 99999999.99 ||
            $totalPedido > 99999999.99
        ) {

            throw new Exception(
                "El valor del pedido supera el máximo permitido."
            );
        }


        $productosPedido[] = [
            "id_producto" =>
                (int) $producto["id_producto"],

            "nombre" =>
                $producto["nombre"],

            "cantidad" =>
                $cantidad,

            "precio_unitario" =>
                $precioUnitario,

            "subtotal" =>
                $subtotal
        ];
    }


    $stmtProducto->close();


    if (empty($productosPedido)) {

        throw new Exception(
            "El carrito no contiene productos válidos."
        );
    }


    // =====================================
    // CREAR PEDIDO
    // =====================================

    $sqlPedido = "
        INSERT INTO pedidos (
            id_usuario,
            id_direccion,
            id_estado,
            total,
            metodo_pago,
            token_checkout
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmtPedido =
        $conexion->prepare(
            $sqlPedido
        );


    if (!$stmtPedido) {

        throw new Exception(
            "No fue posible preparar el pedido."
        );
    }


        $stmtPedido->bind_param(
        "iiidss",
        $idUsuario,
        $idDireccion,
        $idEstadoPendiente,
        $totalPedido,
        $metodoPago,
        $tokenCheckout
    );

    if (!$stmtPedido->execute()) {

        throw new Exception(
            "No fue posible crear el pedido."
        );
    }


    $idPedido =
        (int) $conexion->insert_id;


    $stmtPedido->close();


    if ($idPedido < 1) {

        throw new Exception(
            "No fue posible identificar el pedido creado."
        );
    }

    // =====================================
    // GUARDAR COPIA DE LA DIRECCIÓN
    // DEL PEDIDO
    // =====================================

    $sqlDireccionPedido = "
        INSERT INTO direccion_pedido (
            id_pedido,
            nombre,
            receptor,
            telefono,
            direccion,
            barrio,
            municipio,
            departamento,
            referencia
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            NULLIF(?, ''),
            ?,
            ?,
            NULLIF(?, '')
        )
    ";


    $stmtDireccionPedido =
        $conexion->prepare(
            $sqlDireccionPedido
        );


    if (!$stmtDireccionPedido) {

        throw new Exception(
            "No fue posible preparar la dirección del pedido."
        );
    }


    $stmtDireccionPedido->bind_param(
        "issssssss",
        $idPedido,
        $direccionValida["nombre"],
        $direccionValida["receptor"],
        $direccionValida["telefono"],
        $direccionValida["direccion"],
        $direccionValida["barrio"],
        $direccionValida["municipio"],
        $direccionValida["departamento"],
        $direccionValida["referencia"]
    );


    if (!$stmtDireccionPedido->execute()) {

        throw new Exception(
            "No fue posible guardar la dirección del pedido."
        );
    }


    $stmtDireccionPedido->close();

    // =====================================
    // PREPARAR DETALLE DEL PEDIDO
    // =====================================

    $sqlDetalle = "
        INSERT INTO detalle_pedido (
            id_pedido,
            id_producto,
            cantidad,
            precio_unitario,
            descuento,
            subtotal
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            0,
            ?
        )
    ";


    $stmtDetalle =
        $conexion->prepare(
            $sqlDetalle
        );


    if (!$stmtDetalle) {

        throw new Exception(
            "No fue posible preparar los productos del pedido."
        );
    }


    // =====================================
    // PREPARAR DESCUENTO DE INVENTARIO
    // =====================================

    $sqlInventario = "
        UPDATE inventario

        SET stock_actual =
            stock_actual - ?

        WHERE id_producto = ?
          AND stock_actual >= ?
    ";


    $stmtInventario =
        $conexion->prepare(
            $sqlInventario
        );


    if (!$stmtInventario) {

        throw new Exception(
            "No fue posible preparar la actualización del inventario."
        );
    }


    // =====================================
    // PREPARAR MOVIMIENTO DE INVENTARIO
    // =====================================

    $sqlMovimiento = "
        INSERT INTO movimientos_inventario (
            id_producto,
            id_ingreso,
            tipo,
            cantidad,
            motivo,
            observacion,
            id_pedido,
            id_proveedor,
            id_usuario
        )
        VALUES (
            ?,
            NULL,
            'Salida',
            ?,
            ?,
            ?,
            ?,
            NULL,
            ?
        )
    ";


    $stmtMovimiento =
        $conexion->prepare(
            $sqlMovimiento
        );


    if (!$stmtMovimiento) {

        throw new Exception(
            "No fue posible preparar el historial de inventario."
        );
    }


    // =====================================
    // GUARDAR CADA PRODUCTO
    // =====================================

    foreach (
        $productosPedido
        as $producto
    ) {

        $idProducto =
            (int) $producto["id_producto"];

        $cantidad =
            (int) $producto["cantidad"];

        $precioUnitario =
            (float) $producto["precio_unitario"];

        $subtotal =
            (float) $producto["subtotal"];


        // ---------------------------------
        // DETALLE PEDIDO
        // ---------------------------------

        $stmtDetalle->bind_param(
            "iiidd",
            $idPedido,
            $idProducto,
            $cantidad,
            $precioUnitario,
            $subtotal
        );


        if (!$stmtDetalle->execute()) {

            throw new Exception(
                "No fue posible guardar los productos del pedido."
            );
        }


        // ---------------------------------
        // DESCONTAR STOCK
        // ---------------------------------

        $stmtInventario->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );


        if (!$stmtInventario->execute()) {

            throw new Exception(
                "No fue posible actualizar el stock."
            );
        }


        if (
            $stmtInventario->affected_rows !== 1
        ) {

            throw new Exception(
                "El stock cambió mientras se procesaba la compra. Vuelve al carrito y revisa las cantidades."
            );
        }


        // ---------------------------------
        // MOVIMIENTO DE INVENTARIO
        // ---------------------------------

        $cantidadMovimiento =
            -$cantidad;

        $motivo =
            "Venta de producto";

        $observacion =
            "Salida de inventario por pedido #" .
            $idPedido;


        $stmtMovimiento->bind_param(
            "iissii",
            $idProducto,
            $cantidadMovimiento,
            $motivo,
            $observacion,
            $idPedido,
            $idUsuario
        );


        if (!$stmtMovimiento->execute()) {

            throw new Exception(
                "No fue posible registrar el movimiento de inventario."
            );
        }
    }


    $stmtDetalle->close();

    $stmtInventario->close();

    $stmtMovimiento->close();


    // =====================================
    // HISTORIAL DEL ESTADO
    // =====================================

    $sqlHistorial = "
        INSERT INTO historial_estado_pedido (
            id_pedido,
            id_estado,
            ubicacion,
            descripcion
        )
        VALUES (
            ?,
            ?,
            NULL,
            ?
        )
    ";


    $stmtHistorial =
        $conexion->prepare(
            $sqlHistorial
        );


    if (!$stmtHistorial) {

        throw new Exception(
            "No fue posible preparar el historial del pedido."
        );
    }


    $descripcionEstado =
        "Pedido creado y pendiente de procesamiento.";


    $stmtHistorial->bind_param(
        "iis",
        $idPedido,
        $idEstadoPendiente,
        $descripcionEstado
    );


    if (!$stmtHistorial->execute()) {

        throw new Exception(
            "No fue posible registrar el estado inicial del pedido."
        );
    }


    $stmtHistorial->close();


    // =====================================
    // REGISTRAR PAGO EN LÍNEA
    // =====================================

    if ($metodoPago === "Pago simulado") {

        // ---------------------------------
        // REFERENCIA ÚNICA
        // ---------------------------------

        $referenciaPago =
            "AGR-" .
            strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );


        $moneda =
            "COP";

        $estadoPago =
            "Aprobado";


        // ---------------------------------
        // CREAR PAGO
        // ---------------------------------

        $sqlPago = "
            INSERT INTO pagos (
                id_pedido,
                monto_total,
                moneda,
                metodo_pago,
                estado,
                referencia_unica,
                endpoint_key
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NULL
            )
        ";


        $stmtPago =
            $conexion->prepare(
                $sqlPago
            );


        if (!$stmtPago) {

            throw new Exception(
                "No fue posible preparar el registro del pago."
            );
        }


        $stmtPago->bind_param(
            "idssss",
            $idPedido,
            $totalPedido,
            $moneda,
            $metodoPago,
            $estadoPago,
            $referenciaPago
        );


        if (!$stmtPago->execute()) {

            throw new Exception(
                "No fue posible registrar el pago."
            );
        }


        $idPago =
            (int) $conexion->insert_id;


        $stmtPago->close();


        if ($idPago < 1) {

            throw new Exception(
                "No fue posible identificar el pago registrado."
            );
        }


        // =================================
        // REGISTRAR INTENTO DE PAGO
        // =================================

        $numeroIntento =
            1;

        $estadoIntento =
            "Exitoso";

        $respuestaPago =
            json_encode(
                [
                    "entorno" =>
                        "demostrativo",

                    "resultado" =>
                        "aprobado"
                ],
                JSON_UNESCAPED_UNICODE
            );

        $codigoError =
            null;


        $sqlIntento = "
            INSERT INTO intentos_pago (
                id_pago,
                numero_intento,
                estado,
                respuesta_pasarela,
                codigo_error
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmtIntento =
            $conexion->prepare(
                $sqlIntento
            );


        if (!$stmtIntento) {

            throw new Exception(
                "No fue posible preparar el intento de pago."
            );
        }


        $stmtIntento->bind_param(
            "iisss",
            $idPago,
            $numeroIntento,
            $estadoIntento,
            $respuestaPago,
            $codigoError
        );


        if (!$stmtIntento->execute()) {

            throw new Exception(
                "No fue posible registrar el intento de pago."
            );
        }


        $stmtIntento->close();
    }

    // =====================================
    // CONFIRMAR TODO
    // =====================================

    $conexion->commit();


    // =========================================
    // LIMPIAR COMPRA FINALIZADA
    // =========================================

    unset(
        $_SESSION["carrito"],
        $_SESSION["token_checkout"],
        $_SESSION["pago_simulado_aprobado"],
        $_SESSION["ultimo_intento_confirmacion"]
    );

    // Nuevo CSRF para operaciones futuras
    $_SESSION["csrf_checkout"] =
        bin2hex(
            random_bytes(32)
        );
        
    // =====================================
    // GUARDAR PEDIDO RECIÉN CREADO
    // =====================================

    $_SESSION["pedido_confirmado"] =
        $idPedido;


    header(
        "Location: " .
        $base_url .
        "pedidos/?confirmado=" .
        $idPedido
    );

    exit;


} catch (Throwable $e) {

    // =====================================
    // DESHACER TODO
    // =====================================

    $conexion->rollback();


    // =====================================
    // MENSAJE AMIGABLE
    // =====================================

    $_SESSION["checkout_error"] =
        $e->getMessage();


    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}