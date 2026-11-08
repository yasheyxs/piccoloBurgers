import { api } from "../api.js";
import { agregarAlCarrito, cargarCarrito } from "../carrito.js";
import { iniciarLayout } from "../layout.js";
import { completarNegocio } from "../negocio.js";
import { datosFormulario, escapar, limpiarErrores, mostrarErrores, precio, toast, conBotonOcupado } from "../ui.js";

const POR_PAGINA = 8;

const contenedor = document.getElementById("contenedor-menu");
const buscador = document.getElementById("buscador-menu");
const selectorCategoria = document.getElementById("categoria");
const botonMas = document.getElementById("btn-mostrar-mas");

let offset = 0;
let total = 0;
let consultaActual = 0;

iniciarLayout({ enInicio: true }).then((cliente) => {
  const burbuja = document.getElementById("registro-burbuja");
  if (!cliente && burbuja) {
    burbuja.hidden = false;
    burbuja.querySelector(".cerrar-burbuja").addEventListener("click", () => {
      burbuja.classList.add("fade-out");
      setTimeout(() => burbuja.remove(), 400);
    });
  }
});

if (window.AOS) AOS.init({ duration: 800, once: true });

cargarInicio();
mostrarNegocio();
cargarPuntos();
cargarMenu(true);
iniciarFiltros();
iniciarContacto();

/** Dirección, horarios, mapa y redes del local (vienen de la API). */
async function mostrarNegocio() {
  try {
    const negocio = await completarNegocio();
    document.getElementById("horarios").innerHTML = negocio.horarios
      .map((h) => `<p class="mb-1"><strong>${escapar(h.dias)}</strong></p><p>${escapar(h.horas)}</p>`)
      .join("");
  } catch {
    // sin datos del local, la portada se ve igual salvo esas secciones
  }
}

async function cargarInicio() {
  try {
    const { banner, testimonios } = await api("/inicio");
    if (banner) {
      document.getElementById("banner").style.backgroundImage = `url('${banner.imagen}')`;
      document.getElementById("banner-titulo").textContent = banner.titulo;
      document.getElementById("banner-descripcion").textContent = banner.descripcion;
    }
    mostrarTestimonios(testimonios);
  } catch {
    // si falla, quedan el banner por defecto y la sección de testimonios vacía
  }
}

function mostrarTestimonios(testimonios) {
  const carrusel = document.getElementById("testimonios-carousel");
  if (!testimonios.length) {
    document.getElementById("testimonios").hidden = true;
    return;
  }

  const tarjeta = (t) => `
    <div class="testimonio-card">
      <div class="card h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <p class="card-text">${escapar(t.opinion)}</p>
          <div class="card-footer mt-auto">${escapar(t.nombre)}</div>
        </div>
      </div>
    </div>`;

  // Se repiten para que el carrusel gire sin cortes
  const html = testimonios.map(tarjeta).join("");
  carrusel.innerHTML = html + html;
}

async function cargarPuntos() {
  try {
    const puntos = await api("/puntos/configuracion");
    document.getElementById("puntos-detalle").innerHTML =
      `<br><br>Sumás <strong class="text-gold">1 punto cada ${precio(puntos.puntosPorCada)}</strong> y cada punto vale <strong>${precio(puntos.valorPunto)}</strong> de descuento.`;
  } catch {
    // el texto general de la sección alcanza
  }
}

