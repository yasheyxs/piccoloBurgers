import { api } from "../api.js";
import { cargarCarrito, carritoActual, fijarCantidad, quitarDelCarrito, vaciarCarrito } from "../carrito.js";
import { iniciarLayout } from "../layout.js";
import {
  calcularCanje,
  cargarConfiguracionPuntos,
  guardarUsarPuntos,
  porcentajeMaximo,
  puedeCanjear,
  quiereUsarPuntos,
} from "../puntos.js";
import { escapar, precio, toast } from "../ui.js";

const IMAGEN_POR_DEFECTO = "img/producto.svg";

const contenedor = document.getElementById("carrito-contenido");
const totalTexto = document.getElementById("total");
const descuentoTexto = document.getElementById("descuento-puntos");
const bloquePuntos = document.getElementById("usar-puntos");
const checkPuntos = document.getElementById("usarPuntos");
const botonContinuar = document.getElementById("btnContinuar");
const botonCancelar = document.getElementById("btnCancelar");
const modalCancelar = document.getElementById("modalCancelarPedido");

let cliente = null;
let configuracionPuntos = null;
let imagenes = new Map();
let cargado = false;

window.addEventListener("carrito:actualizado", () => {
  cargado = true;
  mostrarCarrito();
});

iniciarPagina();
iniciarAcciones();

async function iniciarPagina() {
  const [clienteActual] = await Promise.all([
    iniciarLayout({ botonCarrito: false }),
    cargarCarrito().catch((error) => mostrarErrorDeCarga(error)),
  ]);

  cliente = clienteActual;
  if (cliente) {
    configuracionPuntos = await cargarConfiguracionPuntos().catch(() => null);
    prepararPuntos();
  }

  imagenes = await cargarImagenes(carritoActual().productos);
  mostrarCarrito();
}

/* ---------- Productos del carrito ---------- */

function mostrarCarrito() {
  if (!cargado) return;

  const { productos } = carritoActual();
  if (productos.length === 0) {
    contenedor.innerHTML = `
      <div class="carrito-empty-message text-center w-100">
        <div class="carrito-empty-icon">
          <i class="fas fa-shopping-basket" aria-hidden="true"></i>
        </div>
        <p class="carrito-empty-title mb-1">Tu carrito está vacío</p>
        <p class="carrito-empty-subtitle mb-3">Agregá algo delicioso desde el menú para comenzar tu pedido.</p>
        <a href="index.html#menu" class="btn btn-gold">Ver el menú</a>
      </div>`;
  } else {
    contenedor.innerHTML = productos.map(tarjetaProducto).join("");
  }

  actualizarTotales();
}

function tarjetaProducto(producto) {
  const imagen = imagenes.get(producto.id) || IMAGEN_POR_DEFECTO;
  const sinStock = disponibles(producto.id) <= 0;

  return `
    <div class="col d-flex" data-producto-id="${producto.id}">
      <div class="card position-relative d-flex flex-column h-100 w-100">
        <img src="${escapar(imagen)}" class="card-img-top" alt="Foto de ${escapar(producto.nombre)}" loading="lazy">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">${escapar(producto.nombre)}</h5>
          <p class="card-text mb-1"><strong>Precio unitario:</strong> ${precio(producto.precio)}</p>
          <p class="card-text mb-1"><strong>Cantidad:</strong> ${producto.cantidad}</p>
          <p class="card-text"><strong>Subtotal:</strong> ${precio(producto.subtotal)}</p>
          <div class="mt-auto d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" data-accion="restar" aria-label="Quitar una unidad de ${escapar(producto.nombre)}">-</button>
            <button type="button" class="btn btn-secondary" data-accion="sumar" aria-label="Agregar una unidad de ${escapar(producto.nombre)}"
              ${sinStock ? 'disabled title="No hay más unidades disponibles"' : ""}>+</button>
            <button type="button" class="btn btn-danger" data-accion="eliminar">Eliminar</button>
          </div>
        </div>
      </div>
    </div>`;
}

/** Unidades que todavía se pueden sumar de un producto (sin dato, se permite intentar). */
function disponibles(productoId) {
  const dato = carritoActual().disponibilidad.find((d) => d.productoId === productoId);
  return dato ? dato.disponibles : Infinity;
}

/** El carrito no trae las imágenes: se buscan en el menú solo para los productos que hay. */
async function cargarImagenes(productos) {
  const encontradas = new Map();
  const faltan = new Set(productos.map((p) => p.id));
  let offset = 0;
  let total = Infinity;

  try {
    while (faltan.size > 0 && offset < total) {
      const pagina = await api(`/menu?offset=${offset}&limit=50`);
      for (const producto of pagina.productos) {
        if (faltan.delete(producto.id)) encontradas.set(producto.id, producto.imagen);
      }
      total = pagina.total;
      offset += pagina.productos.length || total;
    }
  } catch {
    // sin imágenes se muestra la imagen genérica
  }
  return encontradas;
}

