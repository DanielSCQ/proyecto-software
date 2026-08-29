<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ======================================================
// VERIFICAR SESIÓN
// ======================================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

require_once("../../config/conexion.php");


// ======================================================
// VERIFICAR ID DEL INGRESO
// ======================================================

if (
    !isset($_GET["id"]) ||
    filter_var($_GET["id"], FILTER_VALIDATE_INT) === false ||
    (int)$_GET["id"] <= 0
) {

    header("Location: inventario.php");
    exit();

}

$id_ingreso = (int)$_GET["id"];


// ======================================================
// VARIABLES
// ======================================================

$errores = [];


// ======================================================
// PROCESAR EDICIÓN
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ==================================================
    // RECIBIR DATOS
    // ==================================================

    $id_proveedor = filter_input(
        INPUT_POST,
        "id_proveedor",
        FILTER_VALIDATE_INT
    );

    $fecha = trim($_POST["fecha"] ?? "");

    $documento = trim($_POST["documento"] ?? "");

    $referencia = trim($_POST["referencia"] ?? "");

    $id_producto = filter_input(
        INPUT_POST,
        "id_producto",
        FILTER_VALIDATE_INT
    );

    $cantidadTexto = trim($_POST["cantidad"] ?? "");

    $precioTexto = trim($_POST["precio_compra"] ?? "");

    $observacion = trim($_POST["observacion"] ?? "");


    // ==================================================
    // VALIDAR PROVEEDOR
    // ==================================================

    if (
        $id_proveedor === false ||
        $id_proveedor === null ||
        $id_proveedor <= 0
    ) {

        $errores[] =
            "Debes seleccionar un proveedor.";

    }


    // ==================================================
    // VALIDAR FECHA
    // ==================================================

    if ($fecha === "") {

        $errores[] =
            "La fecha es obligatoria.";

    } else {

        $fechaValida =
            DateTime::createFromFormat(
                "Y-m-d",
                $fecha
            );

        if (
            !$fechaValida ||
            $fechaValida->format("Y-m-d") !== $fecha
        ) {

            $errores[] =
                "La fecha ingresada no es válida.";

        }

    }


    // ==================================================
    // VALIDAR DOCUMENTO
    // ==================================================

    if (mb_strlen($documento) > 100) {

        $errores[] =
            "El documento no puede superar los 100 caracteres.";

    }

    if (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $documento
        )
    ) {

        $errores[] =
            "El documento contiene caracteres no permitidos.";

    }


    // ==================================================
    // VALIDAR REFERENCIA
    // ==================================================

    if (mb_strlen($referencia) > 100) {

        $errores[] =
            "La referencia no puede superar los 100 caracteres.";

    }

    if (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $referencia
        )
    ) {

        $errores[] =
            "La referencia contiene caracteres no permitidos.";

    }


    // ==================================================
    // VALIDAR PRODUCTO
    // ==================================================

    if (
        $id_producto === false ||
        $id_producto === null ||
        $id_producto <= 0
    ) {

        $errores[] =
            "Debes seleccionar un producto.";

    }


    // ==================================================
    // VALIDAR CANTIDAD
    // ==================================================

    if (
        $cantidadTexto === "" ||
        !ctype_digit($cantidadTexto)
    ) {

        $errores[] =
            "La cantidad debe ser un número entero mayor que cero.";

    } else {

        $cantidad = (int)$cantidadTexto;

        if ($cantidad <= 0) {

            $errores[] =
                "La cantidad debe ser mayor que cero.";

        } elseif ($cantidad > 1000000) {

            $errores[] =
                "La cantidad no puede superar 1.000.000 unidades.";

        }

    }


    // ==================================================
    // VALIDAR PRECIO DE COMPRA
    // ==================================================

    if ($precioTexto === "") {

        $errores[] =
            "El precio de compra es obligatorio.";

    } elseif (
        !preg_match(
            '/^\d+(\.\d{1,2})?$/',
            $precioTexto
        )
    ) {

        $errores[] =
            "El precio de compra debe ser un número válido con máximo 2 decimales.";

    } else {

        $precio_compra =
            (float)$precioTexto;

        if ($precio_compra <= 0) {

            $errores[] =
                "El precio de compra debe ser mayor que cero.";

        } elseif (
            $precio_compra > 999999999.99
        ) {

            $errores[] =
                "El precio de compra supera el límite permitido.";

        }

    }


    // ==================================================
    // VALIDAR OBSERVACIÓN
    // ==================================================

    if (mb_strlen($observacion) > 500) {

        $errores[] =
            "La observación no puede superar los 500 caracteres.";

    }

    if (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $observacion
        )
    ) {

        $errores[] =
            "La observación contiene caracteres no permitidos.";

    }


    // ==================================================
    // SI NO HAY ERRORES
    // ==================================================

    if (empty($errores)) {

        try {

            // ==========================================
            // INICIAR TRANSACCIÓN
            // ==========================================

            $conexion->begin_transaction();


            // ==========================================
            // OBTENER INGRESO ORIGINAL
            // ==========================================

            $sqlAnterior = "

                SELECT

                    ii.id_ingreso,
                    ii.id_proveedor,
                    ii.fecha,
                    ii.documento,
                    ii.referencia,
                    ii.total_compra,
                    ii.estado,

                    di.id_detalle_ingreso,
                    di.id_producto,
                    di.cantidad,
                    di.precio_compra,
                    di.subtotal

                FROM ingresos_inventario ii

                INNER JOIN detalle_ingreso di
                    ON ii.id_ingreso = di.id_ingreso

                WHERE ii.id_ingreso = ?

                LIMIT 1

            ";

            $stmtAnterior =
                $conexion->prepare($sqlAnterior);

            if (!$stmtAnterior) {

                throw new Exception(
                    "No fue posible consultar el ingreso."
                );

            }

            $stmtAnterior->bind_param(
                "i",
                $id_ingreso
            );

            if (!$stmtAnterior->execute()) {

                throw new Exception(
                    "No fue posible obtener el ingreso."
                );

            }

            $resultadoAnterior =
                $stmtAnterior->get_result();

            $ingresoAnterior =
                $resultadoAnterior->fetch_assoc();


            if (!$ingresoAnterior) {

                throw new Exception(
                    "El ingreso que intentas editar no existe."
                );

            }


            // ==========================================
            // VERIFICAR ESTADO
            // ==========================================

            if (
                $ingresoAnterior["estado"] ===
                "Cancelado"
            ) {

                throw new Exception(
                    "No se puede editar un ingreso cancelado."
                );

            }


            // ==========================================
            // DATOS ANTERIORES
            // ==========================================

            $productoAnterior =
                (int)$ingresoAnterior["id_producto"];

            $cantidadAnterior =
                (int)$ingresoAnterior["cantidad"];


            // ==========================================
            // VERIFICAR PROVEEDOR ACTIVO
            // ==========================================

            $sqlProveedor = "

                SELECT id_proveedor

                FROM proveedores

                WHERE id_proveedor = ?

                AND estado = 1

                LIMIT 1

            ";

            $stmtProveedor =
                $conexion->prepare($sqlProveedor);

            $stmtProveedor->bind_param(
                "i",
                $id_proveedor
            );

            if (!$stmtProveedor->execute()) {

                throw new Exception(
                    "No fue posible verificar el proveedor."
                );

            }

            $resultadoProveedor =
                $stmtProveedor->get_result();

            if (
                $resultadoProveedor->num_rows === 0
            ) {

                throw new Exception(
                    "El proveedor seleccionado no existe o está inactivo."
                );

            }


            // ==========================================
            // VERIFICAR PRODUCTO ACTIVO
            // ==========================================

            $sqlProducto = "

                SELECT id_producto

                FROM productos

                WHERE id_producto = ?

                AND estado = 1

                LIMIT 1

            ";

            $stmtProducto =
                $conexion->prepare($sqlProducto);

            $stmtProducto->bind_param(
                "i",
                $id_producto
            );

            if (!$stmtProducto->execute()) {

                throw new Exception(
                    "No fue posible verificar el producto."
                );

            }

            $resultadoProducto =
                $stmtProducto->get_result();

            if (
                $resultadoProducto->num_rows === 0
            ) {

                throw new Exception(
                    "El producto seleccionado no existe o está inactivo."
                );

            }


            // ==========================================
            // CALCULAR VALORES
            // ==========================================

            $subtotal =
                $cantidad * $precio_compra;

            $total_compra =
                $subtotal;


            // ==================================================
            // CASO 1: MISMO PRODUCTO
            // ==================================================

            if (
                $productoAnterior === $id_producto
            ) {

                // ======================================
                // OBTENER STOCK
                // ======================================

                $sqlStock = "

                    SELECT stock_actual

                    FROM inventario

                    WHERE id_producto = ?

                    FOR UPDATE

                ";

                $stmtStock =
                    $conexion->prepare($sqlStock);

                $stmtStock->bind_param(
                    "i",
                    $productoAnterior
                );

                if (!$stmtStock->execute()) {

                    throw new Exception(
                        "No fue posible consultar el stock."
                    );

                }

                $resultadoStock =
                    $stmtStock->get_result();

                $stock =
                    $resultadoStock->fetch_assoc();


                if (!$stock) {

                    throw new Exception(
                        "No existe inventario para el producto."
                    );

                }

                $stockActual =
                    (int)$stock["stock_actual"];


                // ======================================
                // REVERTIR CANTIDAD ANTERIOR
                // ======================================

                $stockDespuesReversion =
                    $stockActual -
                    $cantidadAnterior;


                if (
                    $stockDespuesReversion < 0
                ) {

                    throw new Exception(
                        "No se puede editar este ingreso porque el stock actual ya no permite revertir la cantidad original."
                    );

                }


                // ======================================
                // APLICAR NUEVA CANTIDAD
                // ======================================

                $nuevoStock =
                    $stockDespuesReversion +
                    $cantidad;


                // ======================================
                // ACTUALIZAR STOCK
                // ======================================

                $sqlActualizarStock = "

                    UPDATE inventario

                    SET stock_actual = ?

                    WHERE id_producto = ?

                ";

                $stmtActualizarStock =
                    $conexion->prepare(
                        $sqlActualizarStock
                    );

                $stmtActualizarStock->bind_param(
                    "ii",
                    $nuevoStock,
                    $id_producto
                );

                if (
                    !$stmtActualizarStock->execute()
                ) {

                    throw new Exception(
                        "No fue posible actualizar el stock."
                    );

                }


                // ======================================
                // REGISTRAR AJUSTE
                // ======================================

                $diferencia =
                    $cantidad -
                    $cantidadAnterior;


                if ($diferencia != 0) {

                    $tipo = "Ajuste";

                    $motivo =
                        "Modificación de ingreso de inventario";

                    $observacionMovimiento =
                        "Cambio de cantidad del ingreso #"
                        . $id_ingreso
                        . ". Cantidad anterior: "
                        . $cantidadAnterior
                        . ". Nueva cantidad: "
                        . $cantidad;

                    if ($observacion !== "") {

                        $observacionMovimiento .=
                            ". Observación: "
                            . $observacion;

                    }

                    $id_usuario =
                        (int)$_SESSION["id_usuario"];


                    $sqlMovimiento = "

                        INSERT INTO movimientos_inventario

                        (
                            id_producto,
                            id_ingreso,
                            tipo,
                            cantidad,
                            motivo,
                            observacion,
                            id_proveedor,
                            id_usuario
                        )

                        VALUES

                        (?, ?, ?, ?, ?, ?, ?, ?)

                    ";

                    $stmtMovimiento =
                        $conexion->prepare(
                            $sqlMovimiento
                        );

                    $stmtMovimiento->bind_param(
                        "iisissii",
                        $id_producto,
                        $id_ingreso,
                        $tipo,
                        $diferencia,
                        $motivo,
                        $observacionMovimiento,
                        $id_proveedor,
                        $id_usuario
                    );

                    if (
                        !$stmtMovimiento->execute()
                    ) {

                        throw new Exception(
                            "No fue posible registrar el movimiento."
                        );

                    }

                }

            }


            // ==================================================
            // CASO 2: CAMBIÓ EL PRODUCTO
            // ==================================================

            else {

                // ======================================
                // STOCK PRODUCTO ANTERIOR
                // ======================================

                $sqlStockAnterior = "

                    SELECT stock_actual

                    FROM inventario

                    WHERE id_producto = ?

                    FOR UPDATE

                ";

                $stmtStockAnterior =
                    $conexion->prepare(
                        $sqlStockAnterior
                    );

                $stmtStockAnterior->bind_param(
                    "i",
                    $productoAnterior
                );

                if (
                    !$stmtStockAnterior->execute()
                ) {

                    throw new Exception(
                        "No fue posible consultar el stock anterior."
                    );

                }

                $resultadoStockAnterior =
                    $stmtStockAnterior->get_result();

                $stockAnterior =
                    $resultadoStockAnterior->fetch_assoc();


                if (!$stockAnterior) {

                    throw new Exception(
                        "No existe inventario para el producto anterior."
                    );

                }

                $stockProductoAnterior =
                    (int)$stockAnterior["stock_actual"];


                // ======================================
                // REVERTIR PRODUCTO ANTERIOR
                // ======================================

                $nuevoStockAnterior =
                    $stockProductoAnterior -
                    $cantidadAnterior;


                if (
                    $nuevoStockAnterior < 0
                ) {

                    throw new Exception(
                        "No se puede cambiar el producto porque el stock anterior ya no permite revertir el ingreso."
                    );

                }


                // ======================================
                // ACTUALIZAR PRODUCTO ANTERIOR
                // ======================================

                $sqlActualizarAnterior = "

                    UPDATE inventario

                    SET stock_actual = ?

                    WHERE id_producto = ?

                ";

                $stmtActualizarAnterior =
                    $conexion->prepare(
                        $sqlActualizarAnterior
                    );

                $stmtActualizarAnterior->bind_param(
                    "ii",
                    $nuevoStockAnterior,
                    $productoAnterior
                );

                if (
                    !$stmtActualizarAnterior->execute()
                ) {

                    throw new Exception(
                        "No fue posible revertir el stock anterior."
                    );

                }


                // ======================================
                // STOCK PRODUCTO NUEVO
                // ======================================

                $sqlStockNuevo = "

                    SELECT stock_actual

                    FROM inventario

                    WHERE id_producto = ?

                    FOR UPDATE

                ";

                $stmtStockNuevo =
                    $conexion->prepare(
                        $sqlStockNuevo
                    );

                $stmtStockNuevo->bind_param(
                    "i",
                    $id_producto
                );

                if (
                    !$stmtStockNuevo->execute()
                ) {

                    throw new Exception(
                        "No fue posible consultar el stock del nuevo producto."
                    );

                }

                $resultadoStockNuevo =
                    $stmtStockNuevo->get_result();

                $stockNuevo =
                    $resultadoStockNuevo->fetch_assoc();


                if (!$stockNuevo) {

                    throw new Exception(
                        "No existe inventario para el nuevo producto."
                    );

                }

                $stockProductoNuevo =
                    (int)$stockNuevo["stock_actual"];


                // ======================================
                // APLICAR NUEVA CANTIDAD
                // ======================================

                $nuevoStockProducto =
                    $stockProductoNuevo +
                    $cantidad;


                // ======================================
                // ACTUALIZAR NUEVO PRODUCTO
                // ======================================

                $sqlActualizarNuevo = "

                    UPDATE inventario

                    SET stock_actual = ?

                    WHERE id_producto = ?

                ";

                $stmtActualizarNuevo =
                    $conexion->prepare(
                        $sqlActualizarNuevo
                    );

                $stmtActualizarNuevo->bind_param(
                    "ii",
                    $nuevoStockProducto,
                    $id_producto
                );

                if (
                    !$stmtActualizarNuevo->execute()
                ) {

                    throw new Exception(
                        "No fue posible actualizar el stock del nuevo producto."
                    );

                }


                // ======================================
                // MOVIMIENTO PRODUCTO ANTERIOR
                // ======================================

                $tipo = "Ajuste";

                $motivo =
                    "Modificación de ingreso de inventario";

                $observacionAnterior =
                    "Se retiraron "
                    . $cantidadAnterior
                    . " unidades debido al cambio de producto del ingreso #"
                    . $id_ingreso;


                if ($observacion !== "") {

                    $observacionAnterior .=
                        ". Observación: "
                        . $observacion;

                }

                $id_usuario =
                    (int)$_SESSION["id_usuario"];


                $cantidadReversion =
                    -$cantidadAnterior;


                $sqlMovimientoAnterior = "

                    INSERT INTO movimientos_inventario

                    (
                        id_producto,
                        id_ingreso,
                        tipo,
                        cantidad,
                        motivo,
                        observacion,
                        id_usuario
                    )

                    VALUES

                    (?, ?, ?, ?, ?, ?, ?)

                ";

                $stmtMovimientoAnterior =
                    $conexion->prepare(
                        $sqlMovimientoAnterior
                    );

                $stmtMovimientoAnterior->bind_param(
                    "iisissi",
                    $productoAnterior,
                    $id_ingreso,
                    $tipo,
                    $cantidadReversion,
                    $motivo,
                    $observacionAnterior,
                    $id_usuario
                );

                if (
                    !$stmtMovimientoAnterior->execute()
                ) {

                    throw new Exception(
                        "No fue posible registrar la reversión."
                    );

                }


                // ======================================
                // MOVIMIENTO NUEVO PRODUCTO
                // ======================================

                $observacionNuevo =
                    "Se agregaron "
                    . $cantidad
                    . " unidades debido al cambio de producto del ingreso #"
                    . $id_ingreso;


                if ($observacion !== "") {

                    $observacionNuevo .=
                        ". Observación: "
                        . $observacion;

                }


                $sqlMovimientoNuevo = "

                    INSERT INTO movimientos_inventario

                    (
                        id_producto,
                        id_ingreso,
                        tipo,
                        cantidad,
                        motivo,
                        observacion,
                        id_proveedor,
                        id_usuario
                    )

                    VALUES

                    (?, ?, ?, ?, ?, ?, ?, ?)

                ";

                $stmtMovimientoNuevo =
                    $conexion->prepare(
                        $sqlMovimientoNuevo
                    );

                $stmtMovimientoNuevo->bind_param(
                    "iisissii",
                    $id_producto,
                    $id_ingreso,
                    $tipo,
                    $cantidad,
                    $motivo,
                    $observacionNuevo,
                    $id_proveedor,
                    $id_usuario
                );

                if (
                    !$stmtMovimientoNuevo->execute()
                ) {

                    throw new Exception(
                        "No fue posible registrar el nuevo movimiento."
                    );

                }

            }


            // ==================================================
            // ACTUALIZAR INGRESO
            // ==================================================

            $sqlActualizarIngreso = "

                UPDATE ingresos_inventario

                SET
                    id_proveedor = ?,
                    fecha = ?,
                    documento = ?,
                    referencia = ?,
                    total_compra = ?

                WHERE id_ingreso = ?

            ";

            $stmtActualizarIngreso =
                $conexion->prepare(
                    $sqlActualizarIngreso
                );

            $stmtActualizarIngreso->bind_param(
                "isssdi",
                $id_proveedor,
                $fecha,
                $documento,
                $referencia,
                $total_compra,
                $id_ingreso
            );

            if (
                !$stmtActualizarIngreso->execute()
            ) {

                throw new Exception(
                    "No fue posible actualizar el ingreso."
                );

            }


            // ==================================================
            // ACTUALIZAR DETALLE
            // ==================================================

            $sqlActualizarDetalle = "

                UPDATE detalle_ingreso

                SET
                    id_producto = ?,
                    cantidad = ?,
                    precio_compra = ?,
                    subtotal = ?

                WHERE id_ingreso = ?

            ";

            $stmtActualizarDetalle =
                $conexion->prepare(
                    $sqlActualizarDetalle
                );

            $stmtActualizarDetalle->bind_param(
                "iiddi",
                $id_producto,
                $cantidad,
                $precio_compra,
                $subtotal,
                $id_ingreso
            );

            if (
                !$stmtActualizarDetalle->execute()
            ) {

                throw new Exception(
                    "No fue posible actualizar el detalle del ingreso."
                );

            }


            // ==================================================
            // CONFIRMAR
            // ==================================================

            $conexion->commit();


            header("Location: inventario.php");
            exit();


        } catch (Throwable $e) {

            if ($conexion->in_transaction) {

                $conexion->rollback();

            }

            // Durante desarrollo podemos registrar el error
            error_log(
                "Error editar_ingreso.php: "
                . $e->getMessage()
            );

            $errores[] =
                "No se pudo actualizar el ingreso. "
                . "No se realizaron cambios en el inventario.";

        }

    }

}