async function cargarMenu(reiniciar) {
  const consulta = ++consultaActual;
  if (reiniciar) {
    offset = 0;
    contenedor.innerHTML = '<div class="col-12 estado-vacio"><span class="spinner-border spinner-border-sm me-2"></span>Cargando menú...</div>';
  }

  const parametros = new URLSearchParams({
    busqueda: buscador.value.trim(),
    categoria: selectorCategoria.value,
    offset: String(offset),
    limit: String(POR_PAGINA),
  });

  try {
    const datos = await api(`/menu?${parametros}`);
    if (consulta !== consultaActual) return; // llegó una búsqueda más nueva

    llenarCategorias(datos.categorias);
    total = datos.total;
    if (reiniciar) contenedor.innerHTML = "";

    if (datos.productos.length === 0 && offset === 0) {
      contenedor.innerHTML = '<div class="col-12 estado-vacio">No encontramos productos con esa búsqueda.</div>';
    }

    await cargarCarrito().catch(() => {});
    datos.productos.forEach((producto) => {
      contenedor.insertAdjacentHTML("beforeend", tarjetaProducto(producto));
    });

    offset += datos.productos.length;
    botonMas.hidden = offset >= total;
    if (window.AOS) AOS.refresh();
  } catch (error) {
    if (consulta !== consultaActual) return;
    contenedor.innerHTML = `<div class="col-12 estado-vacio">${escapar(error.message)}</div>`;
    botonMas.hidden = true;
  }
}

function tarjetaProducto(producto) {
  const hayStock = producto.disponibles > 0;
  return `
    <div class="col d-flex" data-aos="fade-up">
      <div class="card position-relative d-flex flex-column h-100 w-100">
        <img src="${escapar(producto.imagen)}" class="card-img-top" alt="Foto de ${escapar(producto.nombre)}" loading="lazy">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">${escapar(producto.nombre)}</h5>
          <p class="card-text small"><strong>${escapar(producto.ingredientes)}</strong></p>
          <p class="card-text"><strong>Precio:</strong> ${precio(producto.precio)}</p>
          <p class="card-text"><small><em>${escapar(producto.categoria)}</em></small></p>
          <button class="btn btn-agregar mt-auto${hayStock ? "" : " btn-sin-stock"}" data-id="${producto.id}" data-nombre="${escapar(producto.nombre)}" ${hayStock ? "" : "disabled"}>
            ${hayStock ? "Agregar" : "Sin stock"}
          </button>
        </div>
      </div>
    </div>`;
}

let categoriasCargadas = false;
function llenarCategorias(categorias) {
  if (categoriasCargadas) return;
  categoriasCargadas = true;
  selectorCategoria.insertAdjacentHTML(
    "beforeend",
    categorias.map((c) => `<option value="${escapar(c)}">${escapar(c)}</option>`).join("")
  );
}

function iniciarFiltros() {
  let espera;
  buscador.addEventListener("input", () => {
    clearTimeout(espera);
    espera = setTimeout(() => cargarMenu(true), 300);
  });
  selectorCategoria.addEventListener("change", () => cargarMenu(true));
  document.getElementById("limpiar-filtro-menu").addEventListener("click", () => {
    buscador.value = "";
    selectorCategoria.value = "";
    cargarMenu(true);
  });
  botonMas.addEventListener("click", () => cargarMenu(false));

  contenedor.addEventListener("click", async (evento) => {
    const boton = evento.target.closest(".btn-agregar");
    if (!boton || boton.disabled) return;

    const id = Number(boton.dataset.id);
    try {
      const carrito = await conBotonOcupado(boton, () => agregarAlCarrito(id), "Agregando...");
      toast(`${boton.dataset.nombre} se agregó al carrito.`);

      // Si con esta unidad se agotó el stock, el botón pasa a "Sin stock"
      const restantes = carrito.disponibilidad.find((d) => d.productoId === id)?.disponibles;
      if (restantes === 0) marcarSinStock(boton);
    } catch (error) {
      toast(error.message, error.status === 409 ? "warning" : "danger");
      if (error.status === 409) marcarSinStock(boton);
    }
  });
}

function marcarSinStock(boton) {
  boton.disabled = true;
  boton.classList.add("btn-sin-stock");
  boton.textContent = "Sin stock";
}

function iniciarContacto() {
  const formulario = document.getElementById("formContacto");
  formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    limpiarErrores(formulario);
    const boton = formulario.querySelector('[type="submit"]');

    try {
      const respuesta = await conBotonOcupado(boton, () =>
        api("/comentarios", { metodo: "POST", datos: datosFormulario(formulario) })
      );
      formulario.reset();
      toast(respuesta.mensaje);
    } catch (error) {
      mostrarErrores(formulario, error);
    }
  });
}
