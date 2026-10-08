<?php
/**
 * Reglas compartidas de reseñas, sin HTML ni escritura económica.
 * Los controladores deben obtener el usuario de sesión, exigir POST y CSRF
 * para escribir y presentar el texto escapado. Configurar mysqli en utf8mb4.
 *
 * Fase 2: BEGIN -> compra_entregada(..., true) -> existente -> INSERT con
 * pedido obtenido aquí, compra_verificada=1 y estado=1 -> COMMIT.
 * FOR UPDATE requiere una transacción abierta en la misma conexión.
 * Ante error 1062: ROLLBACK, recuperar la existente y comunicar duplicado;
 * nunca usar UPSERT para sustituir contenido silenciosamente.
 * Edición: comprobar ownership y alguna compra actualmente Entregada;
 * conservar id_pedido original, compra_verificada y estado, y actualizar
 * fecha_actualizacion explícitamente solo al cambiar contenido/calificación.
 * Un cambio posterior de estado del pedido no revoca la evidencia histórica.
 */

function agranda_resena_id(int $id): void
{
    if ($id < 1) { throw new InvalidArgumentException('El identificador no es válido.'); }
}

function agranda_resena_preparar(mysqli $db, string $sql): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if (!$stmt) { throw new RuntimeException('No fue posible consultar las reseñas.'); }
    return $stmt;
}

function agranda_resena_ejecutar(mysqli_stmt $stmt): mysqli_result
{
    if (!$stmt->execute()) { throw new RuntimeException('No fue posible consultar las reseñas.'); }
    $resultado = $stmt->get_result();
    if (!$resultado) { throw new RuntimeException('No fue posible consultar las reseñas.'); }
    return $resultado;
}

function agranda_resena_validar_contenido($calificacion, $comentario): array
{
    if ((!is_int($calificacion) && !is_string($calificacion))
        || !preg_match('/^[1-5]$/D', (string) $calificacion)) {
        throw new InvalidArgumentException('Selecciona una calificación entera de 1 a 5.');
    }
    if (!is_string($comentario) || !mb_check_encoding($comentario, 'UTF-8')) {
        throw new InvalidArgumentException('El comentario no es válido.');
    }
    $texto = preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $comentario);
    if ($texto === null || mb_strlen($texto, 'UTF-8') < 1 || mb_strlen($texto, 'UTF-8') > 150) {
        throw new InvalidArgumentException('Escribe un comentario de 1 a 150 caracteres.');
    }
    // Mantener texto sin interpretar ni escapar HTML; eso corresponde a la vista.
    return ['calificacion' => (int) $calificacion, 'comentario' => $texto];
}

