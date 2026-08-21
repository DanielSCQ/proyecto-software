<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// VERIFICAR ACCIÓN
// =================================

if (!isset($_POST["accion"])) {
    header("Location: promociones.php");
    exit();
}

$accion = $_POST["accion"];


// =================================
// AGREGAR PROMOCIÓN
// =================================

if ($accion === "agregar") {

    // =================================
    // RECIBIR DATOS
    // =================================

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $valor_descuento = $_POST["valor_descuento"] ?? "";

    $aplicar_a = $_POST["aplica_a"] ?? "";

    // AHORA SON ARRAYS
    $id_productos = $_POST["id_producto"] ?? [];
    $id_categorias = $_POST["id_categoria"] ?? [];

    $fecha_inicio = $_POST["fecha_inicio"] ?? "";
    $fecha_fin = $_POST["fecha_fin"] ?? "";

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // =================================
    // ASEGURAR QUE SEAN ARRAYS
    // =================================

    if (!is_array($id_productos)) {
        $id_productos = [$id_productos];
    }

    if (!is_array($id_categorias)) {
        $id_categorias = [$id_categorias];
    }


    // =================================
    // VALIDAR CAMPOS OBLIGATORIOS
    // =================================

    if (
        empty($nombre) ||
        empty($tipo) ||
        $valor_descuento === "" ||
        empty($aplicar_a) ||
        empty($fecha_inicio) ||
        empty($fecha_fin)
    ) {

        die("Error: complete todos los campos obligatorios.");
    }


    // =================================
    // VALIDAR DESCUENTO
    // =================================

    if (!is_numeric($valor_descuento) || $valor_descuento < 0) {

        die("Error: el valor del descuento no es válido.");
    }

    $valor_descuento = (float) $valor_descuento;


    // =================================
    // VALIDAR TIPO
    // =================================

    if (
        $tipo !== "Porcentaje" &&
        $tipo !== "Fijo"
    ) {

        die("Error: el tipo de descuento no es válido.");
    }


    // =================================
    // VALIDAR PORCENTAJE
    // =================================

    if (
        $tipo === "Porcentaje" &&
        $valor_descuento > 100
    ) {

        die(
            "Error: el descuento porcentual no puede ser mayor al 100%."
        );
    }


    // =================================
    // VALIDAR FECHAS
    // =================================

    if ($fecha_fin < $fecha_inicio) {

        die(
            "Error: la fecha de finalización no puede ser anterior a la fecha de inicio."
        );
    }


    // =================================
    // VALIDAR APLICACIÓN
    // =================================

    if (
        $aplicar_a !== "producto" &&
        $aplicar_a !== "categoria"
    ) {

        die(
            "Error: la opción seleccionada para aplicar la promoción no es válida."
        );
    }


    // =================================
    // INICIAR TRANSACCIÓN
    // =================================

    $conexion->begin_transaction();


    try {

        // =================================
        // INSERTAR PROMOCIÓN
        // =================================

        $sqlPromocion = "INSERT INTO promociones
                        (
                            nombre,
                            descripcion,
                            tipo,
                            valor_descuento,
                            fecha_inicio,
                            fecha_fin,
                            estado
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sqlPromocion);

        if (!$stmt) {
            throw new Exception(
                "Error al preparar la promoción."
            );
        }

        $stmt->bind_param(
            "sssdssi",
            $nombre,
            $descripcion,
            $tipo,
            $valor_descuento,
            $fecha_inicio,
            $fecha_fin,
            $estado
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Error al guardar la promoción."
            );
        }

        // ID de la promoción recién creada
        $id_promocion = $conexion->insert_id;

        $stmt->close();


        // =================================
        // APLICAR A PRODUCTOS
        // =================================

        if ($aplicar_a === "producto") {

            // Eliminar valores vacíos
            $id_productos = array_filter(
                $id_productos,
                function ($id) {
                    return $id !== "" && $id !== null;
                }
            );


            // Verificar selección
            if (empty($id_productos)) {

                throw new Exception(
                    "Debe seleccionar al menos un producto."
                );
            }


            // Consulta para verificar producto
            $sqlProducto = "SELECT id_producto
                            FROM productos
                            WHERE id_producto = ?
                            AND estado = 1";

            $stmtProducto = $conexion->prepare(
                $sqlProducto
            );


            // Insertar relaciones
            $sqlRelacion = "INSERT INTO promocion_producto
                            (
                                id_promocion,
                                id_producto
                            )
                            VALUES (?, ?)";

            $stmtRelacion = $conexion->prepare(
                $sqlRelacion
            );


            foreach ($id_productos as $id_producto) {

                $id_producto = (int) $id_producto;


                // Verificar que exista y esté activo
                $stmtProducto->bind_param(
                    "i",
                    $id_producto
                );

                $stmtProducto->execute();

                $resultadoProducto =
                    $stmtProducto->get_result();


                if ($resultadoProducto->num_rows === 0) {

                    throw new Exception(
                        "Uno de los productos seleccionados no existe o está inactivo."
                    );
                }


                // Registrar relación
                $stmtRelacion->bind_param(
                    "ii",
                    $id_promocion,
                    $id_producto
                );

                if (!$stmtRelacion->execute()) {

                    throw new Exception(
                        "Error al relacionar un producto con la promoción."
                    );
                }
            }


            $stmtProducto->close();
            $stmtRelacion->close();
        }


        // =================================
        // APLICAR A CATEGORÍAS
        // =================================

        elseif ($aplicar_a === "categoria") {

            // Eliminar valores vacíos
            $id_categorias = array_filter(
                $id_categorias,
                function ($id) {
                    return $id !== "" && $id !== null;
                }
            );


            // Verificar selección
            if (empty($id_categorias)) {

                throw new Exception(
                    "Debe seleccionar al menos una categoría."
                );
            }


            // Consulta de productos
            $sqlProductos = "SELECT id_producto
                             FROM productos
                             WHERE id_categoria = ?
                             AND estado = 1";

            $stmtProductos = $conexion->prepare(
                $sqlProductos
            );


            // Consulta para insertar relación
            $sqlRelacion = "INSERT INTO promocion_producto
                            (
                                id_promocion,
                                id_producto
                            )
                            VALUES (?, ?)";

            $stmtRelacion = $conexion->prepare(
                $sqlRelacion
            );


            $cantidadProductos = 0;


            // Recorrer categorías seleccionadas
            foreach ($id_categorias as $id_categoria) {

                $id_categoria = (int) $id_categoria;


                // Buscar productos de la categoría
                $stmtProductos->bind_param(
                    "i",
                    $id_categoria
                );

                $stmtProductos->execute();

                $resultadoProductos =
                    $stmtProductos->get_result();


                // Recorrer productos
                while (
                    $producto =
                    $resultadoProductos->fetch_assoc()
                ) {

                    $id_producto =
                        (int) $producto["id_producto"];


                    // Registrar producto
                    $stmtRelacion->bind_param(
                        "ii",
                        $id_promocion,
                        $id_producto
                    );

                    if (!$stmtRelacion->execute()) {

                        throw new Exception(
                            "Error al relacionar los productos con la promoción."
                        );
                    }

                    $cantidadProductos++;
                }
            }


            $stmtProductos->close();
            $stmtRelacion->close();


            // Ninguna categoría tenía productos
            if ($cantidadProductos === 0) {

                throw new Exception(
                    "Las categorías seleccionadas no tienen productos activos."
                );
            }
        }


        // =================================
        // CONFIRMAR OPERACIÓN
        // =================================

        $conexion->commit();


        // =================================
        // VOLVER A PROMOCIONES
        // =================================

        header(
            "Location: promociones.php"
        );

        exit();


    } catch (Exception $e) {

        // =================================
        // DESHACER CAMBIOS
        // =================================

        $conexion->rollback();


        echo "
        <div style='
            font-family: Arial;
            padding: 30px;
        '>

            <h2>Error al guardar la promoción</h2>

            <p>"
            . htmlspecialchars($e->getMessage())
            . "</p>

            <a href='agregar_promocion.php'>
                Volver al formulario
            </a>

        </div>";
    }

}


