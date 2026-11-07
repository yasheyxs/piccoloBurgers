import { api } from "../api.js";
import { iniciarLayout } from "../layout.js";
import { conBotonOcupado, datosFormulario, limpiarErrores, mostrarErrores, mostrarMensaje } from "../ui.js";

const formulario = document.getElementById("form-recuperar");

iniciarLayout();

formulario.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  limpiarErrores(formulario);

  const boton = formulario.querySelector('button[type="submit"]');
  const datos = datosFormulario(formulario);

  try {
    // La API responde lo mismo exista o no la cuenta, para no revelar qué emails están registrados
    const respuesta = await conBotonOcupado(
      boton,
      () => api("/cuenta/recuperar", { metodo: "POST", datos }),
      "Enviando..."
    );
    formulario.reset();
    mostrarMensaje(formulario, respuesta.mensaje, "success");
  } catch (error) {
    mostrarErrores(formulario, error);
  }
});
