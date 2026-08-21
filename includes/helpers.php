<?php
// helpers.php - Funciones auxiliares para la tienda AGRANDA

/**
 * Resuelve la ruta de una imagen comprobando si existe en el sistema de archivos
 * o devolviendo una imagen de respaldo (fallback).
 */
function obtenerRutaImagen($ruta, $tipo = 'producto') {
    if (empty($ruta)) {
        return $tipo === 'categoria' ? 'destacado 1.jfif' : 'destacado 1.jfif';
    }

    // Limpiar posibles barras iniciales o prefijos relativos
    $rutaLimpia = preg_replace('/^(\.\.\/|\.\/|\/)/', '', $ruta);

    // 1. Comprobar si el archivo físico existe en la ruta relativa directa
    if (file_exists(__DIR__ . '/../' . $rutaLimpia)) {
        return $rutaLimpia;
    }

    // 2. Comprobar en /uploads/
    if (file_exists(__DIR__ . '/../uploads/' . $rutaLimpia)) {
        return 'uploads/' . $rutaLimpia;
    }

    // 3. Comprobar en /uploads/productos/
    if (file_exists(__DIR__ . '/../uploads/productos/' . basename($rutaLimpia))) {
        return 'uploads/productos/' . basename($rutaLimpia);
    }

    // 4. Comprobar en /uploads/categorias/
    if (file_exists(__DIR__ . '/../uploads/categorias/' . basename($rutaLimpia))) {
        return 'uploads/categorias/' . basename($rutaLimpia);
    }

    // 5. Si es un archivo base en la raíz (ej. destacado 1.jfif, destacado2.webp)
    if (file_exists(__DIR__ . '/../' . basename($rutaLimpia))) {
        return basename($rutaLimpia);
    }

    return 'destacado 1.jfif';
}

/**
 * Formatea un número como moneda (ej: $ 150.000)
 */
function formatearPrecio($monto, $moneda = '$') {
    return $moneda . ' ' . number_format((float)$monto, 0, ',', '.');
}

/**
 * Obtiene la configuración general de la tienda
 */
function obtenerConfiguracionTienda($conexion) {
    $config = [
        'nombre_tienda' => 'AGRANDA',
        'eslogan' => 'Repuestos y Productos Agrícolas de Calidad',
        'correo_contacto' => 'contacto@agranda.com',
        'telefono_contacto' => '+57 300 000 0000',
        'direccion' => 'Calle Principal # 10-20, Zona Agrícola',
        'logo' => '',
        'moneda' => '$'
    ];

    if ($conexion && !$conexion->connect_error) {
        $resultado = $conexion->query("SELECT * FROM configuracion_tienda LIMIT 1");
        if ($resultado && $fila = $resultado->fetch_assoc()) {
            foreach ($fila as $clave => $valor) {
                if (!empty($valor)) {
                    $config[$clave] = $valor;
                }
            }
        }
    }

    return $config;
}

/**
 * Obtiene el detalle completo de un producto por su ID
 */