function agranda_resena_compra_entregada(mysqli $db, int $usuario, int $producto, bool $bloquear = false): ?int
{
    agranda_resena_id($usuario); agranda_resena_id($producto);
    $stmt = agranda_resena_preparar($db, "SELECT p.id_pedido
        FROM pedidos p
        INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
        INNER JOIN estado_pedido e ON e.id_estado = p.id_estado
        INNER JOIN detalle_pedido d ON d.id_pedido = p.id_pedido
        INNER JOIN productos pr ON pr.id_producto = d.id_producto
        WHERE p.id_usuario = ? AND d.id_producto = ? AND d.cantidad > 0
          AND u.estado = 1 AND u.rol IN ('cliente', 'administrador')
          AND BINARY e.nombre = 'Entregado'
        ORDER BY p.fecha_pedido DESC, p.id_pedido DESC LIMIT 1" . ($bloquear ? ' FOR UPDATE' : ''));
    try {
        $stmt->bind_param('ii', $usuario, $producto);
        $fila = agranda_resena_ejecutar($stmt)->fetch_assoc();
        return $fila ? (int) $fila['id_pedido'] : null;
    } finally { $stmt->close(); }
}

function agranda_resena_existente(mysqli $db, int $usuario, int $producto, bool $bloquear = false): ?array
{
    agranda_resena_id($usuario); agranda_resena_id($producto);
    $stmt = agranda_resena_preparar($db, 'SELECT * FROM resenas WHERE id_usuario = ? AND id_producto = ? LIMIT 1' . ($bloquear ? ' FOR UPDATE' : ''));
    try {
        $stmt->bind_param('ii', $usuario, $producto);
        return agranda_resena_ejecutar($stmt)->fetch_assoc() ?: null;
    } finally { $stmt->close(); }
}

function agranda_resena_usuario_activo(mysqli $db, int $usuario): bool
{
    $stmt = agranda_resena_preparar($db, "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND estado = 1 AND rol IN ('cliente', 'administrador')");
    try {
        $stmt->bind_param('i', $usuario);
        return (bool) agranda_resena_ejecutar($stmt)->fetch_assoc();
    } finally { $stmt->close(); }
}

function agranda_resena_producto(mysqli $db, int $producto): ?array
{
    agranda_resena_id($producto);
    $stmt = agranda_resena_preparar($db, 'SELECT nombre, codigo_producto FROM productos WHERE id_producto = ?');
    try {
        $stmt->bind_param('i', $producto);
        return agranda_resena_ejecutar($stmt)->fetch_assoc() ?: null;
    } finally { $stmt->close(); }
}

/** La transacción conserva el bloqueo de la compra hasta terminar la escritura. */
function agranda_resena_guardar(mysqli $db, int $usuario, int $producto, $calificacion, $comentario, string $modo, ?int $idResena = null): string
{
    agranda_resena_id($usuario); agranda_resena_id($producto);
    $contenido = agranda_resena_validar_contenido($calificacion, $comentario);
    if (!in_array($modo, ['crear', 'editar'], true)) {
        throw new InvalidArgumentException('La operación no es válida.');
    }
    $db->begin_transaction();
    try {
        $pedido = agranda_resena_compra_entregada($db, $usuario, $producto, true);
        if ($pedido === null) {
            throw new DomainException($modo === 'editar'
                ? 'No puedes editar esta reseña porque actualmente no tienes una compra entregada válida de este producto.'
                : 'Solo puedes calificar productos de pedidos entregados.');
        }
        $existente = agranda_resena_existente($db, $usuario, $producto, true);
        if ($modo === 'crear' && $existente) {
            $db->commit();
            return 'existente';
        }
        if ($modo === 'editar') {
            if (!$existente || $idResena !== (int) $existente['id_resena']) {
                throw new DomainException('No puedes editar esta reseña.');
            }
            $stmt = agranda_resena_preparar($db, 'UPDATE resenas SET calificacion = ?, comentario = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_resena = ? AND id_usuario = ? AND id_producto = ?');
            $stmt->bind_param('isiii', $contenido['calificacion'], $contenido['comentario'], $idResena, $usuario, $producto);
        } else {
            $stmt = agranda_resena_preparar($db, 'INSERT INTO resenas (id_producto, id_usuario, id_pedido, calificacion, comentario, compra_verificada, estado) VALUES (?, ?, ?, ?, ?, 1, 1)');
            $stmt->bind_param('iiiis', $producto, $usuario, $pedido, $contenido['calificacion'], $contenido['comentario']);
        }
        try {
            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) { throw new mysqli_sql_exception('La reseña ya existe.', 1062); }
                throw new RuntimeException('No fue posible guardar tu reseña.');
            }
        } finally { $stmt->close(); }
        $db->commit();
        return $modo === 'crear' ? 'creada' : 'editada';
    } catch (Throwable $error) {
        $db->rollback();
        if ($modo === 'crear' && $error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062
            && agranda_resena_existente($db, $usuario, $producto)) {
            return 'existente';
        }
        throw $error;
    }
}

function agranda_resena_existe(mysqli $db, int $usuario, int $producto): bool
{
    return agranda_resena_existente($db, $usuario, $producto) !== null;
}

function agranda_resena_elegibilidad(mysqli $db, int $usuario, int $producto): array
{
    $pedido = agranda_resena_compra_entregada($db, $usuario, $producto);
    $resena = agranda_resena_existente($db, $usuario, $producto);
    return ['id_pedido' => $pedido, 'resena' => $resena,
        'puede_crear' => $pedido !== null && $resena === null,
        'puede_editar' => $pedido !== null && $resena !== null];
}

function agranda_resena_propia(mysqli $db, int $usuario, int $resena): ?array
{
    agranda_resena_id($usuario); agranda_resena_id($resena);
    $stmt = agranda_resena_preparar($db, 'SELECT * FROM resenas WHERE id_resena = ? AND id_usuario = ? LIMIT 1');
    try {
        $stmt->bind_param('ii', $resena, $usuario);
        return agranda_resena_ejecutar($stmt)->fetch_assoc() ?: null;
    } finally { $stmt->close(); }
}

