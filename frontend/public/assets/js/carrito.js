import { api } from "./api.js";

/**
 * Estado del carrito. El servidor reserva el stock por sesión; además se guarda una copia
 * en el navegador para recuperar el carrito si la sesión se pierde (por ejemplo al cerrar el navegador).
 *
 * Cada cambio dispara el evento "carrito:actualizado" en window con el carrito completo.
 */

const CLAVE_LOCAL = "piccolo_carrito";
let estado = { productos: [], total: 0, disponibilidad: [] };
let cargaInicial = null;

function guardar(carrito) {
  estado = carrito;
  try {
    localStorage.setItem(
      CLAVE_LOCAL,
      JSON.stringify(carrito.productos.map(({ id, cantidad }) => ({ id, cantidad })))
    );
  } catch {
    // sin almacenamiento local disponible: el carrito sigue funcionando con la sesión
  }
  window.dispatchEvent(new CustomEvent("carrito:actualizado", { detail: carrito }));
  return carrito;
}

function copiaLocal() {
  try {
    const items = JSON.parse(localStorage.getItem(CLAVE_LOCAL) || "[]");
    return Array.isArray(items) ? items.filter((i) => i && i.id > 0 && i.cantidad > 0) : [];
  } catch {
    return [];
  }
}

/** Carga el carrito del servidor (una sola vez por página) y lo recupera de la copia local si hace falta. */
export function cargarCarrito() {
  if (!cargaInicial) {
    cargaInicial = (async () => {
      let carrito = await api("/carrito");
      const local = copiaLocal();
      if (carrito.productos.length === 0 && local.length > 0) {
        try {
          carrito = await api("/carrito", { metodo: "PUT", datos: { productos: local } });
        } catch {
          // si ya no hay stock de lo guardado, se empieza con el carrito vacío
        }
      }
      return guardar(carrito);
    })();
  }
  return cargaInicial;
}

export function carritoActual() {
  return estado;
}

export function cantidadEnCarrito(productoId) {
  return estado.productos.find((p) => p.id === productoId)?.cantidad || 0;
}

export function totalUnidades(carrito = estado) {
  return carrito.productos.reduce((suma, p) => suma + p.cantidad, 0);
}

export async function fijarCantidad(productoId, cantidad) {
  const carrito = await api(`/carrito/productos/${productoId}`, { metodo: "PUT", datos: { cantidad } });
  return guardar(carrito);
}

export async function agregarAlCarrito(productoId) {
  await cargarCarrito();
  return fijarCantidad(productoId, cantidadEnCarrito(productoId) + 1);
}

export async function quitarDelCarrito(productoId) {
  const carrito = await api(`/carrito/productos/${productoId}`, { metodo: "DELETE" });
  return guardar(carrito);
}

export async function vaciarCarrito() {
  const carrito = await api("/carrito", { metodo: "DELETE" });
  return guardar(carrito);
}

/** Después de confirmar un pedido el servidor ya liberó las reservas: solo queda limpiar la copia local. */
export function carritoConfirmado() {
  return guardar({ productos: [], total: 0, disponibilidad: [] });
}