function obtenerProductoPorId($conexion, $id_producto) {
    $id = (int)$id_producto;
    if ($id <= 0 || !$conexion) return null;

    $sql = "SELECT 
                p.id_producto,
                p.id_categoria,
                p.id_marca,
                p.nombre,
                p.descripcion,
                p.codigo_producto,
                p.precio,
                p.peso,
                p.estado,
                p.destacado,
                c.nombre AS categoria,
                COALESCE(m.nombre, 'Sin Marca') AS marca,
                COALESCE(i.stock_actual, 0) AS stock_actual,
                COALESCE(i.stock_minimo, 0) AS stock_minimo,
                img.ruta_imagen AS imagen_principal,
                pr.id_promocion,
                pr.nombre AS nombre_promocion,
                pr.tipo AS tipo_promocion,
                pr.valor_descuento,
                CASE 
                    WHEN pr.tipo = 'Porcentaje' THEN ROUND(p.precio - (p.precio * (pr.valor_descuento / 100)), 2)
                    WHEN pr.tipo = 'Fijo' THEN GREATEST(0, p.precio - pr.valor_descuento)
                    ELSE p.precio
                END AS precio_final
            FROM productos p
            INNER JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN marcas m ON p.id_marca = m.id_marca
            LEFT JOIN inventario i ON p.id_producto = i.id_producto
            LEFT JOIN imagenes_producto img ON p.id_producto = img.id_producto AND img.principal = 1 AND img.estado = 1
            LEFT JOIN promociones pr ON pr.id_promocion = (
                SELECT pp2.id_promocion
                FROM promocion_producto pp2
                INNER JOIN promociones pr2 ON pp2.id_promocion = pr2.id_promocion
                WHERE pp2.id_producto = p.id_producto
                  AND pr2.estado = 1
                  AND CURDATE() BETWEEN pr2.fecha_inicio AND pr2.fecha_fin
                LIMIT 1
            )
            WHERE p.id_producto = ? AND p.estado = 1
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

/**
 * Obtiene la galería de imágenes de un producto
 */
function obtenerGaleriaProducto($conexion, $id_producto) {
    $id = (int)$id_producto;
    $imagenes = [];
    if ($id <= 0 || !$conexion) return $imagenes;

    $sql = "SELECT id_imagen, ruta_imagen, principal, orden 
            FROM imagenes_producto 
            WHERE id_producto = ? AND estado = 1 
            ORDER BY principal DESC, orden ASC, id_imagen ASC";

    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $imagenes[] = [
                'id_imagen' => $row['id_imagen'],
                'ruta_imagen' => obtenerRutaImagen($row['ruta_imagen'], 'producto'),
                'principal' => (int)$row['principal'],
                'orden' => (int)$row['orden']
            ];
        }
    }
    return $imagenes;
}

/**
 * Obtiene la lista de compatibilidades de un producto con maquinaria
 * Relación: compatibilidades -> modelos -> marcas
 */
function obtenerCompatibilidadesProducto($conexion, $id_producto) {
    $id = (int)$id_producto;
    $compatibilidades = [];
    if ($id <= 0 || !$conexion) return $compatibilidades;

    $sql = "SELECT 
                m.nombre AS marca,
                mo.nombre AS modelo,
                mo.anio_inicio,
                mo.anio_fin,
                mo.descripcion AS descripcion_modelo,
                comp.observaciones
            FROM compatibilidades comp
            INNER JOIN modelos mo ON comp.id_modelo = mo.id_modelo
            INNER JOIN marcas m ON mo.id_marca = m.id_marca
            WHERE comp.id_producto = ? AND comp.estado = 1 AND mo.estado = 1 AND m.estado = 1
            ORDER BY m.nombre ASC, mo.nombre ASC";

    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $compatibilidades[] = $row;
        }
    }
    return $compatibilidades;
}

/**
 * Valida y calcula el carrito de compras contra la base de datos (servidor)
 */
