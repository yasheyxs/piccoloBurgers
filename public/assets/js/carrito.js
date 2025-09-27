const DEBUG_CARRITO = false;


const estadoTotalCarrito = {
  items: [],
  total: 0,
  productos: new Map(),
  usarPuntos: null,
};

function leerCarritoDesdeStorage() {
  try {
    const almacenado = JSON.parse(localStorage.getItem("carrito"));
    return Array.isArray(almacenado) ? almacenado : [];
  } catch (error) {
    console.error("No se pudo leer el carrito desde localStorage:", error);
    return [];
  }
}
function reconstruirResumenDesdeItems(items) {
  estadoTotalCarrito.items = items.slice();
  estadoTotalCarrito.productos = new Map();
  estadoTotalCarrito.total = 0;

  items.forEach((item) => {
    if (!item) {
      return;
    }

    const key = String(item.id);
    const precioUnitario = Number(item.precio) || 0;
    if (!estadoTotalCarrito.productos.has(key)) {
      estadoTotalCarrito.productos.set(key, {
        id: item.id,
        nombre: item.nombre,

        img: item.img,

        cantidad: 0,
        precio: 0,
        precioUnitario,
      });
    }

    const resumen = estadoTotalCarrito.productos.get(key);
    resumen.cantidad += 1;
    resumen.precio += precioUnitario;
    estadoTotalCarrito.total += precioUnitario;
  });
}

function obtenerProductosDelEstado() {
  return Array.from(estadoTotalCarrito.productos.values());
}

function guardarCarritoEnStorage() {
  if (estadoTotalCarrito.items.length === 0) {
    localStorage.removeItem("carrito");
    return;
  }

  localStorage.setItem("carrito", JSON.stringify(estadoTotalCarrito.items));
}

function crearTarjetaProductoHTML(producto) {
  const subtotal = Number(producto.precio) || 0;
  const cantidad = Number(producto.cantidad) || 1;
  const precioUnitario = Number(producto.precioUnitario) || 0;

  return `
      <div class="col d-flex" data-aos="fade-up" data-producto-id="${
        producto.id
      }">
        <div class="card position-relative d-flex flex-column h-100 w-100">
          <img src="${producto.img}" class="card-img-top" alt="Foto de ${
    producto.nombre
  }">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${producto.nombre}</h5>
            <p class="card-text mb-1"><strong>Precio unitario:</strong> $<span class="js-precio-unitario">${precioUnitario.toFixed(
              2
            )}</span></p>
            <p class="card-text mb-1"><strong>Cantidad:</strong> <span class="js-cantidad">${cantidad}</span></p>
            <p class="card-text"><strong>Subtotal:</strong> $<span class="js-subtotal">${subtotal.toFixed(
              2
            )}</span></p>
            <div class="mt-auto d-flex flex-wrap gap-2">
              <button class="btn btn-secondary" onclick="disminuirCantidad(${
                producto.id
              })">-</button>
              <button class="btn btn-secondary" onclick="aumentarCantidad(${
                producto.id
              })">+</button>
              <button class="btn btn-danger" onclick="eliminarProducto(${
                producto.id
              })">Eliminar</button>
            </div>
          </div>
        </div>
      </div>`;
}

function obtenerTarjetaProducto(id) {
  return document.querySelector(`[data-producto-id="${id}"]`);
}

function actualizarTarjetaProducto(producto) {
  const tarjeta = obtenerTarjetaProducto(producto?.id);
  if (!tarjeta) {
    const contenedor = document.getElementById("carrito-contenido");
    if (contenedor && producto) {
      contenedor.insertAdjacentHTML(
        "beforeend",
        crearTarjetaProductoHTML(producto)
      );
    }
    return;
  }

  const cantidadSpan = tarjeta.querySelector(".js-cantidad");
  const subtotalSpan = tarjeta.querySelector(".js-subtotal");
  const precioUnitarioSpan = tarjeta.querySelector(".js-precio-unitario");

  if (cantidadSpan) {
    cantidadSpan.textContent = Number(producto.cantidad || 0).toString();
  }
  if (subtotalSpan) {
    subtotalSpan.textContent = Number(producto.precio || 0).toFixed(2);
  }
  if (precioUnitarioSpan) {
    precioUnitarioSpan.textContent = Number(
      producto.precioUnitario || 0
    ).toFixed(2);
  }
}