// ======================================================
// OBTENER INGRESO ACTUAL
// ======================================================

$sql = "

    SELECT

        ii.id_ingreso,
        ii.id_proveedor,
        ii.fecha,
        ii.documento,
        ii.referencia,
        ii.total_compra,
        ii.estado,

        di.id_producto,
        di.cantidad,
        di.precio_compra,
        di.subtotal,

        p.nombre AS producto

    FROM ingresos_inventario ii

    INNER JOIN detalle_ingreso di
        ON ii.id_ingreso = di.id_ingreso

    INNER JOIN productos p
        ON di.id_producto = p.id_producto

    WHERE ii.id_ingreso = ?

    LIMIT 1

";

$stmt =
    $conexion->prepare($sql);

$stmt->bind_param(
    "i",
    $id_ingreso
);

$stmt->execute();

$resultado =
    $stmt->get_result();

$ingreso =
    $resultado->fetch_assoc();


if (!$ingreso) {

    header("Location: inventario.php");
    exit();

}


// ======================================================
// OBTENER PROVEEDORES
// ======================================================

$sqlProveedores = "

    SELECT
        id_proveedor,
        nombre

    FROM proveedores

    WHERE estado = 1

    ORDER BY nombre ASC

";

$resultadoProveedores =
    $conexion->query($sqlProveedores);


