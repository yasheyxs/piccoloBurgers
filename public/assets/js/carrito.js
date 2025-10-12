const DEBUG_CARRITO = false;

const estadoTotalCarrito = {
  items: {},
  total: 0,
  usarPuntos: null,
  disponibilidad: {},
};

let timeoutGuardarCarrito = null;

const reservasAPI = window.CarritoReservas || null;

function normalizarClaveProducto(id) {
  if (id === null || typeof id === "undefined") {
    return "";
  }

  const numero = Number(id);
  if (Number.isFinite(numero)) {
    return String(numero);
  }

  if (typeof id === "string") {
    const valor = id.trim();
    return valor;
  }

  return String(id);
}

function normalizarMapaDisponibilidad(disponibilidad) {
  const resultado = {};

  if (!disponibilidad) {
    return resultado;
  }

  const procesarEntrada = (entrada, claveFallback) => {
    if (!entrada && entrada !== 0) {
      return;
    }

    const idNormalizado = normalizarClaveProducto(
      entrada?.menu_id ?? entrada?.menuId ?? entrada?.id ?? claveFallback
    );

    if (!idNormalizado) {
      return;
    }

    const unidades = Number(
      entrada?.unidades_disponibles ??
        entrada?.unidadesDisponibles ??
        entrada?.unidades ??
        entrada
    );

    if (!Number.isFinite(unidades)) {
      return;
    }

    resultado[idNormalizado] = Math.max(0, Math.floor(unidades));
  };

  if (Array.isArray(disponibilidad)) {
    disponibilidad.forEach((entrada) => procesarEntrada(entrada));
    return resultado;
  }

  Object.entries(disponibilidad).forEach(([clave, entrada]) =>
    procesarEntrada(entrada, clave)
  );

  return resultado;
}

function actualizarDisponibilidadProductos(disponibilidad) {
  const mapaNormalizado = normalizarMapaDisponibilidad(disponibilidad);
  estadoTotalCarrito.disponibilidad = mapaNormalizado;
}

function obtenerUnidadesDisponiblesProducto(id) {
  const clave = normalizarClaveProducto(id);
  if (!clave) {
    return null;
  }

  if (
    Object.prototype.hasOwnProperty.call(
      estadoTotalCarrito.disponibilidad,
      clave
    )
  ) {
    return estadoTotalCarrito.disponibilidad[clave];
  }
  return null;
}

function puedeIncrementarseProducto(id) {
  const unidadesDisponibles = obtenerUnidadesDisponiblesProducto(id);
  if (unidadesDisponibles === null) {
    return true;
  }

  return unidadesDisponibles > 0;
}

function manejarErrorReserva(
  error,
  mensajeFallback = "Ocurrió un error al actualizar el carrito."
) {
  console.error(mensajeFallback, error);
  const mensaje = error && error.message ? error.message : mensajeFallback;
  alert(mensaje);
}

function solicitarActualizacionReservas(payload, extras = {}) {
  return fetch("api/carrito_actualizar.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (reservasAPI && typeof reservasAPI.procesarRespuesta === "function") {
        return reservasAPI.procesarRespuesta(data, extras);
      }

      if (!data || typeof data !== "object") {
        throw new Error("Respuesta inválida del servidor.");
      }

      if (!data.exito) {
        throw new Error(data.mensaje || "No se pudo actualizar el carrito.");
      }

      return data;
    })
    .then((resultado) => {
      if (resultado && typeof resultado === "object") {
        if (Object.prototype.hasOwnProperty.call(resultado, "disponibilidad")) {
          actualizarDisponibilidadProductos(resultado.disponibilidad);
        }
      }

      cargarCarrito();
      actualizarContador();
      return resultado;
    });
}