// =================================
// EDITAR PROMOCIÓN
// =================================

elseif ($accion === "editar") {

    // =================================
    // RECIBIR DATOS
    // =================================

    $id_promocion = intval($_POST["id_promocion"] ?? 0);

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $valor_descuento = $_POST["valor_descuento"] ?? "";

    $id_productos = $_POST["id_producto"] ?? [];

    $fecha_inicio = $_POST["fecha_inicio"] ?? "";
    $fecha_fin = $_POST["fecha_fin"] ?? "";

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // =================================
    // ASEGURAR QUE PRODUCTOS SEA ARRAY
    // =================================

    if (!is_array($id_productos)) {
        $id_productos = [$id_productos];
    }


    // =================================
    // VALIDAR ID
    // =================================

    if ($id_promocion <= 0) {

        die("Error: la promoción no es válida.");
    }


    // =================================
    // VALIDAR CAMPOS
    // =================================

    if (
        empty($nombre) ||
        empty($tipo) ||
        $valor_descuento === "" ||
        empty($fecha_inicio) ||
        empty($fecha_fin)
    ) {

        die("Error: complete todos los campos obligatorios.");
    }


    // =================================
    // VALIDAR DESCUENTO
    // =================================

    if (
        !is_numeric($valor_descuento) ||
        $valor_descuento < 0
    ) {

        die("Error: el valor del descuento no es válido.");
    }

    $valor_descuento = (float) $valor_descuento;


    // =================================
    // VALIDAR TIPO
    // =================================

    if (
        $tipo !== "Porcentaje" &&
        $tipo !== "Fijo"
    ) {

        die("Error: el tipo de descuento no es válido.");
    }


    // =================================
    // VALIDAR PORCENTAJE
    // =================================

    if (
        $tipo === "Porcentaje" &&
        $valor_descuento > 100
    ) {

        die(
            "Error: el descuento porcentual no puede ser mayor al 100%."
        );
    }


    // =================================
    // VALIDAR FECHAS
    // =================================

    if ($fecha_fin < $fecha_inicio) {

        die(
            "Error: la fecha de finalización no puede ser anterior a la fecha de inicio."
        );
    }


    // =================================
    // LIMPIAR PRODUCTOS
    // =================================

    $id_productos = array_filter(
        $id_productos,
        function ($id) {
            return $id !== "" && $id !== null;
        }
    );


    // =================================
    // VERIFICAR PRODUCTOS
    // =================================

    if (empty($id_productos)) {

        die(
            "Error: debe seleccionar al menos un producto."
        );
    }


    // =================================
    // INICIAR TRANSACCIÓN
    // =================================

    $conexion->begin_transaction();


    try {

        // =================================
        // ACTUALIZAR PROMOCIÓN
        // =================================

        $sqlActualizar = "UPDATE promociones
                          SET
                              nombre = ?,
                              descripcion = ?,
                              tipo = ?,
                              valor_descuento = ?,
                              fecha_inicio = ?,
                              fecha_fin = ?,
                              estado = ?
                          WHERE id_promocion = ?";

        $stmtActualizar = $conexion->prepare(
            $sqlActualizar
        );


        if (!$stmtActualizar) {

            throw new Exception(
                "Error al preparar la actualización."
            );
        }


        $stmtActualizar->bind_param(
            "sssdssii",
            $nombre,
            $descripcion,
            $tipo,
            $valor_descuento,
            $fecha_inicio,
            $fecha_fin,
            $estado,
            $id_promocion
        );


        if (!$stmtActualizar->execute()) {

            throw new Exception(
                "Error al actualizar la promoción."
            );
        }


        $stmtActualizar->close();


        // =================================
        // ELIMINAR RELACIONES ANTERIORES
        // =================================

        $sqlEliminar = "DELETE FROM promocion_producto
                        WHERE id_promocion = ?";

        $stmtEliminar = $conexion->prepare(
            $sqlEliminar
        );


        if (!$stmtEliminar) {

            throw new Exception(
                "Error al preparar las relaciones."
            );
        }


        $stmtEliminar->bind_param(
            "i",
            $id_promocion
        );


        if (!$stmtEliminar->execute()) {

            throw new Exception(
                "Error al actualizar los productos de la promoción."
            );
        }


        $stmtEliminar->close();


        // =================================
        // VERIFICAR PRODUCTOS
        // =================================

        $sqlProducto = "SELECT id_producto
                        FROM productos
                        WHERE id_producto = ?
                        AND estado = 1";

        $stmtProducto = $conexion->prepare(
            $sqlProducto
        );


        if (!$stmtProducto) {

            throw new Exception(
                "Error al verificar los productos."
            );
        }


        // =================================
        // INSERTAR NUEVAS RELACIONES
        // =================================

        $sqlRelacion = "INSERT INTO promocion_producto
                        (
                            id_promocion,
                            id_producto
                        )
                        VALUES (?, ?)";

        $stmtRelacion = $conexion->prepare(
            $sqlRelacion
        );


        if (!$stmtRelacion) {

            throw new Exception(
                "Error al preparar las relaciones."
            );
        }


        foreach ($id_productos as $id_producto) {

            $id_producto = (int) $id_producto;


            // ---------------------------------
            // VERIFICAR PRODUCTO
            // ---------------------------------

            $stmtProducto->bind_param(
                "i",
                $id_producto
            );

            $stmtProducto->execute();

            $resultadoProducto =
                $stmtProducto->get_result();


            if ($resultadoProducto->num_rows === 0) {

                throw new Exception(
                    "Uno de los productos seleccionados no existe o está inactivo."
                );
            }


            // ---------------------------------
            // GUARDAR RELACIÓN
            // ---------------------------------

            $stmtRelacion->bind_param(
                "ii",
                $id_promocion,
                $id_producto
            );


            if (!$stmtRelacion->execute()) {

                throw new Exception(
                    "Error al relacionar el producto con la promoción."
                );
            }
        }


        $stmtProducto->close();
        $stmtRelacion->close();


        // =================================
        // CONFIRMAR CAMBIOS
        // =================================

        $conexion->commit();


        // =================================
        // VOLVER A PROMOCIONES
        // =================================

        header(
            "Location: promociones.php"
        );

        exit();


    } catch (Exception $e) {

        // =================================
        // DESHACER CAMBIOS
        // =================================

        $conexion->rollback();


        echo "
        <div style='
            font-family: Arial;
            padding: 30px;
        '>

            <h2>Error al editar la promoción</h2>

            <p>"
            . htmlspecialchars($e->getMessage())
            . "</p>

            <a href='promociones.php'>
                Volver a promociones
            </a>

        </div>";
    }

}


// =================================
// ACCIÓN NO VÁLIDA
// =================================

else {

    header(
        "Location: promociones.php"
    );

    exit();
}

?>