const DEBUG_CARRITO = false;

const estadoTotalCarrito = {
  items: {},
  total: 0,
  usarPuntos: null,
};

let timeoutGuardarCarrito = null;

function obtenerPrecioUnitario(item = {}) {
  if (typeof item !== "object" || item === null) {
    return 0;
  }

  const precioUnitarioNumerico = Number(item.precioUnitario);
  if (Number.isFinite(precioUnitarioNumerico)) {
    return precioUnitarioNumerico;
  }

  const precio = Number(item.precio);
  if (Number.isFinite(precio)) {
    return precio;
  }

  const producto = item.producto;
  if (producto && typeof producto === "object") {
    return obtenerPrecioUnitario(producto);
  }

  return 0;
}

function crearEntradaCarrito(producto = {}, cantidad = 0) {
  const precioUnitario = obtenerPrecioUnitario(producto);
  return {
    producto: {
      id: producto.id,
      nombre: producto.nombre,
      img: producto.img,
      precioUnitario,
    },
    cantidad: Number.isFinite(Number(cantidad))
      ? Math.max(0, Number(cantidad))
      : 0,
  };
}

function normalizarCarrito(data) {
  const resultado = {};

  if (!data) {
    return resultado;
  }

  if (Array.isArray(data)) {
    data.forEach((item) => {
      if (!item) {
        return;
      }

      const id = String(item.id ?? item?.producto?.id ?? "");
      if (!id) {
        return;
      }

      if (!resultado[id]) {
        resultado[id] = crearEntradaCarrito(item, 0);
      }

      resultado[id].cantidad += 1;
    });
    return resultado;
  }

  Object.values(data).forEach((entrada) => {
    if (!entrada) {
      return;
    }

    const producto = entrada.producto || entrada;
    const id = String(producto.id ?? "");
    if (!id) {
      return;
    }

    const cantidad = Number(entrada.cantidad ?? producto.cantidad ?? 0);
    resultado[id] = crearEntradaCarrito(producto, Math.max(0, cantidad));
  });

  return resultado;
}

function obtenerCantidadTotalCarrito() {
  return Object.values(estadoTotalCarrito.items).reduce(
    (total, item) => total + Number(item?.cantidad || 0),
    0
  );
}

function formatearProductoParaUI(entrada) {
  if (!entrada || !entrada.producto) {
    return null;
  }

  const precioUnitario = Number(
    entrada.producto.precioUnitario ?? entrada.producto.precio ?? 0
  );
  const cantidad = Number(entrada.cantidad || 0);
  const subtotal = Math.max(
    0,
    cantidad * (Number.isFinite(precioUnitario) ? precioUnitario : 0)
  );

  return {
    id: entrada.producto.id,
    nombre: entrada.producto.nombre,
    img: entrada.producto.img,
    cantidad,
    precio: subtotal,
    precioUnitario: precioUnitario,
  };
}

function obtenerProductosDelEstado() {
  return Object.values(estadoTotalCarrito.items)
    .map(formatearProductoParaUI)
    .filter(Boolean);
}

function leerCarritoDesdeStorage() {
  try {
    const almacenado = JSON.parse(localStorage.getItem("carrito"));
    return normalizarCarrito(almacenado);
  } catch (error) {
    console.error("No se pudo leer el carrito desde localStorage:", error);
    return {};
  }
}

function reconstruirResumenDesdeItems(items) {
  const normalizado = normalizarCarrito(items);
  estadoTotalCarrito.items = normalizado;
  estadoTotalCarrito.total = obtenerProductosDelEstado().reduce(
    (acc, producto) => acc + Number(producto?.precio || 0),
    0
  );
}

function serializarCarritoParaStorage() {
  const resultado = [];

  Object.values(estadoTotalCarrito.items).forEach((entrada) => {
    const productoFormateado = formatearProductoParaUI(entrada);
    if (!productoFormateado) {
      return;
    }

    const base = {
      id: productoFormateado.id,
      nombre: productoFormateado.nombre,
      img: productoFormateado.img,
      precio: productoFormateado.precioUnitario,
    };

    for (let i = 0; i < productoFormateado.cantidad; i += 1) {
      resultado.push(base);
    }
  });
  return resultado;
}

