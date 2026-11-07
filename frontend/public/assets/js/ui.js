/** Utilidades de interfaz compartidas por todas las páginas. */

const formatoPrecio = new Intl.NumberFormat("es-AR", {
  style: "currency",
  currency: "ARS",
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});

export function precio(valor) {
  return formatoPrecio.format(Number(valor) || 0);
}

export function fecha(valor) {
  if (!valor) return "";
  const date = new Date(String(valor).replace(" ", "T"));
  return Number.isNaN(date.getTime())
    ? String(valor)
    : date.toLocaleString("es-AR", { dateStyle: "short", timeStyle: "short" });
}

/** Escapa texto para insertarlo en HTML. */
export function escapar(texto) {
  return String(texto ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/** Muestra un aviso flotante abajo a la derecha. tipo: success | danger | warning | info */
export function toast(mensaje, tipo = "success") {
  let contenedor = document.getElementById("toasts");
  if (!contenedor) {
    contenedor = document.createElement("div");
    contenedor.id = "toasts";
    contenedor.className = "toast-container position-fixed bottom-0 end-0 p-3";
    contenedor.style.zIndex = "1200";
    document.body.appendChild(contenedor);
  }

  const elemento = document.createElement("div");
  elemento.className = `toast align-items-center text-bg-${tipo} border-0`;
  elemento.setAttribute("role", "status");
  elemento.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${escapar(mensaje)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>`;
  contenedor.appendChild(elemento);

  const instancia = new bootstrap.Toast(elemento, { delay: 3500 });
  elemento.addEventListener("hidden.bs.toast", () => elemento.remove());
  instancia.show();
}

/**
 * Muestra los errores de la API debajo de cada campo del formulario (por atributo name)
 * y un mensaje general en el elemento [data-mensaje] del formulario, si existe.
 */
export function mostrarErrores(formulario, error) {
  limpiarErrores(formulario);
  const errores = error?.errores || {};
  let hayCampos = false;

  for (const [campo, mensaje] of Object.entries(errores)) {
    const input = formulario.querySelector(`[name="${campo}"]`);
    if (!input) continue;
    hayCampos = true;
    input.classList.add("is-invalid");
    const aviso = document.createElement("div");
    aviso.className = "invalid-feedback d-block";
    aviso.dataset.error = "";
    aviso.textContent = mensaje;
    (input.closest(".input-group") || input).insertAdjacentElement("afterend", aviso);
  }

  const general = formulario.querySelector("[data-mensaje]");
  if (general) {
    general.className = "alert alert-danger";
    general.textContent = error?.message || "Ocurrió un error.";
    general.hidden = false;
  } else if (!hayCampos) {
    toast(error?.message || "Ocurrió un error.", "danger");
  }
}

export function limpiarErrores(formulario) {
  formulario.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
  formulario.querySelectorAll("[data-error]").forEach((el) => el.remove());
  const general = formulario.querySelector("[data-mensaje]");
  if (general) general.hidden = true;
}

export function mostrarMensaje(formulario, texto, tipo = "success") {
  const general = formulario.querySelector("[data-mensaje]");
  if (!general) {
    toast(texto, tipo);
    return;
  }
  general.className = `alert alert-${tipo}`;
  general.textContent = texto;
  general.hidden = false;
}

/** Deshabilita el botón de envío mientras se procesa una acción. */
export async function conBotonOcupado(boton, accion, textoOcupado = "Enviando...") {
  const original = boton.innerHTML;
  boton.disabled = true;
  boton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${escapar(textoOcupado)}`;
  try {
    return await accion();
  } finally {
    boton.disabled = false;
    boton.innerHTML = original;
  }
}

/** Datos de un formulario como objeto (los checkbox se devuelven como true/false). */
export function datosFormulario(formulario) {
  const datos = Object.fromEntries(new FormData(formulario).entries());
  formulario.querySelectorAll('input[type="checkbox"][name]').forEach((check) => {
    datos[check.name] = check.checked;
  });
  return datos;
}
