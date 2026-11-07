import { api } from "../api.js";
import { iniciarLayout, raiz, requerirSesion } from "../layout.js";
import {
  conBotonOcupado,
  datosFormulario,
  escapar,
  fecha,
  limpiarErrores,
  mostrarErrores,
  precio,
  toast,
} from "../ui.js";

/** Países aceptados para el teléfono (los mismos que valida el backend). */
const PAISES = [
  { codigo: "54", bandera: "🇦🇷", nombre: "Argentina", largo: 10 },
  { codigo: "598", bandera: "🇺🇾", nombre: "Uruguay", largo: 9 },
  { codigo: "55", bandera: "🇧🇷", nombre: "Brasil", largo: 11 },
  { codigo: "56", bandera: "🇨🇱", nombre: "Chile", largo: 9 },
  { codigo: "595", bandera: "🇵🇾", nombre: "Paraguay", largo: 9 },
  { codigo: "591", bandera: "🇧🇴", nombre: "Bolivia", largo: 8 },
  { codigo: "51", bandera: "🇵🇪", nombre: "Perú", largo: 9 },
  { codigo: "1", bandera: "🇺🇸", nombre: "Estados Unidos", largo: 10 },
  { codigo: "34", bandera: "🇪🇸", nombre: "España", largo: 9 },
];

/** Cada cuánto se refresca el historial para ver los cambios de estado de los pedidos. */
const INTERVALO_HISTORIAL = 15000;

const ESTADOS = {
  "En preparación": { clase: "text-bg-warning", icono: "fa-hourglass-half" },
  "En camino": { clase: "text-bg-info", icono: "fa-truck" },
  Listo: { clase: "text-bg-success", icono: "fa-circle-check" },
  Cancelado: { clase: "text-bg-danger", icono: "fa-circle-xmark" },
};

const formDatos = document.getElementById("form-datos");
const formPassword = document.getElementById("form-password");
const selectPais = document.getElementById("editar-codigo-pais");
const inputTelefono = document.getElementById("editar-telefono");
const modalEditar = document.getElementById("modalEditarCliente");
const historial = document.getElementById("historial-pedidos");

let cliente = null;
let configuracionPuntos = null;
let ultimosPedidos = "";

iniciarLayout();
iniciar();

async function iniciar() {
  try {
    cliente = await requerirSesion();
  } catch (error) {
    mostrarErrorGeneral(error.message);
    return;
  }

  llenarPaises();
  iniciarFormularios();
  mostrarCliente();
  cargarConfiguracionPuntos();
  cargarPedidos();
  setInterval(refrescarPedidos, INTERVALO_HISTORIAL);

  if (cliente.emailPendiente) {
    document.getElementById("aviso-email").hidden = false;
    bootstrap.Modal.getOrCreateInstance(modalEditar).show();
  }
}

function mostrarErrorGeneral(mensaje) {
  const alerta = document.getElementById("perfil-error");
  alerta.textContent = mensaje;
  alerta.hidden = false;
}

/* ---------- Datos del cliente ---------- */

function mostrarCliente() {
  const { codigoPais, numero } = separarTelefono(cliente.telefono);

  document.getElementById("dato-nombre").textContent = cliente.nombre;
  document.getElementById("dato-telefono").textContent = numero ? `+${codigoPais} ${numero}` : "Sin teléfono";
  document.getElementById("dato-email").textContent = cliente.email || "Sin email cargado";
  document.getElementById("dato-registro").textContent = fecha(cliente.fechaRegistro);
  document.getElementById("dato-puntos").textContent = String(cliente.puntos ?? 0);
  mostrarValorPuntos();
}

async function cargarConfiguracionPuntos() {
  try {
    configuracionPuntos = await api("/puntos/configuracion");
    mostrarValorPuntos();
  } catch {
    // sin la configuración solo se muestra la cantidad de puntos
  }
}

function mostrarValorPuntos() {
  const detalle = document.getElementById("dato-puntos-valor");
  if (!configuracionPuntos || !cliente) return;

  const puntos = Number(cliente.puntos) || 0;
  const { valorPunto, minimoPuntos } = configuracionPuntos;
  let texto = `Equivalen a ${precio(puntos * valorPunto)} de descuento.`;
  if (puntos < minimoPuntos) {
    texto += ` Podés canjearlos a partir de ${minimoPuntos} puntos.`;
  }

  detalle.textContent = texto;
  detalle.hidden = false;
}

/**
 * Separa un teléfono guardado como "+540000825022" en código de país y número.
 * Prueba primero los códigos más largos para que "+598..." no se tome como otro país.
 */
