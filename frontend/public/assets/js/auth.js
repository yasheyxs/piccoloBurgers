/**
 * Utilidades de las páginas de cuenta: login, registro, recuperar y restablecer contraseña.
 */

/**
 * Países habilitados para el teléfono. `longitud` es la cantidad máxima de dígitos
 * del número sin el código de país (la API valida las longitudes exactas).
 */
const PAISES = [
  { codigo: "54", bandera: "🇦🇷", nombre: "Argentina", longitud: 10 },
  { codigo: "598", bandera: "🇺🇾", nombre: "Uruguay", longitud: 9 },
  { codigo: "55", bandera: "🇧🇷", nombre: "Brasil", longitud: 11 },
  { codigo: "56", bandera: "🇨🇱", nombre: "Chile", longitud: 9 },
  { codigo: "595", bandera: "🇵🇾", nombre: "Paraguay", longitud: 9 },
  { codigo: "591", bandera: "🇧🇴", nombre: "Bolivia", longitud: 8 },
  { codigo: "51", bandera: "🇵🇪", nombre: "Perú", longitud: 9 },
  { codigo: "1", bandera: "🇺🇸", nombre: "Estados Unidos", longitud: 10 },
  { codigo: "34", bandera: "🇪🇸", nombre: "España", longitud: 9 },
];

/** Requisitos de la contraseña (los mismos que valida la API). */
const REQUISITOS_PASSWORD = {
  largo: (valor) => valor.length >= 8,
  mayuscula: (valor) => /[A-Z]/.test(valor),
  minuscula: (valor) => /[a-z]/.test(valor),
  numero: (valor) => /\d/.test(valor),
  simbolo: (valor) => /[\W_]/.test(valor),
};

/**
 * Página a la que se vuelve después de iniciar sesión o registrarse.
 * Solo acepta rutas del mismo sitio en ?volver=; si no hay o apunta afuera, va al inicio.
 */
export function destinoDespuesDeIngresar(cliente) {
  if (cliente?.emailPendiente) {
    return "perfil.html";
  }

  const volver = new URLSearchParams(window.location.search).get("volver");
  if (volver) {
    try {
      const url = new URL(volver, window.location.href);
      if (url.origin === window.location.origin) {
        return url.pathname + url.search + url.hash;
      }
    } catch {
      // ruta mal formada: se ignora
    }
  }

  return "../index.html";
}

/** Agrega ?volver= (si la página lo recibió) a un enlace hacia otra página de cuenta. */
export function conservarVolver(enlace) {
  const volver = new URLSearchParams(window.location.search).get("volver");
  if (!enlace || !volver) return;

  const url = new URL(enlace.getAttribute("href"), window.location.href);
  url.searchParams.set("volver", volver);
  enlace.href = url.pathname + url.search;
}

/**
 * Completa el selector de código de país y limita el número a dígitos,
 * con el máximo de caracteres según el país elegido.
 */
export function iniciarTelefono(selectPais, inputTelefono) {
  selectPais.innerHTML = PAISES.map(
    (pais) => `<option value="${pais.codigo}" title="${pais.nombre}">${pais.bandera} +${pais.codigo}</option>`
  ).join("");
  selectPais.value = "54";

  const actualizarLongitud = () => {
    const pais = PAISES.find((item) => item.codigo === selectPais.value);
    inputTelefono.maxLength = pais ? pais.longitud : 15;
  };

  inputTelefono.addEventListener("input", () => {
    inputTelefono.value = inputTelefono.value.replace(/\D/g, "");
  });
  selectPais.addEventListener("change", actualizarLongitud);
  actualizarLongitud();
}

/** Botones para mostrar u ocultar la contraseña: <button data-ver-password="idDelInput">. */
export function iniciarVerPassword(contenedor = document) {
  contenedor.querySelectorAll("[data-ver-password]").forEach((boton) => {
    const input = document.getElementById(boton.dataset.verPassword);
    if (!input) return;

    boton.addEventListener("click", () => {
      const mostrar = input.type === "password";
      input.type = mostrar ? "text" : "password";
      boton.setAttribute("aria-pressed", String(mostrar));
      boton.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
      boton.querySelector("i")?.classList.toggle("fa-eye", !mostrar);
      boton.querySelector("i")?.classList.toggle("fa-eye-slash", mostrar);
    });
  });
}

/**
 * Marca en vivo los requisitos que ya cumple la contraseña.
 * La lista tiene un <li data-requisito="largo|mayuscula|minuscula|numero|simbolo"> por requisito.
 */
export function iniciarRequisitosPassword(input, lista) {
  const actualizar = () => {
    for (const [nombre, cumple] of Object.entries(REQUISITOS_PASSWORD)) {
      const item = lista.querySelector(`[data-requisito="${nombre}"]`);
      item?.classList.toggle("cumplido", cumple(input.value));
    }
  };

  input.addEventListener("input", actualizar);
  actualizar();
}