function cancelarGuardadoPendiente() {
  if (timeoutGuardarCarrito) {
    clearTimeout(timeoutGuardarCarrito);
    timeoutGuardarCarrito = null;
  }
}

function guardarCarritoEnStorage(force = false) {
  const ejecutarGuardado = () => {
    cancelarGuardadoPendiente();
    const serializado = serializarCarritoParaStorage();
    if (serializado.length === 0) {
      localStorage.removeItem("carrito");
    } else {
      localStorage.setItem("carrito", JSON.stringify(serializado));
    }
  };

  if (force) {
    ejecutarGuardado();
    return;
  }

  cancelarGuardadoPendiente();
  timeoutGuardarCarrito = setTimeout(ejecutarGuardado, 500);
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
  const hayProductos = obtenerCantidadTotalCarrito() > 0;

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
  const entrada = estadoTotalCarrito.items[clave];
  if (!entrada) {
    return;
  }

  entrada.cantidad += 1;
  estadoTotalCarrito.total += obtenerPrecioUnitario(entrada.producto);

  guardarCarritoEnStorage();

  const productoFormateado = formatearProductoParaUI(entrada);
  if (productoFormateado) {
    actualizarTarjetaProducto(productoFormateado);
  }

  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function disminuirCantidad(id) {
  const clave = String(id);
  const entrada = estadoTotalCarrito.items[clave];
  if (!entrada) {
    return;
  }

  const precioUnitario = obtenerPrecioUnitario(entrada.producto);
  entrada.cantidad = Math.max(0, entrada.cantidad - 1);
  estadoTotalCarrito.total = Math.max(
    0,
    estadoTotalCarrito.total - precioUnitario
  );

  if (entrada.cantidad <= 0) {
    delete estadoTotalCarrito.items[clave];
    eliminarTarjetaProducto(id);
  } else {
    const productoFormateado = formatearProductoParaUI(entrada);
    if (productoFormateado) {
      actualizarTarjetaProducto(productoFormateado);

    }
  }

  guardarCarritoEnStorage();

  if (obtenerCantidadTotalCarrito() === 0) {
    mostrarMensajeCarritoVacio();
  }

  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function eliminarProducto(id) {
  const clave = String(id);
  const entrada = estadoTotalCarrito.items[clave];
  if (!entrada) {
    return;
  }

  estadoTotalCarrito.total = Math.max(
    0,
    estadoTotalCarrito.total -
      Number(formatearProductoParaUI(entrada)?.precio || 0)
  );

  delete estadoTotalCarrito.items[clave];

  guardarCarritoEnStorage();
  eliminarTarjetaProducto(id);

  if (obtenerCantidadTotalCarrito() === 0) {
    mostrarMensajeCarritoVacio();
  }

  actualizarTotal();
  actualizarContador();
  actualizarEstadoBotones();
}

function vaciarCarrito() {
  estadoTotalCarrito.items = {};
  estadoTotalCarrito.total = 0;
  cancelarGuardadoPendiente();
  localStorage.removeItem("carrito");
  mostrarMensajeCarritoVacio();
  actualizarTotal([], 0);
  actualizarContador();
  actualizarEstadoBotones();
}

function actualizarContador() {
  const contador = document.getElementById("contador-carrito");
  if (contador) {
    contador.textContent = obtenerCantidadTotalCarrito();
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
      obtenerCantidadTotalCarrito() === 0;
  }
  const finalizarPorId = document.getElementById("btnFinalizar");
  if (finalizarPorId) {
    finalizarPorId.disabled =
      Boolean(document.getElementById("puntos_warning")) ||
      obtenerCantidadTotalCarrito() === 0;
  }

  return totalFinal;
}

function mostrarConfirmacionCancelar() {
  const detalleDiv = document.getElementById("detallePedidoModal");
  if (!detalleDiv) {
    return;
  }

  const productos = obtenerProductosDelEstado();

  if (productos.length === 0) {
    detalleDiv.innerHTML = "<p class='text-muted'>El carrito está vacío.</p>";
  } else {
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
  estadoTotalCarrito.items = {};
  estadoTotalCarrito.total = 0;
  cancelarGuardadoPendiente();

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
  guardarCarritoEnStorage(true);

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
