<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// OBTENER ID DE LA PROMOCIÓN
// =================================

$id_promocion = intval($_GET["id"] ?? 0);

if ($id_promocion <= 0) {
    header("Location: promociones.php");
    exit();
}


// =================================
// CONSULTAR PROMOCIÓN
// =================================

$sqlPromocion = "SELECT
                    id_promocion,
                    nombre,
                    descripcion,
                    tipo,
                    valor_descuento,
                    fecha_inicio,
                    fecha_fin,
                    estado
                 FROM promociones
                 WHERE id_promocion = ?";

$stmtPromocion = $conexion->prepare($sqlPromocion);

if (!$stmtPromocion) {
    die("Error en la consulta: " . $conexion->error);
}

$stmtPromocion->bind_param("i", $id_promocion);

$stmtPromocion->execute();

$resultadoPromocion = $stmtPromocion->get_result();


// =================================
// VERIFICAR SI EXISTE
// =================================

if ($resultadoPromocion->num_rows === 0) {
    header("Location: promociones.php");
    exit();
}

$promocion = $resultadoPromocion->fetch_assoc();


// =================================
// CONSULTAR PRODUCTOS
// =================================

$sqlProductos = "SELECT
                    id_producto,
                    nombre
                 FROM productos
                 WHERE estado = 1
                 ORDER BY nombre ASC";

$resultadoProductos = $conexion->query($sqlProductos);


// =================================
// CONSULTAR PRODUCTOS YA ASOCIADOS
// =================================

$sqlAsociados = "SELECT id_producto
                 FROM promocion_producto
                 WHERE id_promocion = ?";

$stmtAsociados = $conexion->prepare($sqlAsociados);

$stmtAsociados->bind_param("i", $id_promocion);

$stmtAsociados->execute();

$resultadoAsociados = $stmtAsociados->get_result();


// Guardamos los IDs en un arreglo
$productosAsociados = [];

while ($producto = $resultadoAsociados->fetch_assoc()) {

    $productosAsociados[] = $producto["id_producto"];

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Promoción | AGRANDA</title>

    <link rel="stylesheet" href="promociones.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Promociones</p>

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

                <li><a href="promociones.php" class="activo">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <!-- =================================
         CONTENIDO PRINCIPAL
    ================================== -->

    <main class="contenido">

        <!-- =================================
             ENCABEZADO
        ================================== -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"]); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"]); ?></div>

                <a href="../cerrar_sesion.php"
                   class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- =================================
             FORMULARIO
        ================================== -->

        <div class="principal">

            <form
                action="acciones_promociones.php"
                method="POST"
            >

                <!-- ID DE LA PROMOCIÓN -->

                <input
                    type="hidden"
                    name="id_promocion"
                    value="<?php echo $promocion["id_promocion"]; ?>"
                >

                <!-- ACCIÓN -->

                <input
                    type="hidden"
                    name="accion"
                    value="editar"
                >

                <h2>✏️ Editar Promoción</h2>


                <!-- =================================
                     NOMBRE
                ================================== -->

                <label>Nombre de la promoción</label>

                <input
                    type="text"
                    name="nombre"
                    value="<?php echo htmlspecialchars($promocion["nombre"]); ?>"
                    required
                >


                <!-- =================================
                     DESCRIPCIÓN
                ================================== -->

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    rows="4"
                    placeholder="Describe brevemente la promoción..."
                ><?php echo htmlspecialchars($promocion["descripcion"] ?? ""); ?></textarea>


                <!-- =================================
                     TIPO
                ================================== -->

                <label>Tipo de descuento</label>

                <select
                    name="tipo"
                    required
                >

                    <option value="Porcentaje"<?php echo $promocion["tipo"] === "Porcentaje"? "selected": "";?>>Porcentaje (%)</option>

                    <option value="Fijo"<?php echo $promocion["tipo"] === "Fijo"? "selected": "";?>>Valor fijo ($)</option>

                </select>


                <!-- =================================
                     VALOR
                ================================== -->

                <label>Valor del descuento</label>

                <input
                    type="number"
                    name="valor_descuento"
                    step="0.01"
                    min="0"
                    value="<?php echo htmlspecialchars($promocion["valor_descuento"]); ?>"
                    required
                >


                <!-- =================================
                     PRODUCTOS
                ================================== -->

                <div class="campo-aplicacion">

                    <label>Productos a los que aplicar la promoción</label>

                    <input
                        type="search"
                        id="buscar-producto"
                        class="buscar-aplicacion"
                        placeholder="🔎 Buscar producto..."
                    >


                    <div
                        class="lista-checklist"
                        id="lista-productos"
                    >

                        <?php if ($resultadoProductos && $resultadoProductos->num_rows > 0): ?>

                            <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>

                                <label class="item-checklist">

                                    <input
                                        type="checkbox"
                                        name="id_producto[]"
                                        value="<?php echo $producto["id_producto"]; ?>"

                                        <?php

                                        if (
                                            in_array(
                                                $producto["id_producto"],
                                                $productosAsociados
                                            )
                                        ) {

                                            echo "checked";

                                        }

                                        ?>
                                    >

                                    <span><?php echo htmlspecialchars($producto["nombre"]);?></span>

                                </label>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <p class="sin-resultados">No hay productos activos disponibles.</p>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================
                     FECHAS
                ================================== -->

                <div class="fila">

                    <div class="campo">

                        <label>Fecha de inicio</label>

                        <input
                            type="date"
                            name="fecha_inicio"
                            value="<?php echo $promocion["fecha_inicio"]; ?>"
                            required
                        >

                    </div>


                    <div class="campo">

                        <label>Fecha de finalización</label>

                        <input
                            type="date"
                            name="fecha_fin"
                            value="<?php echo $promocion["fecha_fin"]; ?>"
                            required
                        >

                    </div>

                </div>


                <!-- =================================
                     ESTADO
                ================================== -->

                <label>Estado</label>

                <select
                    name="estado"
                    required
                >

                    <option value="1"<?php echo $promocion["estado"]? "selected": "";?>>Activa</option>

                    <option value="0"<?php echo !$promocion["estado"]? "selected": "";?>>Inactiva</option>

                </select>

                <br><br>

                <!-- =================================
                     BOTONES
                ================================== -->

                <button type="submit"
                    class="btn-nuevo">💾 Guardar cambios</button>

                <a href="promociones.php"
                    class="btn-cancelar">Cancelar</a>

            </form>

        </div>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>

<script>

// =================================
// BUSCADOR DE PRODUCTOS
// =================================

const buscarProducto =
    document.getElementById("buscar-producto");

const listaProductos =
    document.getElementById("lista-productos");


buscarProducto.addEventListener("input", function(){

    const texto =
        this.value.toLowerCase().trim();


    const productos =
        listaProductos.querySelectorAll(
            ".item-checklist"
        );


    productos.forEach(function(producto){

        const nombre =
            producto.textContent.toLowerCase();


        if (nombre.includes(texto)) {

            producto.style.display = "flex";

        } else {

            producto.style.display = "none";

        }

    });

});

</script>
</body>
</html>