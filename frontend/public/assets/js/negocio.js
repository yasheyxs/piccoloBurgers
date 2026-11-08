import { api } from "./api.js";

/**
 * Datos del local (dirección, horarios, WhatsApp, redes y datos de pago).
 * Vienen de la API (GET /negocio), que los lee de backend/config/negocio.php:
 * para cambiar un dato del local alcanza con cambiarlo ahí.
 */

let datos = null;

/** Se consultan una sola vez por página. */
export function cargarNegocio() {
  if (!datos) {
    datos = api("/negocio").catch((error) => {
      datos = null;
      throw error;
    });
  }
  return datos;
}

/**
 * Completa los elementos marcados con data-negocio="clave" (por ejemplo "direccion.calle")
 * y los enlaces con data-negocio-href="clave".
 */
export async function completarNegocio(raizDom = document) {
  const negocio = await cargarNegocio();
  const valor = (clave) => clave.split(".").reduce((dato, parte) => (dato == null ? undefined : dato[parte]), negocio);

  raizDom.querySelectorAll("[data-negocio]").forEach((elemento) => {
    const texto = valor(elemento.dataset.negocio);
    if (texto !== undefined && texto !== null) elemento.textContent = String(texto);
  });
  raizDom.querySelectorAll("[data-negocio-href]").forEach((elemento) => {
    const enlace = valor(elemento.dataset.negocioHref);
    if (typeof enlace === "string" && /^https:\/\//.test(enlace)) elemento.setAttribute("href", enlace);
  });
  raizDom.querySelectorAll("[data-negocio-src]").forEach((elemento) => {
    const enlace = valor(elemento.dataset.negocioSrc);
    if (typeof enlace === "string" && /^https:\/\//.test(enlace)) elemento.setAttribute("src", enlace);
  });

  return negocio;
}
