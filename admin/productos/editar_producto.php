<?php

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


// Verificar sesión

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

require_once("../../config/conexion.php");

// Verificar ID producto

if (!isset($_GET["id"])) {

    header("Location: productos.php");
    exit();

}

$id_producto = $_GET["id"];

// Actualizar producto

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_categoria = $_POST["id_categoria"];
    $id_marca = $_POST["id_marca"];
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $codigo_producto = $_POST["codigo_producto"];
    $precio = $_POST["precio"];
    $peso = $_POST["peso"];
    $estado = $_POST["estado"];
    $destacado = $_POST["destacado"];

    $sql = "UPDATE productos SET

            id_categoria = ?,
            id_marca = ?,
            nombre = ?,
            descripcion = ?,
            codigo_producto = ?,
            precio = ?,
            peso = ?,
            estado = ?,
            destacado = ?

            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "iisssddiii",
        $id_categoria,
        $id_marca,
        $nombre,
        $descripcion,
        $codigo_producto,
        $precio,
        $peso,
        $estado,
        $destacado,
        $id_producto
    );

    if ($stmt->execute()) {

        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {

            $carpeta = "../../uploads/productos/";

            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            $nombreImagen = time() . "_" . basename($_FILES["imagen"]["name"]);
            $rutaImagen = $carpeta . $nombreImagen;

            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaImagen)) {

                $sqlBuscar = "SELECT id_imagen
                            FROM imagenes_producto
                            WHERE id_producto = ? AND principal = 1";

                $stmtBuscar = $conexion->prepare($sqlBuscar);
                $stmtBuscar->bind_param("i", $id_producto);
                $stmtBuscar->execute();

                $resultado = $stmtBuscar->get_result();

                if ($resultado->num_rows > 0) {

                    $sqlActualizar = "UPDATE imagenes_producto
                                    SET ruta_imagen = ?
                                    WHERE id_producto = ? AND principal = 1";

                    $stmtActualizar = $conexion->prepare($sqlActualizar);
                    $stmtActualizar->bind_param("si", $rutaImagen, $id_producto);
                    $stmtActualizar->execute();

                } else {

                    $sqlInsertar = "INSERT INTO imagenes_producto
                                    (id_producto, ruta_imagen, principal)
                                    VALUES (?, ?, 1)";

                    $stmtInsertar = $conexion->prepare($sqlInsertar);
                    $stmtInsertar->bind_param("is", $id_producto, $rutaImagen);
                    $stmtInsertar->execute();
                }
            }
        }

        header("Location: productos.php");
        exit();

    } else {

        echo "Error al actualizar producto";

    }

}

// Obtener producto actual

$sql = "SELECT * FROM productos WHERE id_producto = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i",$id_producto);

$stmt->execute();

$resultado = $stmt->get_result();

$producto = $resultado->fetch_assoc();

// Cargar categorías

$sqlCategorias = "SELECT id_categoria,nombre
                  FROM categorias
                  WHERE estado = 1
                  ORDER BY nombre ASC";

$resultadoCategorias = $conexion->query($sqlCategorias);

// Cargar marcas

$sqlMarcas = "SELECT id_marca,nombre
              FROM marcas
              WHERE estado = 1
              ORDER BY nombre ASC";

$resultadoMarcas = $conexion->query($sqlMarcas);

$sqlImagen = "SELECT ruta_imagen
              FROM imagenes_producto
              WHERE id_producto = ?
              AND principal = 1
              LIMIT 1";

$stmtImagen = $conexion->prepare($sqlImagen);
$stmtImagen->bind_param("i", $id_producto);
$stmtImagen->execute();

$resultadoImagen = $stmtImagen->get_result();

$imagen = $resultadoImagen->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>
<body>

    <div class="contenedor-dashboard">

    <!-- MENÚ LATERAL -->
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

        <!-- CONTENIDO -->
        <main class="contenido">

            <header class="encabezado">

                <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo $_SESSION["nombre"]; ?>!</h2>

            <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

            <div class="fecha-hora">

            <span id="fecha"></span><br>

            <span id="hora"></span>

            </div>

            <div class="usuario">👤 <?php echo $_SESSION["nombre"]; ?></div>


            <a href="../cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <div class="principal">

            <form method="POST" enctype="multipart/form-data">

                <h2>✏️ Editar Producto</h2>

            <label>Nombre del producto</label>

                <input
                    type="text"
                    name="nombre"
                    value="<?php echo $producto['nombre']; ?>"
                    required>

                <label>Código del producto</label>

                <input
                    type="text"
                    name="codigo_producto"
                    value="<?php echo $producto['codigo_producto']; ?>"
                    required>

                <label>Categoría</label>

                <select name="id_categoria" required>

                <option value="">Seleccione una categoría</option>


                <?php while($categoria = $resultadoCategorias->fetch_assoc()){ ?>


                <option 
                value="<?php echo $categoria['id_categoria']; ?>"
                <?php if($producto['id_categoria']==$categoria['id_categoria']) echo "selected"; ?>
                >

                <?php echo $categoria['nombre']; ?>

                </option>

                <?php } ?>

                </select>

                <label>Marca</label>

                <select name="id_marca">

                <option value="">
                Seleccione una marca
                </option>

                <?php while($marca = $resultadoMarcas->fetch_assoc()){ ?>

                <option
                value="<?php echo $marca['id_marca']; ?>"
                <?php if($producto['id_marca']==$marca['id_marca']) echo "selected"; ?>
                >

                <?php echo $marca['nombre']; ?>

                </option>

                <?php } ?>

                </select>

                <div class="fila">

                    <div class="campo">

                        <label>Precio de venta</label>

                        <input
                            type="number"
                            name="precio"
                            step="0.01"
                            min="0"
                            value="<?php echo $producto['precio']; ?>"
                            required
                        >
                    </div>

                    <div class="campo">

                        <label>Peso (kg)</label>

                        <input
                            type="number"
                            name="peso"
                            step="0.01"
                            min="0"
                            value="<?php echo $producto['peso']; ?>"
                        >
                    </div>

                </div>

                    <label>Descripción</label>
                
                            <textarea
                                name="descripcion"
                                rows="5"
                                ><?php echo $producto['descripcion']; ?></textarea>

                    <label>Imagen actual</label>

                        <?php if($imagen){ ?>

                            <img
                                src="<?php echo $imagen["ruta_imagen"]; ?>"
                                alt="Imagen del producto"
                                width="160">

                        <?php }else{ ?>

                            <p>Este producto no tiene imagen.</p>

                        <?php } ?>

                        <br><br>

                    <label>Cambiar imagen</label>

                        <input
                            type="file"
                            name="imagen"
                            accept="image/*">

                    <label>Estado</label>

                        <select name="estado">

                            <option value="1"
                            <?php if($producto['estado']==1) echo "selected"; ?>>
                            Activo
                            </option>


                            <option value="0"
                            <?php if($producto['estado']==0) echo "selected"; ?>>
                            Inactivo
                            </option>

                        </select>

                    <label>Producto destacado</label>

                        <select name="destacado">

                        <option value="0"
                        <?php if($producto['destacado']==0) echo "selected"; ?>>
                        No
                        </option>


                        <option value="1"
                        <?php if($producto['destacado']==1) echo "selected"; ?>>
                        Sí
                        </option>

                    </select>

                    <br><br>

                    <button
                        type="submit"
                        class="btn-nuevo">
                        💾 Actualizar Producto
                    </button>

                    <a href="productos.php" class="btn-cancelar">Cancelar</a>

            </form>
        </div>

        </main>

    </div>
<script src="../dashboard/dashboard.js"></script>
</body>
</html>