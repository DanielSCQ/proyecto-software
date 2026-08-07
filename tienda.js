// ===== CARRITO =====
const Carrito = {
  obtener() {
    return JSON.parse(localStorage.getItem("carrito")) || [];
  },

  guardar(carrito) {
    localStorage.setItem("carrito", JSON.stringify(carrito));
  },

  agregar(nombre, precio, imagen) {
    let carrito = this.obtener();

    const existe = carrito.find(p => p.nombre === nombre);

    if (existe) {
      existe.cantidad++;
    } else {
      carrito.push({ nombre, precio, imagen, cantidad: 1 });
    }

    this.guardar(carrito);
    UI.actualizarContador();
  }
};

// ===== UI =====
const UI = {
  actualizarContador() {
    const contador = document.getElementById("cart-count");
    if (!contador) return;

    let carrito = Carrito.obtener();

    let totalItems = carrito.reduce((acc, p) => acc + p.cantidad, 0);

    contador.innerText = totalItems;
  }
};

// ===== EVENTOS =====
document.addEventListener("click", function(e) {

  if (e.target.classList.contains("btn-agregar")) {

    const card = e.target.closest(".producto");

    const nombre = card.querySelector(".nombre").innerText;
    const precio = parseInt(card.querySelector(".precio").innerText);
    const imagen = card.querySelector("img").src;

    Carrito.agregar(nombre, precio, imagen);
  }

});

// ===== INICIO =====
document.addEventListener("DOMContentLoaded", () => {
  UI.actualizarContador();
  mostrarCarrito();

  // ===== CATEGORÍA DESDE URL =====
  const params = new URLSearchParams(window.location.search);
  const categoria = params.get("cat");

  if (categoria) {
    filtrarCategoria(categoria);
  }
});


// ===== mostrar los productos =====
function mostrarCarrito() {
  const contenedor = document.getElementById("carrito-items");
  const vacio = document.getElementById("carrito-vacio");
  if (!contenedor) return;

  let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
  

  contenedor.innerHTML = "";

  let total = 0;

  const resumen = document.getElementById("carrito-resumen");

if (carrito.length === 0) {
  vacio.style.display = "flex";     // 👈 importante
  contenedor.style.display = "none";
  resumen.style.display = "none";   // 👈 clave
} else {
  vacio.style.display = "none";
  contenedor.style.display = "flex";
  resumen.style.display = "block";
}

  carrito.forEach((producto, index) => {
    total += producto.precio * producto.cantidad;

    contenedor.innerHTML += `
  <div class="carrito-item">

    <img src="${producto.imagen}" class="img-carrito">

    <div class="item-info">
      <h4>${producto.nombre}</h4>
      <p>$${producto.precio}</p>
      <p>Cantidad: ${producto.cantidad}</p>

    <div class="item-controls">

        <button onclick="cambiarCantidad(${index}, -1)">−</button>
        <span>${producto.cantidad}</span>
        <button onclick="cambiarCantidad(${index}, 1)">+</button>
      </div>
    </div>

    <button class="btn-eliminar" onclick="eliminarProducto(${index})">ELIMINAR🗑️</button>

  </div>
`;

// ===== cambiar cantidad =====
window.cambiarCantidad = function(index, cambio) {
  let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

  carrito[index].cantidad += cambio;

  if (carrito[index].cantidad <= 0) {
    carrito.splice(index, 1);
  }

  localStorage.setItem("carrito", JSON.stringify(carrito));

  mostrarCarrito();
  UI.actualizarContador();
};
  });

  const totalHTML = document.getElementById("total");
  if (totalHTML) {
    totalHTML.innerText = total;
  }
}
function eliminarProducto(index) {
  let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

  carrito.splice(index, 1);

  localStorage.setItem("carrito", JSON.stringify(carrito));

  mostrarCarrito();
  UI.actualizarContador();
}

// ===== BUSCADOR =====

// ===== ESTADO =====
let estadoBusqueda = false; // false = buscar, true = limpiar

// ===== BUSCAR PRODUCTOS =====
function filtrarProductos() {
  const input = document.getElementById("buscador").value.toLowerCase().trim();
  const productos = document.querySelectorAll(".producto");
  const mensaje = document.getElementById("mensaje");

  let encontrados = 0;

  function similitud(a, b) {
    let coincidencias = 0;

    for (let i = 0; i < a.length; i++) {
      if (b.includes(a[i])) {
        coincidencias++;
      }
    }

    return coincidencias / a.length;
  }

  productos.forEach(producto => {
    const nombre = producto.querySelector(".nombre").innerText.toLowerCase();

    if (input === "") {
      producto.style.display = "";
      encontrados++;
      return;
    }

    const score = similitud(input, nombre);

    if (score > 0.6) {
      producto.style.display = "";
      encontrados++;
    } else {
      producto.style.display = "none";
    }
  });

  mensaje.style.display = (encontrados === 0 && input !== "") ? "block" : "none";

  if (input !== "") {
    cambiarBoton(true);
  } else {
    cambiarBoton(false);
  }
}


// ===== BOTÓN BUSCAR / LIMPIAR =====
function accionBusqueda() {
  if (!estadoBusqueda) {
    filtrarProductos();
    cambiarBoton(true);
  } else {
    limpiarBusqueda();
    cambiarBoton(false);
  }
}

// ===== LIMPIAR =====
function limpiarBusqueda() {
  document.getElementById("buscador").value = "";

  const productos = document.querySelectorAll(".producto");

  productos.forEach(p => {
    p.style.display = "";
    p.style.opacity = "1";
    p.style.transform = "scale(1)";
  });

  document.getElementById("mensaje").style.display = "none";
}

// ===== CAMBIAR BOTÓN =====
function cambiarBoton(estado) {
  const btn = document.getElementById("btnBuscar");

  if (estado) {
    btn.innerText = "Limpiar";
    btn.style.backgroundColor = "#e74c3c";
    estadoBusqueda = true;
  } else {
    btn.innerText = "Buscar";
    btn.style.backgroundColor = "";
    estadoBusqueda = false;
  }
}

// ===== ENTER =====
document.getElementById("buscador").addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    filtrarProductos();
  }
});

// ===== SI BORRA TEXTO =====
document.getElementById("buscador").addEventListener("input", function() {
  const valor = this.value.trim();

  if (valor === "") {
    cambiarBoton(false);
    limpiarBusqueda();
  }
});

//=====inspeccionar=====
document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const categoria = params.get("cat");

  if (categoria) {
    filtrarCategoria(categoria);
  }
});

function filtrarCategoria(categoria) {
  const productos = document.querySelectorAll(".producto");

  productos.forEach(producto => {
    const cat = producto.dataset.categoria;

    if (cat === categoria) {
      producto.style.display = "";
    } else {
      producto.style.display = "none";
    }
  });
}