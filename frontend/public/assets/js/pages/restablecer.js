import { api } from "../api.js";
import { iniciarRequisitosPassword, iniciarVerPassword } from "../auth.js";
import { iniciarLayout } from "../layout.js";
import { conBotonOcupado, datosFormulario, limpiarErrores, mostrarErrores } from "../ui.js";

const formulario = document.getElementById("form-restablecer");
const token = new URLSearchParams(window.location.search).get("token") || "";

iniciarLayout();
iniciarVerPassword(formulario);
iniciarRequisitosPassword(document.getElementById("password"), document.getElementById("requisitos-password"));

if (!token) {
  mostrarEstado(
    "El enlace no está completo. Revisá que lo hayas copiado entero desde el correo, o pedí uno nuevo.",
    "warning",
    { texto: "Pedir un enlace nuevo", href: "recuperar.html" }
  );
}

formulario.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  limpiarErrores(formulario);

  const boton = formulario.querySelector('button[type="submit"]');
  const datos = { token, ...datosFormulario(formulario) };

  try {
    const respuesta = await conBotonOcupado(
      boton,
      () => api("/cuenta/restablecer", { metodo: "POST", datos }),
      "Actualizando..."
    );
    mostrarEstado(respuesta.mensaje, "success", { texto: "Iniciar sesión", href: "login.html" });
  } catch (error) {
    if (error.status === 410) {
      // El enlace venció o ya se usó: hay que pedir otro
      mostrarEstado(error.message, "danger", { texto: "Pedir un enlace nuevo", href: "recuperar.html" });
      return;
    }
    mostrarErrores(formulario, error);
  }
});

/** Oculta el formulario y muestra un mensaje final con un enlace para seguir. */
function mostrarEstado(mensaje, tipo, enlace) {
  formulario.hidden = true;

  document.getElementById("estado-mensaje").className = `alert alert-${tipo}`;
  document.getElementById("estado-mensaje").textContent = mensaje;

  const boton = document.getElementById("estado-enlace");
  boton.textContent = enlace.texto;
  boton.href = enlace.href;

  document.getElementById("estado").hidden = false;
  boton.focus();
}