function separarTelefono(telefono) {
  const valor = String(telefono || "");
  const codigos = PAISES.map((pais) => pais.codigo).sort((a, b) => b.length - a.length);
  const codigo = codigos.find((c) => valor.startsWith(`+${c}`));

  if (!codigo) {
    return { codigoPais: PAISES[0].codigo, numero: valor.replace(/\D/g, "") };
  }
  return { codigoPais: codigo, numero: valor.slice(codigo.length + 1) };
}

/* ---------- Formularios ---------- */

function llenarPaises() {
  selectPais.innerHTML = PAISES.map(
    (pais) => `<option value="${pais.codigo}" title="${escapar(pais.nombre)}">${pais.bandera} +${pais.codigo}</option>`
  ).join("");
}

function actualizarLargoTelefono() {
  const pais = PAISES.find((p) => p.codigo === selectPais.value);
  inputTelefono.maxLength = pais ? pais.largo : 15;
}

function iniciarFormularios() {
  // Al abrir el modal se cargan los datos actuales; al cerrarlo se limpia la contraseña.
  modalEditar.addEventListener("show.bs.modal", prepararFormularioDatos);
  modalEditar.addEventListener("shown.bs.modal", () => {
    const campo = cliente.emailPendiente ? "editar-email" : "editar-nombre";
    document.getElementById(campo).focus();
  });
  modalEditar.addEventListener("hidden.bs.modal", () => {
    formPassword.reset();
    limpiarErrores(formPassword);
  });

  selectPais.addEventListener("change", actualizarLargoTelefono);
  inputTelefono.addEventListener("input", () => {
    inputTelefono.value = inputTelefono.value.replace(/\D/g, "");
  });

  formDatos.addEventListener("submit", guardarDatos);
  formPassword.addEventListener("submit", cambiarPassword);
}

function prepararFormularioDatos() {
  const { codigoPais, numero } = separarTelefono(cliente.telefono);

  limpiarErrores(formDatos);
  formDatos.elements.nombre.value = cliente.nombre || "";
  formDatos.elements.email.value = cliente.email || "";
  selectPais.value = codigoPais;
  inputTelefono.value = numero;
  actualizarLargoTelefono();
}

async function guardarDatos(evento) {
  evento.preventDefault();
  limpiarErrores(formDatos);
  const boton = formDatos.querySelector('[type="submit"]');

  try {
    const actualizado = await conBotonOcupado(
      boton,
      () => api("/cuenta", { metodo: "PUT", datos: datosFormulario(formDatos) }),
      "Guardando..."
    );

    cliente = { ...cliente, ...actualizado };
    mostrarCliente();
    if (!cliente.emailPendiente) {
      document.getElementById("aviso-email").hidden = true;
    }
    bootstrap.Modal.getOrCreateInstance(modalEditar).hide();
    toast("Datos actualizados correctamente.");
  } catch (error) {
    mostrarErrores(formDatos, error);
  }
}

async function cambiarPassword(evento) {
  evento.preventDefault();
  limpiarErrores(formPassword);
  const boton = formPassword.querySelector('[type="submit"]');

  try {
    await conBotonOcupado(
      boton,
      () => api("/cuenta/password", { metodo: "PUT", datos: datosFormulario(formPassword) }),
      "Guardando..."
    );

    formPassword.reset();
    bootstrap.Modal.getOrCreateInstance(modalEditar).hide();
    toast("Contraseña actualizada correctamente.");
  } catch (error) {
    mostrarErrores(formPassword, error);
  }
}

/* ---------- Historial de pedidos ---------- */

async function cargarPedidos() {
  historial.innerHTML = `
    <div class="estado-vacio">
      <div class="spinner-border text-warning" role="status" aria-hidden="true"></div>
      <p class="mt-2 mb-0">Cargando tus pedidos...</p>
    </div>`;

  try {
    mostrarPedidos(await api("/pedidos"));
  } catch (error) {
    historial.innerHTML = `
      <div class="alert alert-danger text-center">
        <p class="mb-2">${escapar(error.message)}</p>
        <button type="button" class="btn btn-outline-light btn-sm" id="reintentar-pedidos">Reintentar</button>
      </div>`;
    document.getElementById("reintentar-pedidos").addEventListener("click", cargarPedidos);
  }
}

/** Actualización periódica: si falla, se deja el historial que ya se estaba mostrando. */
async function refrescarPedidos() {
  if (document.hidden) return;
  try {
    const pedidos = await api("/pedidos");
    // Solo se vuelve a dibujar si algo cambió, para no cerrar detalles ni perder el foco.
    if (JSON.stringify(pedidos) !== ultimosPedidos) {
      mostrarPedidos(pedidos);
    }
  } catch {
    // se reintenta en el próximo ciclo
  }
}

