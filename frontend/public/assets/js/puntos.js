import { api } from "./api.js";

/**
 * Canje de puntos: reglas del programa y la preferencia "usar mis puntos".
 *
 * El carrito y la confirmación del pedido muestran una vista previa del descuento.
 * El descuento real lo calcula el backend al crear el pedido; acá se repite la misma regla
 * (backend/src/Services/pedidos.php, crearPedido) para que el cliente vea lo mismo que va a pagar.
 */

const CLAVE_USAR_PUNTOS = "piccolo_usar_puntos";
let configuracion = null;

/** Reglas del programa de puntos (se consultan una sola vez por página). */
export function cargarConfiguracionPuntos() {
  if (!configuracion) {
    configuracion = api("/puntos/configuracion").catch((error) => {
      configuracion = null;
      throw error;
    });
  }
  return configuracion;
}

/** El cliente tiene sesión y le alcanzan los puntos para canjear. */
export function puedeCanjear(cliente, config) {
  return Boolean(cliente && config && Number(cliente.puntos) >= config.minimoPuntos);
}

/**
 * Puntos que se usarían y el descuento que generan:
 * el descuento máximo es un porcentaje del total, redondeado hacia abajo a múltiplos de $100.
 */
export function calcularCanje(total, puntosDisponibles, config) {
  const sinCanje = { puntosUsados: 0, descuento: 0 };
  if (!config || total <= 0 || puntosDisponibles < config.minimoPuntos) {
    return sinCanje;
  }

  const porcentaje = config.maximoPorcentaje > 0 ? Math.min(1, config.maximoPorcentaje) : 0.25;
  const valorPunto = Math.max(0.01, config.valorPunto);
  const descuentoMaximo = total * porcentaje;
  const descuentoRedondeado = Math.floor(descuentoMaximo / 100) * 100;
  const puntosUsados = Math.min(puntosDisponibles, Math.floor(descuentoRedondeado / valorPunto));

  if (puntosUsados <= 0) {
    return sinCanje;
  }
  return { puntosUsados, descuento: Math.min(puntosUsados * valorPunto, descuentoMaximo) };
}

/** Porcentaje máximo de canje para mostrar, por ejemplo "25%". */
export function porcentajeMaximo(config) {
  return `${(config.maximoPorcentaje * 100).toLocaleString("es-AR", { maximumFractionDigits: 2 })}%`;
}

/* La preferencia se guarda en sessionStorage para pasarla del carrito a la confirmación. */

export function quiereUsarPuntos() {
  try {
    return sessionStorage.getItem(CLAVE_USAR_PUNTOS) === "1";
  } catch {
    return false;
  }
}

export function guardarUsarPuntos(usar) {
  try {
    sessionStorage.setItem(CLAVE_USAR_PUNTOS, usar ? "1" : "0");
  } catch {
    // sin almacenamiento disponible: el pedido se envía sin canje
  }
}

export function olvidarUsarPuntos() {
  try {
    sessionStorage.removeItem(CLAVE_USAR_PUNTOS);
  } catch {
    // nada que limpiar
  }
}