// Mantener el mismo filtro en resumen, recientes y futuros listados públicos.
function agranda_resena_filtro_publico(): string
{
    return 'r.estado = 1 AND r.compra_verificada = 1 AND r.calificacion BETWEEN 1 AND 5';
}

function agranda_resena_resumen_publico(mysqli $db, int $producto): array
{
    agranda_resena_id($producto);
    $stmt = agranda_resena_preparar($db, 'SELECT AVG(r.calificacion) AS promedio, COUNT(*) AS total
        FROM resenas r WHERE r.id_producto = ? AND ' . agranda_resena_filtro_publico());
    try {
        $stmt->bind_param('i', $producto);
        $fila = agranda_resena_ejecutar($stmt)->fetch_assoc();
        return ['promedio' => $fila['promedio'] === null ? null : (float) $fila['promedio'],
            'total' => (int) $fila['total']];
    } finally { $stmt->close(); }
}

/** Una consulta agrupada para todas las tarjetas; nunca consultar dentro del render. */
function agranda_resena_resumenes_publicos(mysqli $db, array $productos): array
{
    $ids = array_values(array_unique(array_map('intval', $productos)));
    if (!$ids) { return []; }
    foreach ($ids as $id) { agranda_resena_id($id); }
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = agranda_resena_preparar($db, 'SELECT r.id_producto, AVG(r.calificacion) AS promedio, COUNT(*) AS total
        FROM resenas r WHERE r.id_producto IN (' . $marcadores . ') AND ' . agranda_resena_filtro_publico() . '
        GROUP BY r.id_producto');
    $resumenes = array_fill_keys($ids, ['promedio' => null, 'total' => 0]);
    try {
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        foreach (agranda_resena_ejecutar($stmt) as $fila) {
            $resumenes[(int) $fila['id_producto']] = ['promedio' => (float) $fila['promedio'], 'total' => (int) $fila['total']];
        }
        return $resumenes;
    } finally { $stmt->close(); }
}

/** Datos de presentación comunes para Inicio y Productos, sin HTML ni consultas. */
function agranda_resena_valoracion_tarjeta(array $resumen): array
{
    $total = (int) $resumen['total'];
    $promedio = $resumen['promedio'];
    $texto = $total > 0 ? number_format($promedio, 1, '.', '') : null;
    $cantidad = $total . ($total === 1 ? ' reseña' : ' reseñas');
    $rellenos = [];
    if ($total > 0) {
        for ($i = 0; $i < 5; $i++) {
            $rellenos[] = number_format(max(0, min(1, $promedio - $i)) * 100, 2, '.', '');
        }
    }
    return ['promedio_texto' => $texto, 'cantidad_texto' => $cantidad, 'rellenos' => $rellenos,
        'etiqueta' => $total > 0 ? 'Calificación promedio: ' . $texto . ' de 5, basada en ' . $cantidad . '.' : 'Sin calificaciones'];
}

function agranda_resena_recientes_publicas(mysqli $db, int $producto, int $limite = 3, int $desplazamiento = 0): array
{
    agranda_resena_id($producto);
    if ($limite < 1 || $limite > 50 || $desplazamiento < 0) {
        throw new InvalidArgumentException('El límite de reseñas debe estar entre 1 y 50.');
    }
    $stmt = agranda_resena_preparar($db, 'SELECT r.id_resena, r.calificacion, r.comentario,
        r.fecha, r.fecha_actualizacion, u.nombre AS nombre_cliente
        FROM resenas r INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
        WHERE r.id_producto = ? AND ' . agranda_resena_filtro_publico() . '
        ORDER BY r.fecha DESC, r.id_resena DESC LIMIT ? OFFSET ?');
    try {
        $stmt->bind_param('iii', $producto, $limite, $desplazamiento);
        return agranda_resena_ejecutar($stmt)->fetch_all(MYSQLI_ASSOC);
    } finally { $stmt->close(); }
}

/** Proyección pública: no enviar identificadores ni datos privados de la compra. */
function agranda_resena_datos_publicos(array $filas): array
{
    return array_map(static fn(array $fila): array => [
        'nombre_cliente' => $fila['nombre_cliente'],
        'calificacion' => (int) $fila['calificacion'],
        'comentario' => $fila['comentario'],
        'fecha' => $fila['fecha'],
        'editada' => $fila['fecha_actualizacion'] !== null
    ], $filas);
}