function mostrarErrorDeCarga(error) {
  contenedor.innerHTML = `
    <p class="estado-vacio w-100">
      <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>${escapar(error.message)}
    </p>`;
}

/* ---------- Totales y puntos ---------- */

function prepararPuntos() {
  bloquePuntos.hidden = false;
  document.getElementById("usar-puntos-etiqueta").textContent = `Usar puntos (${cliente.puntos} disponibles)`;

  const ayuda = document.getElementById("usar-puntos-ayuda");
  if (!configuracionPuntos) {
    ayuda.textContent = "No pudimos consultar el programa de puntos. Probá de nuevo más tarde.";
    checkPuntos.disabled = true;
    bloquePuntos.classList.add("is-disabled");
    return;
  }

  if (puedeCanjear(cliente, configuracionPuntos)) {
    ayuda.innerHTML = `Podés canjear hasta el <strong class="text-gold">${porcentajeMaximo(configuracionPuntos)}</strong> de tu pedido.`;
    checkPuntos.checked = quiereUsarPuntos();
  } else {
    ayuda.textContent = `Necesitás al menos ${configuracionPuntos.minimoPuntos} puntos para canjear.`;
    checkPuntos.disabled = true;
    bloquePuntos.classList.add("is-disabled");
    guardarUsarPuntos(false);
  }
}

function actualizarTotales() {
  const { productos, total } = carritoActual();
  const vacio = productos.length === 0;

  let descuento = 0;
  descuentoTexto.innerHTML = "";

  if (checkPuntos.checked && !checkPuntos.disabled && !vacio) {
    const canje = calcularCanje(total, cliente.puntos, configuracionPuntos);
    descuento = canje.descuento;
    descuentoTexto.innerHTML =
      canje.puntosUsados > 0
        ? `<div class="text-success fw-semibold d-flex align-items-center justify-content-end gap-2 mt-2">
             <i class="fa-solid fa-coins" aria-hidden="true"></i>
             <span>- ${precio(descuento)} aplicados (${canje.puntosUsados} ${canje.puntosUsados === 1 ? "punto" : "puntos"})</span>
           </div>`
        : `<div class="text-warning mt-2">
             <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Tu pedido todavía no alcanza para canjear puntos.
           </div>`;
  }

  totalTexto.textContent = precio(Math.max(0, total - descuento));

  botonContinuar.classList.toggle("disabled", vacio);
  botonContinuar.setAttribute("aria-disabled", String(vacio));
  botonContinuar.tabIndex = vacio ? -1 : 0;
  botonCancelar.disabled = vacio;
}

/* ---------- Acciones ---------- */

function iniciarAcciones() {
  contenedor.addEventListener("click", (evento) => {
    const boton = evento.target.closest("button[data-accion]");
    if (!boton) return;

    const tarjeta = boton.closest("[data-producto-id]");
    const id = Number(tarjeta.dataset.productoId);
    const producto = carritoActual().productos.find((p) => p.id === id);
    if (!producto) return;

    const acciones = {
      sumar: () => fijarCantidad(id, producto.cantidad + 1),
      restar: () => fijarCantidad(id, producto.cantidad - 1),
      eliminar: () => quitarDelCarrito(id),
    };
    modificarProducto(tarjeta, acciones[boton.dataset.accion]);
  });

  checkPuntos.addEventListener("change", () => {
    guardarUsarPuntos(checkPuntos.checked);
    actualizarTotales();
  });

  botonCancelar.addEventListener("click", () => {
    document.getElementById("detallePedidoModal").innerHTML = `
      <ul class="list-group">
        ${carritoActual()
          .productos.map(
            (p) => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>${escapar(p.nombre)} (x${p.cantidad})</span>
              <span>${precio(p.subtotal)}</span>
            </li>`
          )
          .join("")}
      </ul>`;
    bootstrap.Modal.getOrCreateInstance(modalCancelar).show();
  });

  document.getElementById("btnConfirmarCancelacion").addEventListener("click", async (evento) => {
    const boton = evento.currentTarget;
    boton.disabled = true;
    try {
      await vaciarCarrito();
      bootstrap.Modal.getInstance(modalCancelar)?.hide();
      toast("Cancelaste el pedido. Tu carrito quedó vacío.", "info");
    } catch (error) {
      toast(error.message, "danger");
    } finally {
      boton.disabled = false;
    }
  });
}

/** Bloquea los botones de la tarjeta mientras el servidor responde, para evitar clics dobles. */
async function modificarProducto(tarjeta, accion) {
  tarjeta.querySelectorAll("button").forEach((b) => (b.disabled = true));
  try {
    await accion();
  } catch (error) {
    const tipo = error.status === 409 ? "warning" : "danger";
    toast(error.message, tipo);
    mostrarCarrito();
  }
}
