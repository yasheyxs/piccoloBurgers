import { api, ApiError } from "../api.js";
import { cargarCarrito, carritoActual, carritoConfirmado } from "../carrito.js";
import { iniciarLayout } from "../layout.js";
import { cargarNegocio } from "../negocio.js";
import { calcularCanje, cargarConfiguracionPuntos, olvidarUsarPuntos, puedeCanjear, quiereUsarPuntos } from "../puntos.js";
import { conBotonOcupado, datosFormulario, escapar, limpiarErrores, mostrarErrores, precio } from "../ui.js";

/** Largos válidos del número (sin el código de país), igual que en el backend. */
const LARGOS_TELEFONO = {
  54: [10],
  598: [8, 9],
  55: [10, 11],
  56: [9],
  595: [9],
  591: [8],
  51: [9],
  1: [10],
  34: [9],
};

/** Los errores del pedido llegan como un mensaje general: estos se marcan además en su campo. */
const CAMPOS_POR_MENSAJE = [
  [/tel[eé]fono/i, "telefono"],
  [/email/i, "email"],
  [/direcci[oó]n/i, "direccion"],
];

const formulario = document.getElementById("form-pedido");
const codigoPais = document.getElementById("codigo_pais");
const telefono = document.getElementById("telefono");

let cliente = null;
let usarPuntos = false;

iniciarPagina();
iniciarFormulario();

async function iniciarPagina() {
  let carrito;
  [cliente, carrito] = await Promise.all([
    iniciarLayout({ botonCarrito: false }),
    cargarCarrito().catch(() => null),
  ]);

  document.getElementById("cargando").hidden = true;

  if (!carrito || carrito.productos.length === 0) {
    const aviso = document.getElementById("carrito-vacio");
    if (!carrito) {
      aviso.querySelector("p").textContent = "No pudimos cargar tu carrito. Revisá tu conexión e intentá de nuevo.";
    }
    aviso.hidden = false;
    return;
  }

  const configuracion = cliente ? await cargarConfiguracionPuntos().catch(() => null) : null;
  usarPuntos = quiereUsarPuntos() && puedeCanjear(cliente, configuracion);

  mostrarResumen(carrito, configuracion);
  completarDatosCliente();
  formulario.hidden = false;
}

/* ---------- Resumen ---------- */

