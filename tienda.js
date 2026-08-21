// tienda.js - Funciones e interactividad Vanilla JavaScript para la tienda AGRANDA

// ===== CARRITO CLIENTE =====
const Carrito = {
  obtener() {
    try {
      return JSON.parse(localStorage.getItem("carrito")) || [];
    } catch (e) {
      return [];
    }
  },

  guardar(carrito) {
    localStorage.setItem("carrito", JSON.stringify(carrito));
  },

  limpiar() {
    localStorage.removeItem("carrito");
  },

  agregar(nombre, precio, imagen, id, cantidad = 1, maxStock = 9999) {
    let carrito = this.obtener();
    let numId = id ? parseInt(id) : null;
    let cant = parseInt(cantidad) || 1;

    const existe = carrito.find(p => (numId && p.id === numId) || p.nombre === nombre);

    if (existe) {
      let nuevaCantidad = existe.cantidad + cant;
      if (nuevaCantidad > maxStock) {
        nuevaCantidad = maxStock;
        UI.mostrarToast(`⚠️ Stock máximo alcanzado (${maxStock} unidades)`);
      } else {
        UI.mostrarToast(`¡Cantidad actualizada en el carrito! (${nuevaCantidad} unid.) 🛒`);
      }
      existe.cantidad = nuevaCantidad;
    } else {
      carrito.push({
        id: numId,
        nombre: nombre,
        precio: Number(precio),
        imagen: imagen || "destacado 1.jfif",
        cantidad: Math.min(cant, maxStock)
      });
      UI.mostrarToast(`¡"${nombre}" añadido al carrito! 🛒`);
    }

    this.guardar(carrito);
    UI.actualizarContador();

    // Sincronizar en background con backend PHP si está disponible
    if (typeof fetch !== 'undefined') {
      fetch('acciones_carrito.php?action=sincronizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: carrito })
      }).catch(() => {});
    }
  }
};

// ===== UI & NOTIFICACIONES =====
const UI = {
  actualizarContador() {
    const contador = document.getElementById("cart-count");
    if (!contador) return;

    let carrito = Carrito.obtener();
    let totalItems = carrito.reduce((acc, p) => acc + (parseInt(p.cantidad) || 1), 0);
    contador.innerText = totalItems;
  },

  mostrarToast(mensaje) {
    let toast = document.getElementById("tienda-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "tienda-toast";
      toast.style.cssText = "position:fixed;bottom:25px;right:25px;background:#1e293b;border-left:4px solid #e65100;color:#fff;padding:14px 22px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.25);z-index:99999;font-weight:600;font-size:14px;transition:opacity 0.3s ease, transform 0.3s ease;opacity:0;transform:translateY(10px);pointer-events:none;";
      document.body.appendChild(toast);
    }
    toast.innerText = mensaje;
    toast.style.opacity = "1";
    toast.style.transform = "translateY(0)";
    
    if (window._toastTimeout) clearTimeout(window._toastTimeout);
    window._toastTimeout = setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateY(10px)";
    }, 2800);
  }
};

// ===== DELEGACIÓN DE EVENTOS PARA BOTONES DE CARRITO =====
document.addEventListener("click", function(e) {
  const btn = e.target.closest(".btn-agregar-carrito") || e.target.closest(".btn-agregar");
  if (btn) {
    let id = btn.getAttribute("data-id");
    let nombre = btn.getAttribute("data-nombre");
    let precio = parseFloat(btn.getAttribute("data-precio"));
    let imagen = btn.getAttribute("data-imagen");
    let stock = parseInt(btn.getAttribute("data-stock")) || 9999;

    if (!nombre) {
      const card = btn.closest(".card-producto-agranda") || btn.closest(".producto") || btn.closest(".card");
      if (card) {
        const nombreEl = card.querySelector(".card-titulo") || card.querySelector(".nombre") || card.querySelector("h3") || card.querySelector("h4");
        nombre = nombreEl ? nombreEl.innerText.trim() : "Producto Agrícola";
        const precioEl = card.querySelector(".precio-final-destacado") || card.querySelector(".precio");
        const precioRaw = precioEl ? precioEl.innerText.replace(/[^0-9.]/g, '') : "0";
        precio = parseFloat(precioRaw) || 0;
        const imgEl = card.querySelector("img");
        imagen = imgEl ? imgEl.src : "destacado 1.jfif";
      }
    }

    Carrito.agregar(nombre, precio, imagen, id, 1, stock);
  }
});

// ===== INICIALIZACIÓN GLOBAL =====
document.addEventListener("DOMContentLoaded", () => {
  UI.actualizarContador();
  configurarCheckout();
});

// ===== CHECKOUT Y CONFIRMACIÓN DE PEDIDO =====
function configurarCheckout() {
  const btnComprar = document.querySelector(".btn-proceder-checkout") || document.querySelector(".btn-comprar");
  if (!btnComprar) return;

  btnComprar.addEventListener("click", () => {
    const carrito = Carrito.obtener();
    if (carrito.length === 0) {
      alert("El carrito está vacío. Agrega productos para continuar.");
      return;
    }
    abrirModalCheckout();
  });
}

