<?php
// Importes enteros en centavos; las reglas no dependen del origen del subtotal.
function agranda_cupon_codigo(string $codigo): string
{
    $codigo = strtoupper(trim($codigo));
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{0,49}$/D', $codigo)) {
        throw new InvalidArgumentException('El código debe tener entre 1 y 50 letras, números, guiones o guiones bajos, sin espacios.');
    }
    return $codigo;
}
function agranda_cupon_centavos($valor): int
{
    if (!is_string($valor) && !is_int($valor)) { throw new InvalidArgumentException('El importe no es válido.'); }
    if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', (string) $valor, $m)) {
        throw new InvalidArgumentException('Utiliza un importe positivo con hasta dos decimales.');
    }
    return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '', 2, '0');
}
function agranda_cupon_importe(int $centavos): string
{
    return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
}
function agranda_cupon_fecha(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');
}
function agranda_cupon_buscar(mysqli $db, string $codigo, bool $bloquear = false): ?array
{
    $codigo = agranda_cupon_codigo($codigo);
    $s = $db->prepare('SELECT * FROM cupones WHERE codigo = ? LIMIT 1' . ($bloquear ? ' FOR UPDATE' : ''));
    $s->bind_param('s', $codigo); $s->execute(); $fila = $s->get_result()->fetch_assoc(); $s->close();
    // No aceptar coincidencias adicionales de la colación como letras acentuadas.
    if ($fila && strtoupper(trim($fila['codigo'])) !== $codigo) { return null; }
    return $fila ?: null;
}
function agranda_cupon_validar(mysqli $db, array $cupon, int $usuario, ?int $subtotal = null, bool $bloquear = false): int
{
    $fecha = agranda_cupon_fecha();
    if ((int) $cupon['estado'] !== 1) { throw new RuntimeException('El cupón está inactivo.'); }
    if ($fecha < $cupon['fecha_inicio'] || $fecha > $cupon['fecha_fin']) { throw new RuntimeException('El cupón no está dentro de su periodo de vigencia.'); }
    $s = $db->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(id_usuario = ?),0) AS individual FROM uso_cupones WHERE id_cupon = ?' . ($bloquear ? ' FOR UPDATE' : ''));
    $id = (int) $cupon['id_cupon']; $s->bind_param('ii', $usuario, $id); $s->execute(); $usos = $s->get_result()->fetch_assoc(); $s->close();
    if ($cupon['uso_maximo'] !== null && (int) $usos['total'] >= (int) $cupon['uso_maximo']) { throw new RuntimeException('El cupón alcanzó su límite total de usos.'); }
    if ($cupon['uso_maximo_por_cliente'] !== null && (int) $usos['individual'] >= (int) $cupon['uso_maximo_por_cliente']) { throw new RuntimeException('Ya alcanzaste el límite de usos de este cupón.'); }
    $s = $db->prepare("SELECT COUNT(*) FROM pedidos p INNER JOIN estado_pedido e ON e.id_estado = p.id_estado WHERE p.id_usuario = ? AND BINARY e.nombre = 'Entregado'" . ($bloquear ? ' FOR UPDATE' : ''));
    $s->bind_param('i', $usuario); $s->execute(); $compras = (int) $s->get_result()->fetch_row()[0]; $s->close();
    if ($compras < (int) $cupon['compras_minimas']) { throw new RuntimeException('Este cupón requiere al menos ' . (int) $cupon['compras_minimas'] . ' compras entregadas.'); }
    $valor = agranda_cupon_centavos((string) $cupon['valor_descuento']);
    if ($valor < 1 || !in_array($cupon['tipo_descuento'], ['Porcentaje','Fijo'], true) || ($cupon['tipo_descuento'] === 'Porcentaje' && $valor > 10000)) { throw new RuntimeException('El cupón tiene un descuento no válido.'); }
    if ($subtotal === null) { return 0; }
    if ($subtotal < agranda_cupon_centavos((string) ($cupon['monto_minimo_compra'] ?? '0'))) { throw new RuntimeException('La compra no alcanza el monto mínimo del cupón.'); }
    $descuento = $cupon['tipo_descuento'] === 'Fijo' ? $valor : intdiv($subtotal * $valor + 5000, 10000);
    return min($subtotal, $descuento);
}
function agranda_cupon_disponibles(mysqli $db, int $usuario): array
{
    $fecha = agranda_cupon_fecha();
    $s = $db->prepare('SELECT * FROM cupones WHERE estado = 1 AND fecha_inicio <= ? AND fecha_fin >= ? ORDER BY fecha_fin, codigo');
    $s->bind_param('ss', $fecha, $fecha); $s->execute(); $filas = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); $validos = [];
    foreach ($filas as $fila) { try { agranda_cupon_validar($db, $fila, $usuario); $validos[] = $fila; } catch (RuntimeException | InvalidArgumentException $error) { /* No es elegible. */ } }
    return $validos;
}
function agranda_cupon_subtotal_carrito(mysqli $db, array $carrito): int
{
    $total = 0;
    $s = $db->prepare('SELECT precio FROM productos WHERE id_producto = ? AND estado = 1');
    foreach ($carrito as $id => $item) {
        $id = filter_var($id, FILTER_VALIDATE_INT); $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$cantidad || $cantidad < 1 || $cantidad > 1000000) { throw new RuntimeException('El carrito no es válido.'); }
        $s->bind_param('i', $id); $s->execute(); $fila = $s->get_result()->fetch_assoc();
        if (!$fila) { throw new RuntimeException('Un producto ya no está disponible.'); }
        $total += agranda_cupon_centavos($fila['precio']) * $cantidad;
        if ($total > 9999999999) { throw new RuntimeException('El importe supera el máximo permitido.'); }
    }
    $s->close(); return $total;
}
function agranda_cupon_detalle(mysqli $db, int $pedido): ?array
{
    $s = $db->prepare('SELECT c.codigo, u.descuento_aplicado, (SELECT SUM(d.subtotal) FROM detalle_pedido d WHERE d.id_pedido = u.id_pedido) AS subtotal FROM uso_cupones u INNER JOIN cupones c ON c.id_cupon = u.id_cupon WHERE u.id_pedido = ? LIMIT 1');
    $s->bind_param('i', $pedido); $s->execute(); $fila = $s->get_result()->fetch_assoc(); $s->close(); return $fila ?: null;
}