function eliminarTarjetaProducto(id) {
  const tarjeta = obtenerTarjetaProducto(id);
  if (tarjeta) {
    tarjeta.remove();
  }
}

function obtenerBotonesCarrito() {
  return {
    finalizar:
      document.getElementById("btnFinalizar") ||
      document.querySelector('#formPedido button[type="submit"]'),
    cancelar: document.getElementById("btnCancelar"),
  };
}

function actualizarEstadoBotones() {
  const { finalizar, cancelar } = obtenerBotonesCarrito();
  const hayProductos = estadoTotalCarrito.items.length > 0;

  if (finalizar) {
    if (!hayProductos) {
      finalizar.disabled = true;
    }
  }
  if (cancelar) {
    cancelar.disabled = !hayProductos;
  }
}

function mostrarMensajeCarritoVacio() {
  const contenedor = document.getElementById("carrito-contenido");
  if (contenedor) {
    contenedor.innerHTML = "<p class='text-center'>Tu carrito está vacío.</p>";
  }
}

function cargarCarrito() {
  const contenedor = document.getElementById("carrito-contenido");
  const totalSpan = document.getElementById("total");

  if (!contenedor || !totalSpan) {
    return 0;
  }

  const items = leerCarritoDesdeStorage();
  reconstruirResumenDesdeItems(items);

  const productos = obtenerProductosDelEstado();
  if (productos.length === 0) {
    mostrarMensajeCarritoVacio();
    actualizarEstadoBotones();
    return actualizarTotal([], 0);
  }

  const html = productos.map((item) => crearTarjetaProductoHTML(item)).join("");

  contenedor.innerHTML = html;

  actualizarEstadoBotones();
  return actualizarTotal(productos, estadoTotalCarrito.total);
}

