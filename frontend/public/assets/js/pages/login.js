import { api } from "../api.js";
import { conservarVolver, destinoDespuesDeIngresar, iniciarTelefono, iniciarVerPassword } from "../auth.js";
import { iniciarLayout } from "../layout.js";
import { conBotonOcupado, datosFormulario, limpiarErrores, mostrarErrores } from "../ui.js";

const formulario = document.getElementById("form-login");

iniciarTelefono(document.getElementById("codigo-pais"), document.getElementById("telefono"));
iniciarVerPassword(formulario);
conservarVolver(document.getElementById("enlace-registro"));

// Si ya tiene la sesión iniciada, no tiene sentido mostrarle el login
iniciarLayout().then((cliente) => {
  if (cliente) {
    window.location.replace(destinoDespuesDeIngresar(cliente));
  }
});

formulario.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  limpiarErrores(formulario);

  const boton = formulario.querySelector('button[type="submit"]');
  const datos = datosFormulario(formulario);

  try {
    const cliente = await conBotonOcupado(
      boton,
      () => api("/cuenta/login", { metodo: "POST", datos }),
      "Ingresando..."
    );
    window.location.href = destinoDespuesDeIngresar(cliente);
  } catch (error) {
    mostrarErrores(formulario, error);
  }
});