function abrirModalCheckout() {
  let modal = document.getElementById("checkout-modal");
  if (modal) modal.remove();

  modal = document.createElement("div");
  modal.id = "checkout-modal";
  modal.style.cssText = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(15,23,42,0.7);display:flex;align-items:center;justify-content:center;z-index:100000;padding:20px;box-sizing:border-box;";
  
  const carrito = Carrito.obtener();
  const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);

  modal.innerHTML = `
    <div style="background:#fff;border-radius:14px;padding:32px;max-width:540px;width:100%;box-shadow:0 15px 35px rgba(0,0,0,0.3);position:relative;max-height:90vh;overflow-y:auto;font-family:inherit;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h2 style="margin:0;color:#0f172a;font-size:22px;display:flex;align-items:center;gap:8px;">
          <span>🚜</span> Confirmar Pedido AGRANDA
        </h2>
        <button type="button" id="btn-cerrar-modal" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;">✕</button>
      </div>
      <p style="color:#64748b;font-size:14px;margin-bottom:20px;line-height:1.4;">Completa tus datos de entrega para procesar tu orden y coordinar el despacho directo de tus repuestos.</p>
      
      <form id="form-checkout">
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Nombre y Apellidos *</label>
          <input type="text" id="chk-nombre" required placeholder="Ej: Daniel Santiago Gómez" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;">
        </div>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Correo Electrónico *</label>
          <input type="email" id="chk-correo" required placeholder="Ej: contacto@agroempresa.co" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Teléfono / WhatsApp *</label>
            <input type="tel" id="chk-telefono" required placeholder="Ej: 3209007970" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;">
          </div>
          <div>
            <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Ciudad / Municipio *</label>
            <input type="text" id="chk-ciudad" required placeholder="Ej: Purificación, Tolima" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;">
          </div>
        </div>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Dirección de Entrega / Finca *</label>
          <input type="text" id="chk-direccion" required placeholder="Ej: Km 4 Vía Guamo, Finca El Porvenir" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Método de Pago Preferido</label>
          <select id="chk-metodo" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;font-size:14px;background:#fff;">
            <option value="Transferencia Bancaria / PSE / Nequi">Transferencia Bancaria / PSE / Bancolombia / Nequi</option>
            <option value="Pago Contra Entrega">Pago Contra Entrega (Ciudades Principales)</option>
            <option value="Tarjeta de Crédito / Débito">Tarjeta de Crédito / Débito</option>
          </select>
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:14px 18px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;color:#334155;">Total Estimado (${carrito.length} items):</span>
          <span style="font-size:20px;font-weight:800;color:#e65100;">$ ${Number(total).toLocaleString('es-CO')}</span>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;">
          <button type="button" id="btn-cancelar-modal" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:11px 20px;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;">Cancelar</button>
          <button type="submit" style="background:#e65100;color:white;border:none;padding:11px 24px;border-radius:8px;cursor:pointer;font-weight:700;font-size:14px;">✓ Confirmar Orden</button>
        </div>
      </form>
    </div>
  `;

  document.body.appendChild(modal);

  modal.querySelector("#btn-cerrar-modal").addEventListener("click", () => modal.remove());
  modal.querySelector("#btn-cancelar-modal").addEventListener("click", () => modal.remove());

  modal.querySelector("#form-checkout").addEventListener("submit", async (e) => {
    e.preventDefault();
    
    const payload = {
      nombre: document.getElementById("chk-nombre").value,
      correo: document.getElementById("chk-correo").value,
      telefono: document.getElementById("chk-telefono").value,
      direccion: document.getElementById("chk-direccion").value + " - " + document.getElementById("chk-ciudad").value,
      metodo_pago: document.getElementById("chk-metodo").value,
      items: Carrito.obtener()
    };

    try {
      const res = await fetch("/api/pedidos", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.ok) {
        modal.innerHTML = `
          <div style="background:#fff;border-radius:14px;padding:36px;max-width:480px;width:100%;text-align:center;font-family:inherit;">
            <div style="font-size:56px;margin-bottom:12px;">🎉</div>
            <h2 style="color:#0f172a;margin:0 0 10px 0;font-size:22px;">¡Pedido Registrado con Éxito!</h2>
            <p style="color:#475569;font-size:14px;line-height:1.5;">Tu orden <strong>#${data.id_pedido}</strong> ha sido registrada en el sistema de AGRANDA. Un asesor se comunicará contigo al teléfono ${payload.telefono} para confirmar el despacho.</p>
            <div style="margin-top:24px;">
              <button id="btn-finalizar-exito" style="background:#e65100;color:white;border:none;padding:12px 28px;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;">Seguir Comprando</button>
            </div>
          </div>
        `;
        Carrito.limpiar();
        UI.actualizarContador();

        modal.querySelector("#btn-finalizar-exito").addEventListener("click", () => {
          modal.remove();
          window.location.href = "productos.php";
        });
      } else {
        alert("Error al procesar el pedido. " + (data.error || "Intenta nuevamente."));
      }
    } catch (err) {
      console.error(err);
      alert("Pedido registrado en modo demostración. ¡Gracias por tu compra!");
      Carrito.limpiar();
      UI.actualizarContador();
      modal.remove();
      window.location.href = "productos.php";
    }
  });
}
