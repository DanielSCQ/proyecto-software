<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

    $sqlCategorias = "SELECT id_categoria, nombre
                      FROM categorias
                      WHERE estado = 1
                      ORDER BY nombre ASC";

    $resultadoCategorias = $conexion->query($sqlCategorias);

    $sqlMarcas = "SELECT id_marca, nombre
              FROM marcas
              WHERE estado = 1
              ORDER BY nombre ASC";

    $resultadoMarcas = $conexion->query($sqlMarcas);

    $sqlProveedores = "SELECT id_proveedor, nombre
                   FROM proveedores
                   WHERE estado = 1
                   ORDER BY nombre ASC";

    $resultadoProveedores = $conexion->query($sqlProveedores);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Control | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

    <!-- Contenedor principal -->
    <div class="contenedor-dashboard">

        <!-- Menú lateral -->
        <aside class="menu-lateral">

            <div class="logo-panel">

                <h1>AGRANDA</h1>

                <p>productos</p>

            </div>

            <nav>

                <ul>

                    <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                    <li><a href="productos.php" class="activo">📦 Productos</a></li>

                    <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                    <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                    <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                    <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                    <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                    <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                    <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                    <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                    <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

                </ul>

            </nav>

        </aside>

        <!-- Contenido principal -->
        <main class="contenido">

            <!-- Encabezado -->
            <header class="encabezado">

                <div class="titulo-panel">
                    <h2>¡Bienvenido, <?php echo $_SESSION["nombre"]; ?>!</h2>
                        <p>Panel de Administración AGRANDA</p>
                </div>

                <div class="panel-usuario">

                    <div class="fecha-hora">
                        <span id="fecha"></span><br>
                        <span id="hora"></span>
                    </div>

                <div class="usuario">
                    👤 <?php echo $_SESSION["nombre"]; ?>
                </div>

                    <a href="../cerrar_sesion.php" class="btn-salir">
                        Cerrar sesión
                    </a>

                </div>

            </header>
            <div class="principal">

            <form action="acciones_productos.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="accion" value="agregar">

                <h2>➕ Agregar Producto</h2>

                <label>Nombre del producto</label>
                <input
                    type="text"
                    name="nombre"
                    required>

                <label>Código del producto</label>
                <input
                    type="text"
                    name="codigo_producto"
                    required>

                <label>Categoría</label>

                <select name="id_categoria" required>

                    <option value="">Seleccione una categoría</option>

                    <?php while($categoria = $resultadoCategorias->fetch_assoc()){ ?>

                        <option value="<?php echo $categoria["id_categoria"]; ?>">
                            <?php echo $categoria["nombre"]; ?>
                        </option>

                    <?php } ?>

                </select>


                <label>Marca</label>

                <select name="id_marca" required>

                    <option value="">Seleccione una marca</option>

                    <?php while($marca = $resultadoMarcas->fetch_assoc()){ ?>

                        <option value="<?php echo $marca["id_marca"]; ?>">
                            <?php echo $marca["nombre"]; ?>
                        </option>

                    <?php } ?>

                </select>

                <label>Proveedor</label>

                <select name="id_proveedor" required>

                    <option value="">Seleccione un proveedor</option>

                        <?php while($proveedor = $resultadoProveedores->fetch_assoc()){ ?>

                    <option value="<?php echo $proveedor["id_proveedor"]; ?>">
                        <?php echo $proveedor["nombre"]; ?>
                    </option>

                    <?php } ?>

                </select>

                <label>Código del proveedor</label>

                <input
                    type="text"
                    name="codigo_proveedor">

                <div class="fila">

                    <div class="campo">

                        <label>Precio de venta</label>

                        <input
                            type="number"
                            name="precio"
                            step="0.01"
                            min="0"
                            required>

                    </div>

                    <div class="campo">

                        <label>Precio de compra</label>

                        <input
                            type="number"
                            name="precio_compra"
                            step="0.01"
                            min="0"
                            required>

                    </div>
                    
                </div>

                <div class="fila">

                    <div class="campo">

                        <label>Stock inicial</label>

                        <input
                            type="number"
                            name="stock_actual"
                            min="0"
                            value="0"
                            required>

                    </div>

                    <div class="campo">

                        <label>Stock mínimo</label>

                        <input
                            type="number"
                            name="stock_minimo"
                            min="0"
                            value="0"
                            required>

                    </div>

                </div>                

                <div class="fila">

                    <div class="campo">

                        <label>Peso (kg)</label>

                        <input
                            type="number"
                            name="peso"
                            step="0.01"
                            min="0">

                    </div>

                </div>


                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    rows="5"></textarea>


                <label>Imagen del producto</label>

                <input
                    type="file"
                    name="imagen"
                    accept="image/*">


                <label>Estado</label>

                <select name="estado">

                    <option value="1">Activo</option>

                    <option value="0">Inactivo</option>

                </select>


                <label>Producto destacado</label>

                <select name="destacado">

                    <option value="0">No</option>

                    <option value="1">Sí</option>

                </select>

                <br><br>

                <button
                    type="submit"
                    class="btn-nuevo">

                    💾 Guardar Producto

                </button>

                <a
                    href="productos.php"
                    class="btn-cancelar">

                    Cancelar

                </a>

            </form>
        </div>    
        </main>

    </div>

    <script src="../dashboard/dashboard.js"></script>

</body>

</html>