function calcularCarritoServidor($conexion, $itemsEnSesion, $codigoCupon = null) {
    $itemsValidados = [];
    $subtotal = 0;
    $totalDescuentoPromociones = 0;
    $mensajesAjuste = [];

    if (empty($itemsEnSesion) || !is_array($itemsEnSesion)) {
        return [
            'items' => [],
            'subtotal_base' => 0,
            'subtotal' => 0,
            'descuento_promociones' => 0,
            'descuento_cupon' => 0,
            'total' => 0,
            'cupon_aplicado' => null,
            'mensajes' => []
        ];
    }

    foreach ($itemsEnSesion as $item) {
        $idProducto = (int)($item['id'] ?? $item['id_producto'] ?? 0);
        $cantidadPedida = (int)($item['cantidad'] ?? 1);
        if ($idProducto <= 0 || $cantidadPedida <= 0) continue;

        $prodBD = obtenerProductoPorId($conexion, $idProducto);
        if (!$prodBD) {
            $mensajesAjuste[] = "Un producto solicitado ya no está disponible y fue retirado de tu carrito.";
            continue;
        }

        $stockActual = (int)$prodBD['stock_actual'];
        if ($stockActual <= 0) {
            $mensajesAjuste[] = "El producto \"" . $prodBD['nombre'] . "\" se encuentra agotado.";
            continue;
        }

        $cantidadAceptada = min($cantidadPedida, $stockActual);
        if ($cantidadAceptada < $cantidadPedida) {
            $mensajesAjuste[] = "La cantidad de \"" . $prodBD['nombre'] . "\" se ajustó a " . $cantidadAceptada . " por límite de inventario.";
        }

        $precioOriginal = (float)$prodBD['precio'];
        $precioFinal = (float)$prodBD['precio_final'];
        $subtotalItem = $precioFinal * $cantidadAceptada;
        $descuentoItem = ($precioOriginal - $precioFinal) * $cantidadAceptada;

        $subtotal += $precioOriginal * $cantidadAceptada;
        $totalDescuentoPromociones += $descuentoItem;

        $itemsValidados[] = [
            'id_producto' => $prodBD['id_producto'],
            'nombre' => $prodBD['nombre'],
            'codigo_producto' => $prodBD['codigo_producto'],
            'categoria' => $prodBD['categoria'],
            'marca' => $prodBD['marca'],
            'imagen' => obtenerRutaImagen($prodBD['imagen_principal'], 'producto'),
            'precio_original' => $precioOriginal,
            'precio_unitario' => $precioFinal,
            'tiene_promo' => !empty($prodBD['id_promocion']) && $precioFinal < $precioOriginal,
            'nombre_promo' => $prodBD['nombre_promocion'],
            'cantidad' => $cantidadAceptada,
            'stock_disponible' => $stockActual,
            'subtotal' => $subtotalItem
        ];
    }

    $subtotalConPromos = $subtotal - $totalDescuentoPromociones;
    $descuentoCupon = 0;
    $infoCupon = null;

    // Validación de cupón en la tabla `cupones`
    if (!empty($codigoCupon) && $conexion) {
        $codigoLimpio = trim($codigoCupon);
        $sqlCupon = "SELECT id_cupon, codigo, descripcion, tipo_descuento, valor_descuento, 
                            fecha_inicio, fecha_fin, uso_maximo, uso_actual, monto_minimo_compra 
                     FROM cupones 
                     WHERE codigo = ? AND estado = 1 
                       AND CURDATE() BETWEEN fecha_inicio AND fecha_fin 
                     LIMIT 1";
        $stmtC = $conexion->prepare($sqlCupon);
        if ($stmtC) {
            $stmtC->bind_param("s", $codigoLimpio);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            if ($cup = $resC->fetch_assoc()) {
                // Validar uso máximo
                if ($cup['uso_maximo'] !== null && $cup['uso_actual'] >= $cup['uso_maximo']) {
                    $mensajesAjuste[] = "El cupón \"" . $codigoLimpio . "\" ha alcanzado el límite máximo de usos.";
                } elseif ($subtotalConPromos < (float)$cup['monto_minimo_compra']) {
                    $mensajesAjuste[] = "El cupón \"" . $codigoLimpio . "\" requiere una compra mínima de " . formatearPrecio($cup['monto_minimo_compra']) . ".";
                } else {
                    // Aplicar descuento
                    if ($cup['tipo_descuento'] === 'Porcentaje') {
                        $descuentoCupon = round($subtotalConPromos * ((float)$cup['valor_descuento'] / 100), 2);
                    } else {
                        $descuentoCupon = min($subtotalConPromos, (float)$cup['valor_descuento']);
                    }
                    $infoCupon = [
                        'id_cupon' => $cup['id_cupon'],
                        'codigo' => $cup['codigo'],
                        'tipo_descuento' => $cup['tipo_descuento'],
                        'valor_descuento' => (float)$cup['valor_descuento'],
                        'monto_descontado' => $descuentoCupon
                    ];
                }
            } else {
                $mensajesAjuste[] = "El cupón ingresado no es válido o ha vencido.";
            }
        }
    }

    $totalFinal = max(0, $subtotalConPromos - $descuentoCupon);

    return [
        'items' => $itemsValidados,
        'subtotal_base' => $subtotal,
        'descuento_promociones' => $totalDescuentoPromociones,
        'subtotal' => $subtotalConPromos,
        'descuento_cupon' => $descuentoCupon,
        'total' => $totalFinal,
        'cupon_aplicado' => $infoCupon,
        'mensajes' => $mensajesAjuste
    ];
}
?>
