import { API_URL } from "./config.js";

/** Error devuelto por la API, con el código HTTP y los errores por campo. */
export class ApiError extends Error {
  constructor(mensaje, status = 0, errores = {}) {
    super(mensaje);
    this.status = status;
    this.errores = errores || {};
  }
}

/**
 * Llama a la API y devuelve `datos`. Si la respuesta es un error, lanza ApiError.
 *
 *   const menu = await api("/menu?limit=8");
 *   await api("/cuenta/login", { metodo: "POST", datos: { telefono, password } });
 */
export async function api(ruta, { metodo = "GET", datos } = {}) {
  const opciones = {
    method: metodo,
    credentials: "include",
    headers: { Accept: "application/json" },
  };

  if (datos !== undefined) {
    opciones.headers["Content-Type"] = "application/json";
    opciones.body = JSON.stringify(datos);
  }

  let respuesta;
  try {
    respuesta = await fetch(API_URL + ruta, opciones);
  } catch {
    throw new ApiError("No pudimos conectarnos. Revisá tu conexión e intentá de nuevo.");
  }

  let cuerpo = null;
  try {
    cuerpo = await respuesta.json();
  } catch {
    // respuesta sin JSON (por ejemplo un error del servidor web)
  }

  if (!respuesta.ok || !cuerpo || cuerpo.exito === false) {
    throw new ApiError(
      cuerpo?.mensaje || "Ocurrió un error inesperado. Intentá nuevamente en unos minutos.",
      respuesta.status,
      cuerpo?.errores
    );
  }

  return cuerpo.datos;
}
