<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// CONSULTA DE PRODUCTOS
// =================================

$sqlProductos = "SELECT id_producto, nombre
                 FROM productos
                 WHERE estado = 1
                 ORDER BY nombre ASC";

$resultadoProductos = $conexion->query($sqlProductos);


// =================================
// CONSULTA DE CATEGORÍAS
// =================================

$sqlCategorias = "SELECT id_categoria, nombre
                  FROM categorias
                  WHERE estado = 1
                  ORDER BY nombre ASC";

$resultadoCategorias = $conexion->query($sqlCategorias);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva Promoción | AGRANDA</title>

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
             FORMULARIO DE PROMOCIÓN
        ================================== -->

        <div class="principal">

            <form
                action="acciones_promociones.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="agregar"
                >


                <h2>🎁 Nueva Promoción</h2>

                <!-- =================================
                     NOMBRE
                ================================== -->

                <label>Nombre de la promoción</label>

                <input
                    type="text"
                    name="nombre"
                    placeholder="Ejemplo: Descuento de temporada"
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
                ></textarea>

                <!-- =================================
                     TIPO DE DESCUENTO
                ================================== -->

                <label>Tipo de descuento</label>

                <select
                    name="tipo"
                    id="tipo"
                    required
                >

                    <option value="">Seleccione un tipo de descuento</option>

                    <option value="Porcentaje">Porcentaje (%)</option>

                    <option value="Fijo">Valor fijo ($)</option>

                </select>

                <!-- =================================
                     VALOR DEL DESCUENTO
                ================================== -->

                <label>Valor del descuento</label>

                <input
                    type="number"
                    name="valor_descuento"
                    step="0.01"
                    min="0"
                    placeholder="Ejemplo: 15"
                    required
                >

                <!-- =================================
                     APLICAR PROMOCIÓN
                ================================== -->

                <label>Aplicar promoción a</label>

                <select
                    name="aplica_a"
                    id="aplica_a"
                    required
                >

                    <option value="">Seleccione una opción</option>

                    <option value="producto">📦 Producto</option>

                    <option value="categoria">🗂️ Categoría</option>

                </select>

                <!-- =================================
                    SELECCIONAR PRODUCTOS
                ================================== -->

                <div
                    class="campo-aplicacion"
                    id="campo-producto"
                    style="display:none;"
                >

                    <label>Productos a los que aplicar la promoción</label>

                    <input
                        type="search"
                        id="buscar-producto"
                        class="buscar-aplicacion"
                        placeholder="🔎 Buscar producto..."
                    >

                    <div class="lista-checklist" id="lista-productos">

                        <?php if ($resultadoProductos && $resultadoProductos->num_rows > 0): ?>

                            <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>

                                <label class="item-checklist">

                                    <input
                                        type="checkbox"
                                        name="id_producto[]"
                                        value="<?php echo $producto["id_producto"]; ?>"
                                    >

                                    <span>
                                        <?php echo htmlspecialchars($producto["nombre"]); ?>
                                    </span>

                                </label>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <p class="sin-resultados">
                                No hay productos activos disponibles.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- =================================
                    SELECCIONAR CATEGORÍAS
                ================================== -->

                <div
                    class="campo-aplicacion"
                    id="campo-categoria"
                    style="display:none;"
                >

                    <label>Categorías a las que aplicar la promoción</label>

                    <input
                        type="search"
                        id="buscar-categoria"
                        class="buscar-aplicacion"
                        placeholder="🔎 Buscar categoría..."
                    >

                    <div class="lista-checklist" id="lista-categorias">

                        <?php if ($resultadoCategorias && $resultadoCategorias->num_rows > 0): ?>

                            <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                                <label class="item-checklist">

                                    <input
                                        type="checkbox"
                                        name="id_categoria[]"
                                        value="<?php echo $categoria["id_categoria"]; ?>"
                                    >

                                    <span>
                                        <?php echo htmlspecialchars($categoria["nombre"]); ?>
                                    </span>

                                </label>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <p class="sin-resultados">
                                No hay categorías activas disponibles.
                            </p>

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
                            required
                        >

                    </div>

                    <div class="campo">

                        <label>Fecha de finalización</label>

                        <input
                            type="date"
                            name="fecha_fin"
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

                    <option value="1">Activa</option>

                    <option value="0">Inactiva</option>

                </select>

                <br><br>

                <!-- =================================
                     BOTONES
                ================================== -->

                <button type="submit"
                    class="btn-nuevo">💾 Guardar Promoción</button>

                <a href="promociones.php"
                    class="btn-cancelar">Cancelar</a>

            </form>

        </div>

    </main>

</div>


<!-- =================================
     JAVASCRIPT
================================== -->

<script src="../dashboard/dashboard.js"></script>


<script>

// =================================
// MOSTRAR PRODUCTOS O CATEGORÍAS
// =================================

const aplicaA = document.getElementById("aplica_a");

const campoProducto = document.getElementById("campo-producto");

const campoCategoria = document.getElementById("campo-categoria");


aplicaA.addEventListener("change", function(){

    campoProducto.style.display = "none";
    campoCategoria.style.display = "none";


    if (this.value === "producto") {

        campoProducto.style.display = "block";

    }


    if (this.value === "categoria") {

        campoCategoria.style.display = "block";

    }

});


// =================================
// BUSCADOR DE PRODUCTOS
// =================================

const buscarProducto = document.getElementById("buscar-producto");

const listaProductos = document.getElementById("lista-productos");


buscarProducto.addEventListener("input", function(){

    const texto = this.value.toLowerCase().trim();

    const productos = listaProductos.querySelectorAll(
        ".item-checklist"
    );


    productos.forEach(function(producto){

        const nombre = producto
            .textContent
            .toLowerCase();

        if (nombre.includes(texto)) {

            producto.style.display = "flex";

        } else {

            producto.style.display = "none";

        }

    });

});


// =================================
// BUSCADOR DE CATEGORÍAS
// =================================

const buscarCategoria = document.getElementById("buscar-categoria");

const listaCategorias = document.getElementById("lista-categorias");


buscarCategoria.addEventListener("input", function(){

    const texto = this.value.toLowerCase().trim();

    const categorias = listaCategorias.querySelectorAll(
        ".item-checklist"
    );


    categorias.forEach(function(categoria){

        const nombre = categoria
            .textContent
            .toLowerCase();

        if (nombre.includes(texto)) {

            categoria.style.display = "flex";

        } else {

            categoria.style.display = "none";

        }

    });

});

</script>
</body>
</html>