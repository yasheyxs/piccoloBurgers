import { api } from "./api.js";
import { cargarCarrito, totalUnidades } from "./carrito.js";
import { WHATSAPP_NUMERO } from "./config.js";
import { escapar, toast } from "./ui.js";

/**
 * Partes comunes de todas las páginas: barra de navegación, botones flotantes y sesión.
 *
 * Cada página carga este módulo y llama a iniciarLayout(). Las páginas que están en
 * una subcarpeta indican la ruta a la raíz con <body data-raiz="../">.
 */

let sesion = null;

/** Cliente con sesión iniciada, o null. Se consulta una sola vez por página. */
export function obtenerCliente() {
  if (!sesion) {
    sesion = api("/cuenta/sesion");
  }
  return sesion;
}

export function raiz() {
  return document.body.dataset.raiz || "";
}

/** Redirige al login si no hay sesión. Devuelve el cliente. */
export async function requerirSesion() {
  const cliente = await obtenerCliente();
  if (!cliente) {
    const volver = encodeURIComponent(window.location.pathname + window.location.search);
    window.location.href = `${raiz()}cliente/login.html?volver=${volver}`;
    return new Promise(() => {});
  }
  return cliente;
}

/**
 * @param {{ enInicio?: boolean, botonCarrito?: boolean }} opciones
 *   enInicio: los enlaces de secciones apuntan a anclas de la misma página.
 *   botonCarrito: muestra el botón flotante del carrito (no tiene sentido en el carrito mismo).
 */
export async function iniciarLayout({ enInicio = false, botonCarrito = true } = {}) {
  const r = raiz();
  const secciones = enInicio ? "#" : `${r}index.html#`;

  const header = document.createElement("header");
  header.innerHTML = `
  <nav class="navbar navbar-expand-lg navbar-dark sticky-navbar bg-dark">
    <div class="container">
      <a class="navbar-brand" href="${enInicio ? "#inicio" : r + "index.html"}">
        <i class="fas fa-utensils" aria-hidden="true"></i> Piccolo Burgers
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="${secciones}inicio">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="${secciones}menu">Menú</a></li>
          <li class="nav-item"><a class="nav-link" href="${secciones}nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="${secciones}testimonios">Testimonio</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Más</a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="${secciones}puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="${secciones}ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="${secciones}contacto">Contacto</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link position-relative" href="${r}carrito.html" aria-label="Carrito">
              <i class="fas fa-shopping-cart" aria-hidden="true"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-counter" id="contador-carrito">0</span>
            </a>
          </li>
          <li class="nav-item ms-lg-3" id="nav-cuenta">
            <a class="btn btn-gold rounded-pill nav-auth-btn" href="${r}cliente/login.html">Iniciar sesión / Registrarse</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>`;
  document.body.prepend(header);

  agregarBotonesFlotantes(r, botonCarrito);
  agregarModalCerrarSesion();

  window.addEventListener("carrito:actualizado", (evento) => actualizarContador(evento.detail));
  cargarCarrito().catch(() => {});

  const cliente = await obtenerCliente().catch(() => null);
  if (cliente) {
    const item = document.getElementById("nav-cuenta");
    item.className = "nav-item d-flex";
    item.innerHTML = `
      <a href="${r}cliente/perfil.html" class="nav-link" title="${escapar(cliente.nombre)}" aria-label="Mi perfil">
        <i class="fas fa-user-circle" aria-hidden="true"></i>
      </a>
      <a href="#" class="nav-link" title="Cerrar sesión" aria-label="Cerrar sesión" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
      </a>`;
  }

  return cliente;
}

function actualizarContador(carrito) {
  const contador = document.getElementById("contador-carrito");
  if (contador) contador.textContent = String(totalUnidades(carrito));
}

function agregarBotonesFlotantes(r, botonCarrito) {
  const mensaje = encodeURIComponent("¡Hola! Me gustaría hacer un pedido");
  const contenedor = document.createElement("div");
  contenedor.innerHTML = `
    ${botonCarrito ? `<a href="${r}carrito.html" class="btn-flotante btn-carrito" aria-label="Ir al carrito"><i class="fa-solid fa-cart-shopping"></i></a>` : ""}
    <a href="https://wa.me/${WHATSAPP_NUMERO}?text=${mensaje}" class="btn-flotante btn-whatsapp" target="_blank" rel="noopener" aria-label="Contactanos por WhatsApp"><i class="fab fa-whatsapp"></i></a>
    <button type="button" class="btn-flotante btn-scroll-up" aria-label="Volver arriba"><i class="fa-solid fa-chevron-up"></i></button>`;
  document.body.append(...contenedor.children);

  const subir = document.querySelector(".btn-scroll-up");
  const alternar = () => subir.classList.toggle("visible", window.scrollY > 300);
  window.addEventListener("scroll", alternar, { passive: true });
  subir.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
  alternar();
}

function agregarModalCerrarSesion() {
  const modal = document.createElement("div");
  modal.innerHTML = `
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-0 shadow-lg">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title fw-bold" id="logoutModalLabel">
            <i class="fas fa-sign-out-alt me-2 text-warning" aria-hidden="true"></i> ¿Cerrar sesión?
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <p class="fs-5 mb-0">¿Estás seguro de que querés cerrar sesión?</p>
        </div>
        <div class="modal-footer justify-content-center border-top-0">
          <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Quedarme</button>
          <button type="button" class="btn btn-danger px-4" id="confirmar-logout">
            <i class="fas fa-door-open me-1" aria-hidden="true"></i> Cerrar sesión
          </button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.append(modal.firstElementChild);

  document.getElementById("confirmar-logout").addEventListener("click", async () => {
    try {
      await api("/cuenta/logout", { metodo: "POST", datos: {} });
      window.location.href = `${raiz()}index.html`;
    } catch (error) {
      toast(error.message, "danger");
    }
  });
}
