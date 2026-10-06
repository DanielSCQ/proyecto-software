<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) { header('Location: ../login.php'); exit(); }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: cupones.php'); exit(); }
function agranda_error_cupon_admin(string $mensaje, array $valores = []): void
{
    $_SESSION['cupon_admin_error'] = $mensaje;
    $_SESSION['cupon_admin_valores'] = $valores;
    header('Location: cupones.php'); exit();
}
$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || !is_string($_SESSION['csrf_cupon_admin'] ?? null) || !hash_equals($_SESSION['csrf_cupon_admin'], $csrf)) { agranda_error_cupon_admin('La solicitud expiró. Recarga la página.'); }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/cupones.php';
$campos = ['codigo','descripcion','tipo_descuento','valor_descuento','fecha_inicio','fecha_fin','uso_maximo','uso_maximo_por_cliente','monto_minimo_compra','compras_minimas','estado'];
$v = [];
foreach ($campos as $campo) { $dato = $_POST[$campo] ?? ''; if (!is_string($dato)) { agranda_error_cupon_admin('Los campos deben contener valores válidos.'); } $v[$campo] = trim($dato); }
$id = filter_var($_POST['id_cupon'] ?? '0', FILTER_VALIDATE_INT);
if ($id === false || $id < 0) { agranda_error_cupon_admin('El cupón seleccionado no es válido.'); }
$v['id_cupon'] = $id;
$transaccion = false;
try {
    if (!in_array($v['estado'], ['0','1'], true)) { throw new RuntimeException('Selecciona un estado válido.'); }
    $conexion->begin_transaction(); $transaccion = true;
    $actual = null;
    if ($id > 0) {
        $s = $conexion->prepare('SELECT * FROM cupones WHERE id_cupon = ? FOR UPDATE'); $s->bind_param('i', $id); $s->execute(); $actual = $s->get_result()->fetch_assoc(); $s->close();
        if (!$actual) { throw new RuntimeException('El cupón no existe.'); }
        $s = $conexion->prepare('SELECT COUNT(*) FROM uso_cupones WHERE id_cupon = ?'); $s->bind_param('i', $id); $s->execute(); $usado = (int) $s->get_result()->fetch_row()[0]; $s->close();
        if ($usado > 0) {
            // Política conservadora: después del primer uso solo cambia el estado.
            $estado = (int) $v['estado']; $s = $conexion->prepare('UPDATE cupones SET estado = ? WHERE id_cupon = ?'); $s->bind_param('ii', $estado, $id); $s->execute(); $s->close();
            $conexion->commit(); $transaccion = false;
            $_SESSION['cupon_admin_exito'] = 'Estado actualizado. Los datos del cupón utilizado permanecen protegidos.';
            unset($_SESSION['cupon_admin_valores']); $_SESSION['csrf_cupon_admin'] = bin2hex(random_bytes(32)); header('Location: cupones.php'); exit();
        }
    }
    $v['codigo'] = agranda_cupon_codigo($v['codigo']);
    if (!mb_check_encoding($v['descripcion'], 'UTF-8') || mb_strlen($v['descripcion'], 'UTF-8') > 2000 || preg_match('/[<>\x00]/u', $v['descripcion'])) { throw new RuntimeException('La descripción admite hasta 2000 caracteres de texto plano, sin HTML.'); }
    if (!in_array($v['tipo_descuento'], ['Porcentaje','Fijo'], true)) { throw new RuntimeException('Selecciona Porcentaje o Fijo.'); }
    $valor = agranda_cupon_centavos($v['valor_descuento']);
    if ($valor < 1 || ($v['tipo_descuento'] === 'Porcentaje' && $valor > 10000)) { throw new RuntimeException('El descuento debe ser mayor que cero y el porcentaje no puede superar 100.'); }
    $v['valor_descuento'] = agranda_cupon_importe($valor);
    $v['monto_minimo_compra'] = agranda_cupon_importe(agranda_cupon_centavos($v['monto_minimo_compra']));
    foreach (['fecha_inicio','fecha_fin'] as $campo) {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $v[$campo]);
        if (!$fecha || $fecha->format('Y-m-d') !== $v[$campo] || $v[$campo] < '1000-01-01') { throw new RuntimeException('Introduce fechas válidas.'); }
    }
    if ($v['fecha_fin'] < $v['fecha_inicio']) { throw new RuntimeException('El vencimiento no puede ser anterior al inicio.'); }
    foreach (['uso_maximo','uso_maximo_por_cliente','compras_minimas'] as $campo) {
        if ($campo !== 'compras_minimas' && $v[$campo] === '') { $v[$campo] = null; continue; }
        $n = filter_var($v[$campo], FILTER_VALIDATE_INT, ['options'=>['min_range'=>$campo === 'compras_minimas' ? 0 : 1,'max_range'=>2147483647]]);
        if ($n === false) { throw new RuntimeException('Los límites deben ser enteros positivos; las compras mínimas pueden ser cero.'); }
        $v[$campo] = $n;
    }
    $s = $conexion->prepare('SELECT id_cupon FROM cupones WHERE codigo = ? AND id_cupon <> ? LIMIT 1'); $s->bind_param('si', $v['codigo'], $id); $s->execute(); $duplicado = $s->get_result()->fetch_assoc(); $s->close();
    if ($duplicado) { throw new RuntimeException('Ya existe un cupón con ese código.'); }
    if ($id > 0) {
        $s = $conexion->prepare('UPDATE cupones SET codigo=?,descripcion=?,tipo_descuento=?,valor_descuento=?,fecha_inicio=?,fecha_fin=?,uso_maximo=?,uso_maximo_por_cliente=?,monto_minimo_compra=?,compras_minimas=?,estado=? WHERE id_cupon=?');
        $s->bind_param('ssssssiisiii', $v['codigo'],$v['descripcion'],$v['tipo_descuento'],$v['valor_descuento'],$v['fecha_inicio'],$v['fecha_fin'],$v['uso_maximo'],$v['uso_maximo_por_cliente'],$v['monto_minimo_compra'],$v['compras_minimas'],$v['estado'],$id);
    } else {
        $s = $conexion->prepare('INSERT INTO cupones (codigo,descripcion,tipo_descuento,valor_descuento,fecha_inicio,fecha_fin,uso_maximo,uso_maximo_por_cliente,monto_minimo_compra,compras_minimas,estado) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $s->bind_param('ssssssiisii', $v['codigo'],$v['descripcion'],$v['tipo_descuento'],$v['valor_descuento'],$v['fecha_inicio'],$v['fecha_fin'],$v['uso_maximo'],$v['uso_maximo_por_cliente'],$v['monto_minimo_compra'],$v['compras_minimas'],$v['estado']);
    }
    $s->execute(); $s->close(); $conexion->commit(); $transaccion = false;
} catch (Throwable $error) {
    if ($transaccion) { $conexion->rollback(); }
    $mensaje = !($error instanceof mysqli_sql_exception) && ($error instanceof RuntimeException || $error instanceof InvalidArgumentException) ? $error->getMessage() : ((int) $error->getCode() === 1062 ? 'Ya existe un cupón con ese código.' : 'No fue posible guardar el cupón.');
    agranda_error_cupon_admin($mensaje, $v);
}
unset($_SESSION['cupon_admin_valores']); $_SESSION['csrf_cupon_admin'] = bin2hex(random_bytes(32)); $_SESSION['cupon_admin_exito'] = 'Cupón guardado correctamente.';
header('Location: cupones.php'); exit();