function mostrarResumen({ productos, total }, configuracion) {
  document.getElementById("resumen-productos").innerHTML = productos
    .map(
      (p) => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span>${escapar(p.nombre)} (x${p.cantidad})</span>
        <span>${precio(p.subtotal)}</span>
      </li>`
    )
    .join("");

  let totales = `<p class="fs-5 mb-0"><strong>Total:</strong> ${precio(total)}</p>`;

  if (usarPuntos) {
    const canje = calcularCanje(total, Number(cliente.puntos), configuracion);
    if (canje.puntosUsados > 0) {
      totales = `
        <p class="mb-0">Subtotal: ${precio(total)}</p>
        <p class="text-success fw-semibold mb-0">
          <i class="fa-solid fa-coins me-1" aria-hidden="true"></i>- ${precio(canje.descuento)} con ${canje.puntosUsados} puntos
        </p>
        <p class="fs-5 mb-0"><strong>Total:</strong> ${precio(total - canje.descuento)}</p>`;
    }
  }

  document.getElementById("resumen-totales").innerHTML = totales;
  document.getElementById("resumen-pedido").hidden = false;
}

/* ---------- Formulario ---------- */

function completarDatosCliente() {
  if (!cliente) return;

  formulario.nombre.value = cliente.nombre || "";
  formulario.email.value = cliente.email || "";

  const partes = separarTelefono(cliente.telefono);
  if (partes) {
    codigoPais.value = partes.codigo;
    telefono.value = partes.numero;
  }
  ajustarLargoTelefono();
}

/** "+540000825022" → { codigo: "54", numero: "0000825022" }. Prueba primero los códigos más largos. */
function separarTelefono(valor) {
  const digitos = String(valor || "").replace(/^\+|^00/, "").replace(/\D/g, "");
  const codigos = Object.keys(LARGOS_TELEFONO).sort((a, b) => b.length - a.length);

  for (const codigo of codigos) {
    const numero = digitos.slice(codigo.length);
    if (digitos.startsWith(codigo) && LARGOS_TELEFONO[codigo].includes(numero.length)) {
      return { codigo, numero };
    }
  }
  return null;
}

function telefonoValido() {
  const largos = LARGOS_TELEFONO[codigoPais.value] || [];
  return largos.includes(telefono.value.length);
}

function ajustarLargoTelefono() {
  telefono.maxLength = Math.max(...(LARGOS_TELEFONO[codigoPais.value] || [15]));
}

function mostrarCamposDelivery() {
  const esDelivery = formulario.tipoEntrega.value === "Delivery";
  document.getElementById("grupo-direccion").hidden = !esDelivery;
  document.getElementById("grupo-referencias").hidden = !esDelivery;
  document.getElementById("aviso-delivery").hidden = !esDelivery;
  formulario.direccion.required = esDelivery;
}

function iniciarFormulario() {
  ajustarLargoTelefono();

  codigoPais.addEventListener("change", ajustarLargoTelefono);
  telefono.addEventListener("input", () => {
    telefono.value = telefono.value.replace(/\D/g, "");
  });
  formulario.nombre.addEventListener("input", (evento) => {
    evento.target.value = evento.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g, "");
  });
  formulario.querySelectorAll('[name="tipoEntrega"]').forEach((radio) => {
    radio.addEventListener("change", mostrarCamposDelivery);
  });

  formulario.addEventListener("submit", enviarPedido);
}

async function enviarPedido(evento) {
  evento.preventDefault();
  limpiarErrores(formulario);

  if (!telefonoValido()) {
    mostrarErrores(
      formulario,
      new ApiError("Revisá los datos marcados.", 422, { telefono: "Ingresá un número de teléfono válido." })
    );
    telefono.focus();
    return;
  }

  const datos = datosFormulario(formulario);
  const esDelivery = datos.tipoEntrega === "Delivery";
  const pedido = {
    nombre: datos.nombre.trim(),
    codigoPais: datos.codigoPais,
    telefono: datos.telefono,
    email: datos.email.trim(),
    nota: datos.nota.trim(),
    metodoPago: datos.metodoPago,
    tipoEntrega: datos.tipoEntrega,
    direccion: esDelivery ? datos.direccion.trim() : "",
    referencias: esDelivery ? datos.referencias.trim() : "",
    usarPuntos,
    productos: carritoActual().productos.map(({ id, cantidad }) => ({ id, cantidad })),
  };

  try {
    const resultado = await conBotonOcupado(
      document.getElementById("btn-enviar"),
      () => api("/pedidos", { metodo: "POST", datos: pedido }),
      "Enviando pedido..."
    );
    carritoConfirmado();
    olvidarUsarPuntos();
    // Los datos para pagar (alias, titular, WhatsApp) vienen de los datos del local
    const negocio = await cargarNegocio().catch(() => null);
    mostrarAgradecimiento(resultado, pedido, negocio);
  } catch (error) {
    mostrarErrores(formulario, errorConCampo(error));
    formulario.querySelector(".is-invalid, [data-mensaje]")?.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

/** Si el mensaje de la API se refiere a un campo, lo marca también en ese campo. */
function errorConCampo(error) {
  if (error.status !== 422 || Object.keys(error.errores).length > 0) return error;

  const coincidencia = CAMPOS_POR_MENSAJE.find(([patron]) => patron.test(error.message));
  if (!coincidencia) return error;

  return new ApiError("Revisá los datos marcados.", error.status, { [coincidencia[1]]: error.message });
}

/* ---------- Pedido confirmado ---------- */

function mostrarAgradecimiento(resultado, pedido, negocio) {
  const contenedor = document.createElement("div");
  contenedor.innerHTML = `
  <div class="modal fade modal-gracias" id="modalGracias" tabindex="-1" aria-labelledby="modalGraciasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header border-0 px-4 py-3">
          <h5 class="modal-title fw-bold d-flex align-items-center" id="modalGraciasLabel">
            <i class="fas fa-check-circle text-success me-2 fa-lg" aria-hidden="true"></i>¡Gracias por tu pedido!
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body fs-5 px-4 py-3">
          <p><i class="fa-solid fa-circle-check text-success me-2" aria-hidden="true"></i><strong>${escapar(pedido.nombre)}</strong>, tu pedido está en preparación.</p>
          ${detalleDescuento(resultado)}
          <p class="mt-3"><i class="fa-solid fa-cash-register me-2" aria-hidden="true"></i><strong>Total a pagar:</strong> ${precio(resultado.total)}</p>
          ${resultado.puntosGanados > 0 ? `<p><i class="fa-solid fa-gift me-2" aria-hidden="true"></i><strong>Puntos ganados:</strong> ${resultado.puntosGanados}</p>` : ""}
          ${pedido.metodoPago === "MercadoPago" && negocio ? datosMercadoPago(pedido.tipoEntrega === "Delivery", negocio) : ""}
        </div>
        <div class="modal-footer border-0 px-4 py-3 justify-content-center">
          <a href="index.html" class="btn btn-gold px-4">Volver al inicio</a>
        </div>
      </div>
    </div>
  </div>`;

  const modal = contenedor.firstElementChild;
  document.body.append(modal);
  // El pedido ya está hecho: al cerrar el aviso se vuelve al inicio
  modal.addEventListener("hidden.bs.modal", () => {
    window.location.href = "index.html";
  });
  new bootstrap.Modal(modal).show();
}

function detalleDescuento(resultado) {
  if (!(resultado.descuento > 0)) return "";

  return `
    <div class="mb-2">
      <p class="mb-0"><i class="fa-solid fa-money-bill-wave me-2" aria-hidden="true"></i><strong>Total original:</strong> ${precio(resultado.totalOriginal)}</p>
      <p class="mb-0"><i class="fa-solid fa-arrow-down me-2" aria-hidden="true"></i><strong>Descuento por puntos:</strong> -${precio(resultado.descuento)}</p>
      ${resultado.puntosUsados > 0 ? `<p class="mb-0"><i class="fa-solid fa-coins me-2" aria-hidden="true"></i><strong>Puntos utilizados:</strong> ${resultado.puntosUsados}</p>` : ""}
    </div>`;
}

function datosMercadoPago(esDelivery, negocio) {
  return `
    <div class="pago-mercadopago p-4 mt-4 rounded">
      <h5 class="mb-3"><i class="fa-solid fa-mobile-screen-button me-2" aria-hidden="true"></i>Pagá por Mercado Pago</h5>
      <p class="mb-2"><strong>Alias:</strong> ${escapar(negocio.pago.alias)}</p>
      <p class="mb-2"><strong>Nombre del titular:</strong> ${escapar(negocio.pago.titular)}</p>
      <p class="mb-2">
        Enviá el comprobante por WhatsApp a:
        <a href="${escapar(negocio.whatsapp.url)}" target="_blank" rel="noopener">${escapar(negocio.whatsapp.visible)}</a>
      </p>
      ${
        esDelivery
          ? `<p class="aviso-costo-delivery mt-3 mb-0">
               <i class="fa-solid fa-money-bill-wave me-2" aria-hidden="true"></i>El costo del delivery varía <strong>${escapar(negocio.costoDelivery)}</strong>.
               Envianos un mensaje para confirmar el monto.
             </p>`
          : ""
      }
    </div>`;
}