function mostrarPedidos(pedidos) {
  ultimosPedidos = JSON.stringify(pedidos);

  if (!pedidos.length) {
    historial.innerHTML = `
      <div class="alert alert-dark text-center">
        <p class="mb-3">Todavía no hiciste ningún pedido.</p>
        <a href="${raiz()}index.html#menu" class="btn btn-gold">Ver el menú</a>
      </div>`;
    return;
  }

  // Se conservan abiertos los detalles que el cliente estaba mirando.
  const abiertos = new Set(
    [...historial.querySelectorAll(".pedido-detalle.show")].map((detalle) => detalle.dataset.id)
  );

  // Del más nuevo al más viejo. El número es el orden del pedido para este cliente (#1 es el primero).
  const ordenados = [...pedidos].sort((a, b) => String(b.fecha).localeCompare(String(a.fecha)));
  historial.innerHTML = ordenados
    .map((pedido, indice) => tarjetaPedido(pedido, ordenados.length - indice, abiertos.has(String(pedido.id))))
    .join("");
}

function tarjetaPedido(pedido, numero, abierto) {
  const idDetalle = `pedido-${pedido.id}`;

  return `
    <article class="pedido">
      <button type="button" class="pedido-resumen d-flex flex-wrap align-items-center gap-2" data-bs-toggle="collapse"
        data-bs-target="#${idDetalle}" aria-expanded="${abierto}" aria-controls="${idDetalle}">
        <span class="me-auto">
          <i class="fas fa-receipt me-2" aria-hidden="true"></i>Pedido #${numero}
          <small class="fw-normal ms-2">${escapar(fecha(pedido.fecha))}</small>
        </span>
        ${etiquetaEstado(pedido.estado)}
        ${pedido.pagado
          ? '<span class="badge text-bg-success">Pagado</span>'
          : '<span class="badge text-bg-secondary">Pago pendiente</span>'}
        <span class="badge text-bg-dark">${escapar(pedido.tipoEntrega || "Sin especificar")}</span>
        <span class="fs-5">${precio(pedido.total)}</span>
        <i class="fas fa-chevron-down pedido-flecha" aria-hidden="true"></i>
      </button>
      <div class="collapse pedido-detalle${abierto ? " show" : ""}" id="${idDetalle}" data-id="${pedido.id}">
        ${detallePedido(pedido)}
      </div>
    </article>`;
}

function etiquetaEstado(estado) {
  const { clase, icono } = ESTADOS[estado] || { clase: "text-bg-secondary", icono: "fa-circle-info" };
  return `<span class="badge ${clase}"><i class="fa-solid ${icono} me-1" aria-hidden="true"></i>${escapar(estado)}</span>`;
}

function detallePedido(pedido) {
  const items = (pedido.items || [])
    .map(
      (item) => `
      <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
        <div>
          <strong>${escapar(item.nombre)}</strong>
          <div class="small texto-ayuda">${precio(item.precio)} × ${item.cantidad}</div>
        </div>
        <span>${precio(item.precio * item.cantidad)}</span>
      </li>`
    )
    .join("");

  const nota = pedido.nota ? escapar(pedido.nota).replace(/\n/g, "<br>") : "Sin nota";
  const direccion = pedido.tipoEntrega === "Delivery" && pedido.direccion
    ? `<dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">${escapar(pedido.direccion)}</dd>`
    : "";
  const cancelado = pedido.estado === "Cancelado"
    ? '<p class="text-danger mb-3">Este pedido fue cancelado. Esperamos poder servirte mejor en el futuro.</p>'
    : "";

  return `
    ${cancelado}
    <dl class="row mb-3">
      <dt class="col-sm-4">Entrega</dt><dd class="col-sm-8">${escapar(pedido.tipoEntrega || "Sin especificar")}</dd>
      ${direccion}
      <dt class="col-sm-4">Método de pago</dt><dd class="col-sm-8">${escapar(pedido.metodoPago || "Sin especificar")}</dd>
      <dt class="col-sm-4">Nota</dt><dd class="col-sm-8">${nota}</dd>
    </dl>
    <h6 class="text-warning">Productos</h6>
    <ul class="list-group list-group-flush pedido-items mb-2">${items}</ul>
    <div class="d-flex justify-content-between fw-semibold fs-5 pt-2">
      <span>Total</span><span>${precio(pedido.total)}</span>
    </div>`;
}