// ======================================================
// OBTENER PRODUCTOS
// ======================================================

$sqlProductos = "

    SELECT
        id_producto,
        nombre,
        codigo_producto

    FROM productos

    WHERE estado = 1

    ORDER BY nombre ASC

";

$resultadoProductos =
    $conexion->query($sqlProductos);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Ingreso | AGRANDA</title>

    <link
        rel="stylesheet"
        href="inventario.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ==================================================
         MENÚ LATERAL
    =================================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Inventario</p>

        </div>


        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="inventario.php" class="activo">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- ==================================================
         CONTENIDO
    =================================================== -->

    <main class="contenido">


        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>!
                </h2>

                <p>
                    Panel de Administración AGRANDA
                </p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span>

                    <br>

                    <span id="hora"></span>

                </div>


                <div class="usuario">

                    👤
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </div>


                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir">

                    Cerrar sesión

                </a>

            </div>

        </header>


        <!-- ==================================================
             FORMULARIO
        =================================================== -->

        <div class="principal">

            <section class="resumen">

                <h2>
                    ✏️ Editar Ingreso de Inventario
                </h2>

                <p>
                    Modifica los datos del ingreso seleccionado.
                </p>


                <!-- ==========================================
                     ERRORES
                =========================================== -->

                <?php if (!empty($errores)) { ?>

                    <div class="mensaje-error">

                        <?php foreach ($errores as $error) { ?>

                            <p>

                                ❌

                                <?php
                                echo htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </p>

                        <?php } ?>

                    </div>

                <?php } ?>


                <div class="principal">

                    <form method="POST">


                        <!-- PROVEEDOR -->

                        <label>
                            Proveedor
                        </label>

                        <select
                            name="id_proveedor"
                            required>

                            <option value="">
                                Seleccione un proveedor
                            </option>

                            <?php

                            while (
                                $proveedor =
                                $resultadoProveedores
                                ->fetch_assoc()
                            ) {

                                $valorProveedor =
                                    isset(
                                        $_POST["id_proveedor"]
                                    )
                                    ?
                                    (int)$_POST["id_proveedor"]
                                    :
                                    (int)$ingreso[
                                        "id_proveedor"
                                    ];

                                $seleccionado =
                                    $valorProveedor ===
                                    (int)$proveedor[
                                        "id_proveedor"
                                    ];

                            ?>

                                <option
                                    value="<?php
                                    echo $proveedor[
                                        "id_proveedor"
                                    ];
                                    ?>"
                                    <?php
                                    echo $seleccionado
                                        ? "selected"
                                        : "";
                                    ?>>

                                    <?php
                                    echo htmlspecialchars(
                                        $proveedor["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </option>

                            <?php } ?>

                        </select>


                        <!-- FECHA -->

                        <label>
                            Fecha
                        </label>

                        <input
                            type="date"
                            name="fecha"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["fecha"]
                                    ??
                                    $ingreso["fecha"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?>"
                            required>


                        <!-- DOCUMENTO -->

                        <label>
                            Documento / Factura
                        </label>

                        <input
                            type="text"
                            name="documento"
                            id="documento"
                            maxlength="100"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["documento"]
                                    ??
                                    $ingreso["documento"]
                                    ??
                                    "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?>"
                            placeholder="Factura o documento">

                        <div class="contador-caracteres">

                            <span id="contador-documento">
                                0
                            </span>
                            / 100

                        </div>


                        <!-- REFERENCIA -->

                        <label>
                            Referencia
                        </label>

                        <input
                            type="text"
                            name="referencia"
                            id="referencia"
                            maxlength="100"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["referencia"]
                                    ??
                                    $ingreso["referencia"]
                                    ??
                                    "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?>"
                            placeholder="Referencia del ingreso">

                        <div class="contador-caracteres">

                            <span id="contador-referencia">
                                0
                            </span>
                            / 100

                        </div>


                        <!-- PRODUCTO -->

                        <label>
                            Producto
                        </label>

                        <select
                            name="id_producto"
                            required>

                            <option value="">
                                Seleccione un producto
                            </option>

                            <?php

                            while (
                                $producto =
                                $resultadoProductos
                                ->fetch_assoc()
                            ) {

                                $valorProducto =
                                    isset(
                                        $_POST["id_producto"]
                                    )
                                    ?
                                    (int)$_POST["id_producto"]
                                    :
                                    (int)$ingreso[
                                        "id_producto"
                                    ];

                                $seleccionado =
                                    $valorProducto ===
                                    (int)$producto[
                                        "id_producto"
                                    ];

                            ?>

                                <option
                                    value="<?php
                                    echo $producto[
                                        "id_producto"
                                    ];
                                    ?>"
                                    <?php
                                    echo $seleccionado
                                        ? "selected"
                                        : "";
                                    ?>>

                                    <?php
                                    echo htmlspecialchars(
                                        $producto["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                    (

                                    <?php
                                    echo htmlspecialchars(
                                        $producto[
                                            "codigo_producto"
                                        ],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                    )

                                </option>

                            <?php } ?>

                        </select>


                        <!-- CANTIDAD -->

                        <label>
                            Cantidad
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            min="1"
                            max="1000000"
                            step="1"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["cantidad"]
                                    ??
                                    $ingreso["cantidad"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?>"
                            required>


                        <!-- PRECIO -->

                        <label>
                            Precio de compra
                        </label>

                        <input
                            type="number"
                            name="precio_compra"
                            min="0.01"
                            max="999999999.99"
                            step="0.01"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST[
                                        "precio_compra"
                                    ]
                                    ??
                                    $ingreso[
                                        "precio_compra"
                                    ],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?>"
                            required>


                        <!-- OBSERVACIÓN -->

                        <label>
                            Observación
                        </label>

                        <textarea
                            name="observacion"
                            id="observacion"
                            maxlength="500"
                            rows="4"><?php

                                echo htmlspecialchars(
                                    $_POST[
                                        "observacion"
                                    ]
                                    ??
                                    "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            ?></textarea>


                        <div class="contador-caracteres">

                            <span id="contador-observacion">
                                0
                            </span>
                            / 500

                        </div>


                        <br>
                        <br>


                        <!-- BOTONES -->

                        <button
                            type="submit"
                            class="btn-nuevo">💾 Guardar cambios</button>

                        <a
                            href="inventario.php"
                            class="btn-cancelar">Cancelar</a>


                    </form>

                </div>

            </section>

        </div>

    </main>

</div>


<script src="../dashboard/dashboard.js"></script>


<!-- ======================================================
     CONTADORES
======================================================= -->

<script>

function configurarContador(
    campoId,
    contadorId,
    maximo
) {

    const campo =
        document.getElementById(campoId);

    const contador =
        document.getElementById(contadorId);


    if (!campo || !contador) {

        return;

    }


    function actualizarContador() {

        const longitud =
            campo.value.length;

        contador.textContent =
            longitud;


        if (longitud >= maximo) {

            contador.style.color = "red";
            contador.style.fontWeight = "bold";

        } else {

            contador.style.color = "";
            contador.style.fontWeight = "";

        }

    }


    campo.addEventListener(
        "input",
        actualizarContador
    );


    actualizarContador();

}


// Documento
configurarContador(
    "documento",
    "contador-documento",
    100
);


// Referencia
configurarContador(
    "referencia",
    "contador-referencia",
    100
);


// Observación
configurarContador(
    "observacion",
    "contador-observacion",
    500
);

</script>

</body>
</html>