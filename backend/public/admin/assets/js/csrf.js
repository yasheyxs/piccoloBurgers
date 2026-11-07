/**
 * Agrega el token CSRF a todo lo que el panel envía al servidor:
 * - a cada formulario POST, como campo oculto "csrf_token";
 * - a cada pedido con fetch() a este mismo sitio que no sea GET, como cabecera "X-CSRF-Token".
 *
 * Así ninguna pantalla tiene que acordarse de hacerlo. El servidor rechaza los envíos sin token
 * (ver src/Http/Admin/acceso.php).
 */
(function () {
  "use strict";

  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
  if (!token) {
    return;
  }

  function agregarAlFormulario(formulario) {
    if (!(formulario instanceof HTMLFormElement)) return;
    if ((formulario.getAttribute("method") || "get").toLowerCase() !== "post") return;

    let campo = formulario.querySelector('input[name="csrf_token"]');
    if (!campo) {
      campo = document.createElement("input");
      campo.type = "hidden";
      campo.name = "csrf_token";
      formulario.appendChild(campo);
    }
    campo.value = token;
  }

  // Formularios que ya están en la página y los que se envíen después
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form").forEach(agregarAlFormulario);
  });
  document.addEventListener("submit", (evento) => agregarAlFormulario(evento.target), true);

  // fetch() a este mismo sitio
  const fetchOriginal = window.fetch.bind(window);
  window.fetch = function (recurso, opciones = {}) {
    const url = new URL(recurso instanceof Request ? recurso.url : String(recurso), window.location.href);
    const metodo = (opciones.method || (recurso instanceof Request ? recurso.method : "GET")).toUpperCase();

    if (url.origin === window.location.origin && !["GET", "HEAD", "OPTIONS"].includes(metodo)) {
      const cabeceras = new Headers(opciones.headers || (recurso instanceof Request ? recurso.headers : undefined));
      if (!cabeceras.has("X-CSRF-Token")) {
        cabeceras.set("X-CSRF-Token", token);
      }
      opciones = { ...opciones, headers: cabeceras };
    }

    return fetchOriginal(recurso, opciones);
  };
})();
