<?php
// El carrito solo modifica cantidades de sesión. No reserva ni escribe inventario.
function agranda_carrito_centavos(string $importe): int
{
    if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', $importe, $partes)) {
        throw new RuntimeException('No fue posible validar el precio de un producto.');
    }
    return (int) $partes[1] * 100 + (int) str_pad($partes[2] ?? '', 2, '0');
}

function agranda_carrito_moneda(int $centavos): string
{
    return '$' . number_format($centavos / 100, 0, ',', '.');
}

function agranda_carrito_estado(mysqli $db, array &$carrito): array
{
    $productos = [];
    $normalizado = $carrito;
    $total = 0;
    $unidades = 0;
    $ajustes = [];
    $puedeContinuar = true;
    $stmt = $db->prepare("SELECT p.id_producto, p.nombre, p.codigo_producto, p.precio,
        p.estado AS producto_estado, c.nombre AS categoria, c.estado AS categoria_estado,
        m.nombre AS marca, COALESCE(i.stock_actual, 0) AS stock,
        (SELECT img.ruta_imagen FROM imagenes_producto img
         WHERE img.id_producto = p.id_producto AND img.principal = 1 AND img.estado = 1
         ORDER BY img.id_imagen LIMIT 1) AS imagen
        FROM productos p INNER JOIN categorias c ON c.id_categoria = p.id_categoria
        LEFT JOIN marcas m ON m.id_marca = p.id_marca
        LEFT JOIN inventario i ON i.id_producto = p.id_producto
        WHERE p.id_producto = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('No fue posible consultar el carrito.');
    }
    try {
        foreach ($carrito as $clave => $item) {
            $id = filter_var($clave, FILTER_VALIDATE_INT);
            $cantidad = is_array($item)
                ? filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT) : false;
            if (!$id || $id < 1 || !$cantidad || $cantidad < 1 || $cantidad > 1000000) {
                unset($normalizado[$clave]);
                continue;
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new RuntimeException('No fue posible consultar el carrito.');
            }
            $fila = $stmt->get_result()->fetch_assoc();
            // Una referencia eliminada tampoco desaparece silenciosamente.
            $fila = $fila ?: ['nombre' => 'Producto no disponible', 'codigo_producto' => '',
                'precio' => '0', 'producto_estado' => 0, 'categoria_estado' => 0,
                'categoria' => '', 'marca' => '', 'stock' => 0, 'imagen' => null];
            $stock = max(0, (int) $fila['stock']);
            $disponible = (int) $fila['producto_estado'] === 1
                && (int) $fila['categoria_estado'] === 1 && $stock > 0;
            $cantidadValida = $disponible ? min($cantidad, $stock) : 0;
            $ajustado = $disponible && $cantidadValida !== $cantidad;
            if ($ajustado) {
                $normalizado[$clave]['cantidad'] = $cantidadValida;
                $ajustes[] = $id;
            }
            $precio = agranda_carrito_centavos((string) $fila['precio']);
            $subtotal = $precio * $cantidadValida;
            $total += $subtotal;
            $unidades += $cantidadValida;
            $puedeContinuar = $puedeContinuar && $disponible;
            $visible = $disponible ? $cantidadValida : $cantidad;
            $productos[] = [
                'id_producto' => $id, 'nombre' => $fila['nombre'],
                'codigo_producto' => $fila['codigo_producto'], 'categoria' => $fila['categoria'],
                'marca' => $fila['marca'], 'imagen' => $fila['imagen'],
                'cantidad_solicitada' => $cantidad, 'cantidad' => $visible,
                'cantidad_valida' => $cantidadValida, 'stock' => $stock,
                'disponible' => $disponible, 'ajustado' => $ajustado,
                'precio_formateado' => agranda_carrito_moneda($precio),
                'subtotal_formateado' => agranda_carrito_moneda($subtotal),
                'precio_centavos' => $precio, 'subtotal_centavos' => $subtotal,
                'puede_sumar' => $disponible && $visible < $stock,
                'puede_restar' => $disponible && $visible > 1,
                'disponibilidad_texto' => !$disponible
                    ? ($stock === 0 ? 'Sin stock' : 'Producto no disponible')
                    : $stock . ' disponible' . ($stock === 1 ? '' : 's'),
                'mensaje' => $ajustado ? 'La cantidad de este producto fue ajustada al stock disponible.'
                    : ($disponible && $visible === $stock ? 'Cantidad máxima disponible alcanzada.' : '')
            ];
        }
    } finally {
        $stmt->close();
    }
    // Aplicar ajustes solo cuando se logró consultar y calcular todo el estado.
    $carrito = $normalizado;
    return ['productos' => $productos, 'total_centavos' => $total,
        'total_formateado' => agranda_carrito_moneda($total),
        'cantidad_carrito' => $unidades,
        'unidades_texto' => $unidades . ' unidad' . ($unidades === 1 ? '' : 'es'),
        'contador_texto' => $unidades . ' producto' . ($unidades === 1 ? '' : 's'),
        'puede_continuar' => $puedeContinuar && $unidades > 0,
        'bloqueo_texto' => !$puedeContinuar
            ? 'Retira los productos sin stock o no disponibles para continuar.' : '',
        'ajustes' => $ajustes];
}

function agranda_carrito_cambiar(mysqli $db, array &$carrito, int $id, string $accion): array
{
    $estado = agranda_carrito_estado($db, $carrito);
    $producto = null;
    foreach ($estado['productos'] as $fila) {
        if ($fila['id_producto'] === $id) { $producto = $fila; break; }
    }
    $mensaje = '';
    $codigo = 200;
    if (!$producto) {
        $mensaje = 'El producto ya no está en tu carrito.';
        $codigo = 404;
    } elseif ($producto['ajustado']) {
        // No sumar/restar además de corregir una reducción externa del stock.
        $mensaje = 'La cantidad de este producto fue ajustada al stock disponible.';
    } elseif (!$producto['disponible']) {
        $mensaje = 'Este producto no está disponible. Retíralo del carrito para continuar.';
        $codigo = 409;
    } elseif ($accion === 'sumar' && !$producto['puede_sumar']) {
        $mensaje = 'No puedes agregar más unidades. Alcanzaste el stock disponible.';
        $codigo = 409;
    } elseif ($accion === 'restar' && !$producto['puede_restar']) {
        $mensaje = 'La cantidad mínima es una unidad.';
    } else {
        $carrito[$id]['cantidad'] = $producto['cantidad'] + ($accion === 'sumar' ? 1 : -1);
        $ajustes = $estado['ajustes'];
        $estado = agranda_carrito_estado($db, $carrito);
        $estado['ajustes'] = array_values(array_unique(array_merge($ajustes, $estado['ajustes'])));
    }
    foreach ($estado['productos'] as &$fila) {
        if (in_array($fila['id_producto'], $estado['ajustes'], true)) {
            $fila['mensaje'] = 'La cantidad de este producto fue ajustada al stock disponible.';
        }
    }
    unset($fila);
    return ['success' => $codigo === 200, 'message' => $mensaje, 'estado' => $estado, 'codigo' => $codigo];
}