function aumentarCantidad(id) {
  const clave = String(id);
  const productoBase = estadoTotalCarrito.items.find(
    (p) => p && String(p.id) === clave
  );
  if (!productoBase) {
    return;
  }

  estadoTotalCarrito.items.push({ ...productoBase });

  const precioUnitario = Number(productoBase.precio) || 0;
  let resumen = estadoTotalCarrito.productos.get(clave);
  if (!resumen) {
    resumen = {
      id: productoBase.id,
      nombre: productoBase.nombre,
      img: productoBase.img,
      cantidad: 0,
      precio: 0,
      precioUnitario,
    };
  }

  resumen.cantidad += 1;
  resumen.precio += precioUnitario;
  resumen.precioUnitario = precioUnitario;
  estadoTotalCarrito.productos.set(clave, resumen);
  estadoTotalCarrito.total += precioUnitario;

  guardarCarritoEnStorage();
  actualizarTarjetaProducto(resumen);
  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function disminuirCantidad(id) {
  const clave = String(id);
  const index = estadoTotalCarrito.items.findIndex(
    (p) => p && String(p.id) === clave
  );
  if (index === -1) {
    return;
  }

  const [eliminado] = estadoTotalCarrito.items.splice(index, 1);
  const resumen = estadoTotalCarrito.productos.get(clave);

  if (!resumen) {
    reconstruirResumenDesdeItems(estadoTotalCarrito.items);
  } else {
    const precioUnitario =
      Number(resumen.precioUnitario || eliminado?.precio || 0) || 0;
    resumen.cantidad -= 1;
    resumen.precio = Math.max(0, resumen.precio - precioUnitario);
    estadoTotalCarrito.total = Math.max(
      0,
      estadoTotalCarrito.total - precioUnitario
    );

    if (resumen.cantidad <= 0) {
      estadoTotalCarrito.productos.delete(clave);
    } else {
      estadoTotalCarrito.productos.set(clave, resumen);
    }
  }

  guardarCarritoEnStorage();

  const productoActualizado = estadoTotalCarrito.productos.get(clave);
  if (productoActualizado) {
    actualizarTarjetaProducto(productoActualizado);
  } else {
    eliminarTarjetaProducto(id);
  }

  if (estadoTotalCarrito.items.length === 0) {
    mostrarMensajeCarritoVacio();
  }

  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function eliminarProducto(id) {
  const clave = String(id);
  const resumen = estadoTotalCarrito.productos.get(clave);
  if (!resumen) {
    return;
  }

  estadoTotalCarrito.items = estadoTotalCarrito.items.filter(
    (p) => p && String(p.id) !== clave
  );
  estadoTotalCarrito.productos.delete(clave);
  estadoTotalCarrito.total = Math.max(
    0,
    estadoTotalCarrito.total - Number(resumen.precio || 0)
  );

  guardarCarritoEnStorage();
  eliminarTarjetaProducto(id);

  if (estadoTotalCarrito.items.length === 0) {
    mostrarMensajeCarritoVacio();
  }

  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function vaciarCarrito() {
  estadoTotalCarrito.items = [];
  estadoTotalCarrito.productos = new Map();
  estadoTotalCarrito.total = 0;
  localStorage.removeItem("carrito");
  mostrarMensajeCarritoVacio();
  actualizarTotal([], 0);
  actualizarContador();
  actualizarEstadoBotones();
}

function actualizarContador() {
  const contador = document.getElementById("contador-carrito");
  if (contador) {
    contador.textContent = estadoTotalCarrito.items.length;
  }
}

function actualizarTotal(
  items = obtenerProductosDelEstado(),
  total = estadoTotalCarrito.total
) {
  const totalSpan = document.getElementById("total");
  if (!totalSpan) {
    return estadoTotalCarrito.total;
  }

  if (!Array.isArray(items)) {
    items = obtenerProductosDelEstado();
  }

  if (typeof total !== "number" || Number.isNaN(total)) {
    total = items.reduce((acc, item) => acc + Number(item?.precio || 0), 0);
  }
  estadoTotalCarrito.total = total;

  const usarPuntosCheckbox = document.getElementById("usarPuntos");
  const usarPuntos = usarPuntosCheckbox
    ? Boolean(usarPuntosCheckbox.checked)
    : Boolean(estadoTotalCarrito.usarPuntos);
  if (estadoTotalCarrito.usarPuntos !== usarPuntos) {
    localStorage.setItem("usar_puntos_activado", usarPuntos ? "1" : "0");
    estadoTotalCarrito.usarPuntos = usarPuntos;
  }

  const puntosDisponibles = parseInt(
    document.getElementById("puntosDisponibles")?.value || "0",
    10
  );
  let descuento = 0;

  document.getElementById("puntos_usados")?.remove();
  document.getElementById("puntos_warning")?.remove();

  if (usarPuntos && total > 0) {
    const valorPorPunto = 20;
    const minimoParaCanjear = 50;
    const maximoDescuento = total * 0.25;

    if (puntosDisponibles < minimoParaCanjear) {
      totalSpan.insertAdjacentHTML(
        "afterend",
        `<div id="puntos_warning" class="text-danger">⚠️ Necesitás al menos ${minimoParaCanjear} puntos para canjear.</div>`
      );
    } else if (total < 1000) {
      totalSpan.insertAdjacentHTML(
        "afterend",
        '<div id="puntos_warning" class="text-danger">⚠️ El total debe ser al menos $1000 para canjear puntos.</div>'
      );
    } else {
      const puntosPosibles = Math.floor(maximoDescuento / valorPorPunto);
      const puntosAUsar = Math.min(puntosDisponibles, puntosPosibles);
      descuento = puntosAUsar * valorPorPunto;
      if (descuento > 0) {
        totalSpan.insertAdjacentHTML(
          "afterend",
          `<div id="puntos_usados" class="text-success">- $${descuento.toFixed(
            2
          )} aplicados en puntos</div>`
        );
      }
    }
  }

  const totalFinal = Math.max(0, total - descuento);
  totalSpan.textContent = totalFinal.toFixed(2);
  totalSpan.dataset.totalFinal = totalFinal.toFixed(2);

  const finalizarBtn = document.querySelector(
    '#formPedido button[type="submit"]'
  );
  if (finalizarBtn) {
    finalizarBtn.disabled =
      Boolean(document.getElementById("puntos_warning")) ||
      estadoTotalCarrito.items.length === 0;
  }
  const finalizarPorId = document.getElementById("btnFinalizar");
  if (finalizarPorId) {
    finalizarPorId.disabled =
      Boolean(document.getElementById("puntos_warning")) ||
      estadoTotalCarrito.items.length === 0;
  }

  return totalFinal;
}

function mostrarConfirmacionCancelar() {
  const carrito = estadoTotalCarrito.items;
  const detalleDiv = document.getElementById("detallePedidoModal");
  if (!detalleDiv) {
    return;
  }

  if (carrito.length === 0) {
    detalleDiv.innerHTML = "<p class='text-muted'>El carrito está vacío.</p>";
  } else {
    const productos = obtenerProductosDelEstado();

    detalleDiv.innerHTML = `
      <ul class="list-group">
        ${productos
          .map(
            (producto) => `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${producto.nombre} (x${producto.cantidad})</span>
                <span>$${Number(producto.precio || 0).toFixed(2)}</span>
              </li>`
          )
          .join("")}
      </ul>`;
  }

  const modalElement = document.getElementById("modalCancelarPedido");
  if (modalElement && typeof bootstrap !== "undefined") {
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }
}

function confirmarCancelacion() {
  estadoTotalCarrito.items = [];
  estadoTotalCarrito.productos = new Map();
  estadoTotalCarrito.total = 0;

  localStorage.removeItem("carrito");

  const modalElement = document.getElementById("modalCancelarPedido");
  if (modalElement && typeof bootstrap !== "undefined") {
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
      modal.hide();
    }
  }

  mostrarMensajeCarritoVacio();
  actualizarTotal([], 0);
  actualizarContador();
  actualizarEstadoBotones();
}

function prepararEnvioPedido(event) {
  const usarPuntosChecked = document.getElementById("usarPuntos")?.checked;
  const usarPuntosInput = document.getElementById("usarPuntosInput");
  if (usarPuntosInput) {
    usarPuntosInput.value = usarPuntosChecked ? "1" : "0";
  }

  const carritoFinal = obtenerProductosDelEstado().map((producto) => ({
    id: String(producto.id),
    nombre: producto.nombre,
    precio: Number(producto.precio),
    cantidad: Number(producto.cantidad),
  }));

  const hayIncompletos = carritoFinal.some(
    (producto) =>
      !producto.id ||
      !producto.nombre ||
      typeof producto.precio !== "number" ||
      !producto.cantidad
  );

  if (hayIncompletos) {
    console.error("⚠️ Hay productos incompletos:", carritoFinal);
    alert(
      "Error: uno de los productos del carrito no tiene toda la información necesaria."
    );
    event.preventDefault();
    return;
  }

  const carritoInput = document.getElementById("carritoInput");
  if (carritoInput) {
    carritoInput.value = JSON.stringify(carritoFinal);
  }

  console.log("✅ Carrito que se enviará:", carritoFinal);
}

function ajustarPaddingContenido() {
  const navbar = document.querySelector(".navbar");
  const contenido = document.querySelector(".contenido-ajustado");

  if (navbar && contenido) {
    const alturaNavbar = navbar.getBoundingClientRect().height;
    contenido.style.paddingTop = `${alturaNavbar + 24}px`;
  }
}

document.addEventListener("DOMContentLoaded", () => {
if (DEBUG_CARRITO) {
    console.log("Contenido del carrito:", localStorage.getItem("carrito"));
  }
  const usarPuntosCheckbox = document.getElementById("usarPuntos");
  const usarPuntosGuardado =
    localStorage.getItem("usar_puntos_activado") === "1";
  estadoTotalCarrito.usarPuntos = usarPuntosGuardado;

  if (usarPuntosCheckbox) {
    usarPuntosCheckbox.checked = usarPuntosGuardado;
    usarPuntosCheckbox.addEventListener("change", actualizarTotal);
  }

  const cancelarBtn = document.getElementById("btnCancelar");
  if (cancelarBtn) {
    cancelarBtn.addEventListener("click", mostrarConfirmacionCancelar);
  }

  const confirmarBtn = document.getElementById("btnConfirmarCancelacion");
  if (confirmarBtn) {
    confirmarBtn.addEventListener("click", confirmarCancelacion);
  }

  const formPedido = document.getElementById("formPedido");
  if (formPedido) {
    formPedido.addEventListener("submit", prepararEnvioPedido);
  }

  const totalFinal = cargarCarrito();
  const totalSpan = document.getElementById("total");
  if (totalSpan && Number.isFinite(totalFinal)) {
    totalSpan.dataset.totalFinal = totalFinal.toFixed(2);
  }
  actualizarContador();
  ajustarPaddingContenido();
});