function sincronizarReservasIniciales() {
  if (
    !reservasAPI ||
    typeof reservasAPI.sincronizarConServidor !== "function"
  ) {
    return Promise.resolve();
  }

  return reservasAPI
    .sincronizarConServidor()
    .then((resultado) => {
      if (resultado && typeof resultado === "object") {
        if (Object.prototype.hasOwnProperty.call(resultado, "disponibilidad")) {
          actualizarDisponibilidadProductos(resultado.disponibilidad);
        }
      }

      return resultado;
    })
    .catch((error) => {
      console.error(
        "No se pudo sincronizar el carrito con el servidor:",
        error
      );
    });
}

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
  const unidadesDisponibles = obtenerUnidadesDisponiblesProducto(producto.id);
  const puedeIncrementar = puedeIncrementarseProducto(producto.id);
  const disponibilidadAttr =
    unidadesDisponibles === null
      ? ""
      : ` data-unidades-disponibles="${Math.max(0, unidadesDisponibles)}"`;

  return `
      <div class="col d-flex" data-aos="fade-up" data-producto-id="${
        producto.id
      }">
        <div class="card position-relative d-flex flex-column h-100 w-100"${disponibilidadAttr}>
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
              <button type="button" class="btn btn-secondary js-btn-disminuir" onclick="disminuirCantidad(${
                producto.id
              })">-</button>
              <button type="button" class="btn btn-secondary js-btn-incrementar${
                puedeIncrementar ? "" : " btn-disabled"
              }" onclick="aumentarCantidad(${producto.id})"${
    puedeIncrementar ? "" : " disabled"
  }${
    unidadesDisponibles === null
      ? ""
      : ` data-unidades-disponibles="${Math.max(0, unidadesDisponibles)}"`
  }>+</button>
              <button type="button" class="btn btn-danger js-btn-eliminar" onclick="eliminarProducto(${
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

  const unidadesDisponibles = obtenerUnidadesDisponiblesProducto(producto.id);
  if (unidadesDisponibles === null) {
    delete tarjeta.dataset.unidadesDisponibles;
  } else {
    tarjeta.dataset.unidadesDisponibles = String(Math.max(0, unidadesDisponibles));
  }

  const cantidadSpan = tarjeta.querySelector(".js-cantidad");
  const subtotalSpan = tarjeta.querySelector(".js-subtotal");
  const precioUnitarioSpan = tarjeta.querySelector(".js-precio-unitario");
  const botonIncrementar = tarjeta.querySelector(".js-btn-incrementar");

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

  if (botonIncrementar) {
    const puedeIncrementar = puedeIncrementarseProducto(producto.id);
    botonIncrementar.disabled = !puedeIncrementar;
    botonIncrementar.classList.toggle("btn-disabled", !puedeIncrementar);

    if (unidadesDisponibles === null) {
      botonIncrementar.removeAttribute("data-unidades-disponibles");
    } else {
      botonIncrementar.dataset.unidadesDisponibles = String(
        Math.max(0, unidadesDisponibles)
      );
    }
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
  const usarPuntosCheckbox = document.getElementById("usarPuntos");


  if (finalizar) {
    finalizar.disabled = !hayProductos;
  }
  if (cancelar) {
    cancelar.disabled = !hayProductos;
  }

  if (usarPuntosCheckbox) {
    const wrapper = usarPuntosCheckbox.closest(".usar-puntos-wrapper");
    const habilitadoBase =
      usarPuntosCheckbox.dataset.habilitadoBase !== "0";

    if (!hayProductos) {
      if (usarPuntosCheckbox.checked) {
        usarPuntosCheckbox.checked = false;
      }
      if (!usarPuntosCheckbox.disabled) {
        usarPuntosCheckbox.disabled = true;
      }
      if (wrapper) {
        wrapper.classList.add("is-disabled");
      }
    } else if (habilitadoBase) {
      if (usarPuntosCheckbox.disabled) {
        usarPuntosCheckbox.disabled = false;
      }
      if (wrapper) {
        wrapper.classList.remove("is-disabled");
      }
    }
  }
}

function mostrarMensajeCarritoVacio() {
  const contenedor = document.getElementById("carrito-contenido");
  if (contenedor) {
    contenedor.innerHTML = `
      <div class="carrito-empty-message text-center">
        <div class="carrito-empty-icon">
          <i class="fas fa-shopping-basket" aria-hidden="true"></i>
        </div>
        <p class="carrito-empty-title mb-1">Tu carrito está vacío</p>
        <p class="carrito-empty-subtitle mb-0">
          Agregá algo delicioso desde el menú para comenzar tu pedido.
        </p>
      </div>
    `;
  }
}

function cargarCarrito() {
  const contenedor = document.getElementById("carrito-contenido");
  const totalSpan = document.getElementById("total");

  if (!contenedor || !totalSpan) {
    return 0;
  }

  const items =
    reservasAPI && typeof reservasAPI.leerCarritoDesdeStorage === "function"
      ? reservasAPI.leerCarritoDesdeStorage()
      : leerCarritoDesdeStorage();
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

  if (!puedeIncrementarseProducto(id)) {
    return;
  }


  const extras = {};
  extras[clave] = entrada.producto;

  solicitarActualizacionReservas(
    { action: "increment", menuId: Number(id) },
    extras
  ).catch((error) =>
    manejarErrorReserva(
      error,
      "No se pudo agregar otra unidad. Verificá la disponibilidad de stock."
    )
  );
}

function disminuirCantidad(id) {
  const clave = String(id);
  const entrada = estadoTotalCarrito.items[clave];
  if (!entrada) {
    return;
  }

  solicitarActualizacionReservas({
    action: "decrement",
    menuId: Number(id),
  }).catch((error) =>
    manejarErrorReserva(error, "No se pudo quitar la unidad seleccionada.")
  );
}

function eliminarProducto(id) {
  const clave = String(id);
  const entrada = estadoTotalCarrito.items[clave];
  if (!entrada) {
    return;
  }
  const extras = {};
  extras[clave] = entrada.producto;

  solicitarActualizacionReservas(
    { action: 'remove', menuId: Number(id) },
    extras
  ).catch((error) =>
    manejarErrorReserva(error, "No se pudo eliminar el producto del carrito.")
  );
}

function vaciarCarrito() {
  solicitarActualizacionReservas({ action: "clear" }).catch((error) =>
    manejarErrorReserva(error, "No se pudo vaciar el carrito por completo.")
  );
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
  let usarPuntos = Boolean(estadoTotalCarrito.usarPuntos);

  if (usarPuntosCheckbox) {
    if (usarPuntosCheckbox.disabled) {
      if (usarPuntosCheckbox.checked) {
        usarPuntosCheckbox.checked = false;
      }
      usarPuntos = false;
    } else {
      usarPuntos = Boolean(usarPuntosCheckbox.checked);
    }
  }
  if (estadoTotalCarrito.usarPuntos !== usarPuntos) {
    localStorage.setItem("usar_puntos_activado", usarPuntos ? "1" : "0");
  }

  estadoTotalCarrito.usarPuntos = usarPuntos;

  const puntosDisponiblesDato =
    usarPuntosCheckbox?.dataset?.puntosDisponibles ??
    document.getElementById("puntosDisponibles")?.value ??
    "0";
  const puntosDisponiblesParseado = Number.parseInt(puntosDisponiblesDato, 10);
  const puntosDisponibles = Number.isNaN(puntosDisponiblesParseado)
    ? 0
    : puntosDisponiblesParseado;

  let descuento = 0;

  document.getElementById("puntos_usados")?.remove();
  document.getElementById("puntos_warning")?.remove();

  if (usarPuntos && total > 0) {
    const valorPorPunto = 20;
    const minimoParaCanjearDato =
      usarPuntosCheckbox?.dataset?.minimoPuntos ?? "50";
    const minimoParaCanjearParseado = Number.parseInt(
      minimoParaCanjearDato,
      10
    );
    const minimoParaCanjear = Number.isNaN(minimoParaCanjearParseado)
      ? 50
      : minimoParaCanjearParseado;
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
    detalleDiv.innerHTML = `
      <div class="carrito-empty-message carrito-empty-message--compact text-center">
        <div class="carrito-empty-icon">
          <i class="fas fa-shopping-basket" aria-hidden="true"></i>
        </div>
        <p class="carrito-empty-title mb-1">Tu carrito está vacío</p>
        <p class="carrito-empty-subtitle mb-0">
          Podés seguir explorando el menú antes de cancelar.
        </p>
      </div>
    `;
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
  solicitarActualizacionReservas({ action: "clear" })
    .then(() => {
      const modalElement = document.getElementById("modalCancelarPedido");
      if (modalElement && typeof bootstrap !== "undefined") {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
          modal.hide();
        }
      }
    })
    .catch((error) =>
      manejarErrorReserva(
        error,
        "No se pudo cancelar el pedido en este momento."
      )
    );
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
  let usarPuntosInicial = usarPuntosGuardado;

  if (usarPuntosCheckbox) {
    const minimoPuntosParseado = Number.parseInt(
      usarPuntosCheckbox.dataset.minimoPuntos || "50",
      10
    );
    const minimoPuntos = Number.isNaN(minimoPuntosParseado)
      ? 50
      : minimoPuntosParseado;
    const puntosDisponiblesParseado = Number.parseInt(
      usarPuntosCheckbox.dataset.puntosDisponibles ||
        document.getElementById("puntosDisponibles")?.value ||
        "0",
      10
    );
    const puntosDisponibles = Number.isNaN(puntosDisponiblesParseado)
      ? 0
      : puntosDisponiblesParseado;
    const wrapper = usarPuntosCheckbox.closest(".usar-puntos-wrapper");
    const puntosSuficientes = puntosDisponibles >= minimoPuntos;
    usarPuntosCheckbox.dataset.habilitadoBase = puntosSuficientes ? "1" : "0";

    if (!puntosSuficientes) {
      usarPuntosInicial = false;
      usarPuntosCheckbox.checked = false;
      usarPuntosCheckbox.disabled = true;
      if (wrapper) {
        wrapper.classList.add("is-disabled");
      }
      localStorage.setItem("usar_puntos_activado", "0");
    } else {
      usarPuntosCheckbox.checked = usarPuntosGuardado;
      usarPuntosCheckbox.addEventListener("change", actualizarTotal);
    }
  }

  estadoTotalCarrito.usarPuntos = usarPuntosInicial;

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

  sincronizarReservasIniciales()
    .catch(() => {})
    .finally(() => {
      const totalFinal = cargarCarrito();
      const totalSpan = document.getElementById("total");
      if (totalSpan && Number.isFinite(totalFinal)) {
        totalSpan.dataset.totalFinal = totalFinal.toFixed(2);
      }
      actualizarContador();
      ajustarPaddingContenido();
    });
});
