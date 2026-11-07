import { api } from "../api.js";
import {
  conservarVolver,
  destinoDespuesDeIngresar,
  iniciarRequisitosPassword,
  iniciarTelefono,
  iniciarVerPassword,
} from "../auth.js";
import { iniciarLayout } from "../layout.js";
import { conBotonOcupado, datosFormulario, limpiarErrores, mostrarErrores } from "../ui.js";

const formulario = document.getElementById("form-registro");
const inputNombre = document.getElementById("nombre");

iniciarTelefono(document.getElementById("codigo-pais"), document.getElementById("telefono"));
iniciarVerPassword(formulario);
iniciarRequisitosPassword(document.getElementById("password"), document.getElementById("requisitos-password"));
conservarVolver(document.getElementById("enlace-login"));

// El nombre solo admite letras, espacios y algunos signos comunes (apóstrofo, punto, guion)
inputNombre.addEventListener("input", () => {
  inputNombre.value = inputNombre.value.replace(/[^\p{L}\s'.-]/gu, "");
});

// Si ya tiene la sesión iniciada, no necesita crear otra cuenta
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
    // La API crea la cuenta y deja la sesión iniciada
    const cliente = await conBotonOcupado(
      boton,
      () => api("/cuenta/registro", { metodo: "POST", datos }),
      "Creando cuenta..."
    );
    window.location.href = destinoDespuesDeIngresar(cliente);
  } catch (error) {
    mostrarErrores(formulario, error);
  }
});
