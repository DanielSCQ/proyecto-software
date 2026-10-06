<?php
session_start();require_once __DIR__.'/../includes/auth.php';if(!agranda_admin_autorizado()){header('Location: ../login.php');exit();}
require_once __DIR__.'/../../config/conexion.php';require_once __DIR__.'/../../config/cupones.php';
if(empty($_SESSION['csrf_cupon_admin']))$_SESSION['csrf_cupon_admin']=bin2hex(random_bytes(32));
$mensajeError=$_SESSION['cupon_admin_error']??'';$mensajeExito=$_SESSION['cupon_admin_exito']??'';$anteriores=$_SESSION['cupon_admin_valores']??null;unset($_SESSION['cupon_admin_error'],$_SESSION['cupon_admin_exito'],$_SESSION['cupon_admin_valores']);
$campos=['codigo'=>['Código','text'],'descripcion'=>['Descripción (opcional)','textarea'],'tipo_descuento'=>['Tipo de descuento','select'],'valor_descuento'=>['Valor','number'],'fecha_inicio'=>['Fecha de inicio','date'],'fecha_fin'=>['Vencimiento','date'],'uso_maximo'=>['Uso máximo total','number'],'uso_maximo_por_cliente'=>['Uso máximo por cliente','number'],'monto_minimo_compra'=>['Compra mínima','number'],'compras_minimas'=>['Compras entregadas mínimas','number']];
$valores=array_fill_keys(array_keys($campos),'');$valores=array_merge($valores,['id_cupon'=>0,'estado'=>1,'tipo_descuento'=>'Porcentaje','monto_minimo_compra'=>'0.00','compras_minimas'=>0,'fecha_inicio'=>agranda_cupon_fecha(),'fecha_fin'=>agranda_cupon_fecha()]);
$lista=[];$bloqueado=false;
try{$lista=$conexion->query('SELECT c.*, (SELECT COUNT(*) FROM uso_cupones u WHERE u.id_cupon=c.id_cupon) AS usos FROM cupones c ORDER BY c.id_cupon DESC')->fetch_all(MYSQLI_ASSOC);$editar=filter_var($_GET['editar']??0,FILTER_VALIDATE_INT);if(is_array($anteriores))$editar=(int)($anteriores['id_cupon']??0);foreach($lista as $fila){if((int)$fila['id_cupon']===$editar){$valores=$fila;$bloqueado=(int)$fila['usos']>0;break;}}if(is_array($anteriores)&&!$bloqueado)$valores=array_merge($valores,$anteriores);}catch(Throwable $e){$mensajeError='No fue posible cargar los cupones.';}
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cupones | AGRANDA</title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            v_admin("configuracion.css", __DIR__),
            ENT_QUOTES,
            "UTF-8"
        ) ?>"
    >

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ===============================
         MENÚ LATERAL
    ================================ -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Configuración</p>

        </div>


        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="configuracion.php" class="activo">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- ===============================
         CONTENIDO PRINCIPAL
    ================================ -->

    <main class="contenido">


        <!-- ===============================
             ENCABEZADO
        ================================ -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">
                    👤 <?php echo htmlspecialchars($_SESSION["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- ===============================
             CONFIGURACIÓN
        ================================ -->

<section class="contenido-configuracion"><div class="encabezado-subconfiguracion"><div><span class="subconfiguracion-etiqueta">BENEFICIOS</span><h2>Cupones</h2><p>Gestiona los descuentos y sus condiciones de uso.</p></div><a class="btn-volver-configuracion" href="configuracion.php">← Volver a configuración</a></div>
<?php foreach (['error'=>$mensajeError,'exito'=>$mensajeExito] as $tipo=>$mensaje): if ($mensaje !== ''): ?><div class="mensaje-configuracion-<?= $tipo ?>" role="<?= $tipo === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8') ?></div><?php endif; endforeach; ?>
<div class="cupones-admin-lista"><h3>Cupones existentes</h3><div class="cupones-tabla"><table><thead><tr><th>Código</th><th>Descuento</th><th>Vigencia</th><th>Usos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($lista as $c): ?><tr><td><?= htmlspecialchars($c['codigo'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($c['valor_descuento'],ENT_QUOTES,'UTF-8') ?> <?= $c['tipo_descuento'] === 'Porcentaje' ? '%' : 'COP' ?></td><td><?= htmlspecialchars($c['fecha_inicio'].' / '.$c['fecha_fin'],ENT_QUOTES,'UTF-8') ?></td><td><?= (int)$c['usos'] ?> / <?= $c['uso_maximo'] === null ? 'Sin límite' : (int)$c['uso_maximo'] ?></td><td><?= (int)$c['estado'] === 1 ? 'Activo' : 'Inactivo' ?></td><td><a href="cupones.php?editar=<?= (int)$c['id_cupon'] ?>">Consultar / editar</a></td></tr><?php endforeach; ?>
<?php if (!$lista): ?><tr><td colspan="6">Todavía no hay cupones.</td></tr><?php endif; ?></tbody></table></div></div>
<form action="acciones_cupones.php" method="POST" class="cupon-admin-form"><h3><?= $valores['id_cupon'] ? 'Editar cupón' : 'Crear cupón' ?></h3>
<?php if ($bloqueado): ?><p>Este cupón ya fue utilizado. Solo puedes modificar su estado para proteger el historial.</p><?php endif; ?>
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_cupon_admin'],ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="id_cupon" value="<?= (int)$valores['id_cupon'] ?>">
<fieldset <?= $bloqueado ? 'disabled' : '' ?> class="cupon-admin-campos">
<?php foreach ($campos as $campo=>[$etiqueta,$tipo]): ?><div><label for="<?= $campo ?>"><?= $etiqueta ?></label>
<?php if ($tipo === 'textarea'): ?><textarea id="<?= $campo ?>" name="<?= $campo ?>" maxlength="2000" rows="3"><?= htmlspecialchars((string)$valores[$campo],ENT_QUOTES,'UTF-8') ?></textarea>
<?php elseif ($tipo === 'select'): ?><select id="<?= $campo ?>" name="<?= $campo ?>"><?php foreach (['Porcentaje','Fijo'] as $opcion): ?><option <?= $valores[$campo] === $opcion ? 'selected' : '' ?>><?= $opcion ?></option><?php endforeach; ?></select>
<?php else: ?><input id="<?= $campo ?>" name="<?= $campo ?>" type="<?= $tipo ?>" value="<?= htmlspecialchars((string)($valores[$campo] ?? ''),ENT_QUOTES,'UTF-8') ?>" <?= $campo === 'codigo' ? 'maxlength="50"' : '' ?> <?= $tipo === 'number' ? 'min="0" step="'.(in_array($campo,['valor_descuento','monto_minimo_compra'],true)?'0.01':'1').'"' : '' ?> <?= in_array($campo,['uso_maximo','uso_maximo_por_cliente'],true) ? '' : 'required' ?> ><?php endif; ?>
<?php if (in_array($campo,['uso_maximo','uso_maximo_por_cliente'],true)): ?><small>Vacío: sin límite.</small><?php endif; ?></div><?php endforeach; ?></fieldset>
<label for="estado">Estado</label><select id="estado" name="estado"><option value="1" <?= (string)$valores['estado'] === '1' ? 'selected' : '' ?>>Activo</option><option value="0" <?= (string)$valores['estado'] === '0' ? 'selected' : '' ?>>Inactivo</option></select>
<div class="acciones-apariencia"><a href="cupones.php" class="btn-cancelar-configuracion">Nuevo cupón</a><button class="btn-guardar-configuracion" type="submit">Guardar cupón</button></div></form></section>

    </main>

</div>


<script
    src="<?= htmlspecialchars(
        v_admin("../dashboard/dashboard.js", __DIR__),
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
></script>

</body>

</html>
