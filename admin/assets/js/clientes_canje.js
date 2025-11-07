(function () {
  "use strict";

  const data = window.ClienteCanjeData || {};
  const cliente = data.cliente || {};
  const config = data.config || {};
  const menuDisponible = Array.isArray(data.menu) ? data.menu : [];
  const premiosDisponibles = Array.isArray(data.premios) ? data.premios : [];

  const reservasEndpoint =
    typeof data.reservas_endpoint === "string" && data.reservas_endpoint
      ? data.reservas_endpoint
      : "../public/api/carrito_actualizar.php";

  const disponibilidadEndpoint =
    typeof data.disponibilidad_endpoint === "string" &&
    data.disponibilidad_endpoint
      ? data.disponibilidad_endpoint
      : "../public/api/disponibilidad_menu.php";

  const productosPorId = new Map(
    menuDisponible
      .map((item) => {
        const id = Number(item.id);
        return Number.isInteger(id) && id > 0 ? [id, item] : null;
      })
      .filter(Boolean)
  );

  const elementos = {
    modoRadios: document.querySelectorAll('input[name="modoCanje"]'),
    panelDescuento: document.getElementById("modoDescuentoPanel"),
    panelPremios: document.getElementById("modoPremiosPanel"),
    contenedorAlertas: document.getElementById("alertasCanje"),

    filtroNombre: document.getElementById("filtroNombre"),
    filtroCategoria: document.getElementById("filtroCategoria"),
    listadoProductos: document.getElementById("listadoProductos"),
    estadoProductos: document.getElementById("estadoListadoProductos"),
    tablaProductos: document.getElementById("tablaProductosSeleccionados"),
    btnVaciar: document.getElementById("btnVaciarSeleccion"),

    resumenTotalParcial: document.getElementById("resumenTotalParcial"),
    resumenPuntosAplicados: document.getElementById("resumenPuntosAplicados"),
    resumenDescuento: document.getElementById("resumenDescuento"),
    resumenTotalFinal: document.getElementById("resumenTotalFinal"),

    inputPuntosPersonalizados: document.getElementById(
      "inputPuntosPersonalizados"
    ),
    btnMaximoPuntos: document.getElementById("btnMaximoPuntos"),
    textoAyudaPuntos: document.getElementById("ayudaPuntos"),
    errorPuntosPersonalizados: document.getElementById(
      "errorPuntosPersonalizados"
    ),
    alertaMinimoPuntos: document.getElementById("alertaMinimoPuntos"),
    alertaMinimoPuntosTexto: document.getElementById("alertaMinimoPuntosTexto"),
    filaPuntosAplicados: document.getElementById("filaPuntosAplicados"),

    selectMetodoPago: document.getElementById("selectMetodoPago"),
    selectTipoEntrega: document.getElementById("selectTipoEntrega"),
    camposDelivery: document.getElementById("camposDelivery"),
    inputDireccion: document.getElementById("inputDireccion"),
    inputReferencias: document.getElementById("inputReferencias"),
    textareaNota: document.getElementById("textareaNota"),
    btnRegistrarVenta: document.getElementById("btnRegistrarVenta"),

    puntosClienteResumen: document.getElementById("puntosClienteValor"),

    listadoPremios: document.getElementById("listadoPremios"),
    estadoPremios: document.getElementById("estadoListadoPremios"),
    puntosDisponiblesPremios: document.getElementById(
      "premiosPuntosDisponibles"
    ),
    puntosUsadosPremios: document.getElementById("premiosPuntosUsados"),
    btnConfirmarPremios: document.getElementById("btnConfirmarPremios"),

    modalResumen: document.getElementById("resumenVentaModal"),
    modalResumenProductos: document.getElementById("resumenVentaProductos"),
    modalResumenTotalOriginal: document.getElementById(
      "resumenVentaTotalOriginal"
    ),
    modalResumenPuntosUsados: document.getElementById(
      "resumenVentaPuntosUsados"
    ),
    modalResumenDescuento: document.getElementById("resumenVentaDescuento"),
    modalResumenTotalFinal: document.getElementById("resumenVentaTotalFinal"),
    modalResumenPuntosGanados: document.getElementById(
      "resumenVentaPuntosGanados"
    ),
    modalResumenPuntosActuales: document.getElementById(
      "resumenVentaPuntosActuales"
    ),
    modalResumenPedidoId: document.getElementById("resumenVentaPedidoId"),
    modalResumenMetodoPago: document.getElementById("resumenVentaMetodoPago"),
    modalResumenTipoEntrega: document.getElementById("resumenVentaTipoEntrega"),
    modalCanjePremios: document.getElementById("modalCanjePremios"),
    modalCanjePremiosMensaje: document.getElementById(
      "modalCanjePremiosMensaje"
    ),
    modalCanjePremiosDetalle: document.getElementById(
      "modalCanjePremiosDetalle"
    ),
    modalCanjePremiosPuntosUsados: document.getElementById(
      "modalCanjePremiosPuntosUsados"
    ),
    modalCanjePremiosPuntosRestantes: document.getElementById(
      "modalCanjePremiosPuntosRestantes"
    ),
    modalCanjePremiosFecha: document.getElementById("modalCanjePremiosFecha"),
    modalCanjePremiosDescripcion: document.getElementById(
      "modalCanjePremiosDescripcion"
    ),
  };

  const formatoMoneda = new Intl.NumberFormat("es-AR", {
    style: "currency",
    currency: "ARS",
    minimumFractionDigits: 2,
  });

  const formatoEntero = new Intl.NumberFormat("es-AR", {
    maximumFractionDigits: 0,
  });

  const state = {
    puntosDisponibles: Number.isFinite(Number(cliente.puntos))
      ? Number(cliente.puntos)
      : 0,
    seleccionProductos: new Map(),
    seleccionPremios: new Map(),
    resumenDescuento: {
      totalParcial: 0,
      puntosIngresados: 0,
      puntosAplicados: 0,
      descuento: 0,
      totalFinal: 0,
      maximoPuntosPermitidos: 0,
      faltaMinimo: false,
      maximoInferiorAlMinimo: false,
      minimoRequerido: Number(config.minimo_puntos) || 0,
      valorPunto: Number(config.valor_punto) || 0,
    },
    puntosEditadosManualmente: false,
    disponibilidadProductos: new Map(),
    sincronizandoReservas: false,
  };

  const valoresConfig = {
    minimo: Number(config.minimo_puntos) || 0,
    valorPunto: Number(config.valor_punto) || 0,
    maximoPorcentaje: Number(config.maximo_porcentaje) || 0,
  };

  let modalResumenInstance = null;
  let modalCanjePremiosInstance = null;

  function mostrarAlerta(tipo, mensaje) {
    if (!elementos.contenedorAlertas) {
      return;
    }

    elementos.contenedorAlertas.innerHTML = "";

    if (!mensaje) {
      elementos.contenedorAlertas.classList.add("d-none");
      return;
    }

    const alerta = document.createElement("div");
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.setAttribute("role", "alert");
    alerta.innerHTML = `
      ${mensaje}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;

    elementos.contenedorAlertas.appendChild(alerta);
    elementos.contenedorAlertas.classList.remove("d-none");
  }

  function formatearMoneda(valor) {
    try {
      return formatoMoneda.format(valor || 0);
    } catch (_error) {
      const numero = Number(valor) || 0;
      return `$${numero.toFixed(2)}`;
    }
  }

  function formatearEntero(valor) {
    try {
      return formatoEntero.format(valor || 0);
    } catch (_error) {
      const numero = Number(valor) || 0;
      return Math.round(numero).toString();
    }
  }

  function formatearFechaCompleta(valor) {
    if (!valor) {
      return new Date().toLocaleString("es-AR");
    }

    const fecha = new Date(valor);
    if (Number.isNaN(fecha.getTime())) {
      return new Date().toLocaleString("es-AR");
    }

    try {
      return fecha.toLocaleString("es-AR", {
        dateStyle: "short",
        timeStyle: "short",
      });
    } catch (_error) {
      return fecha.toLocaleString("es-AR");
    }
  }

  function obtenerProductoPorId(menuId) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0) {
      return null;
    }

    return productosPorId.get(id) || null;
  }

  function actualizarDisponibilidadMapa(disponibilidad = {}) {
    if (!disponibilidad || typeof disponibilidad !== "object") {
      return;
    }

    Object.values(disponibilidad).forEach((entrada) => {
      if (!entrada) {
        return;
      }

      const id = Number(
        entrada.menu_id ?? entrada.id ?? entrada.menuId ?? entrada.producto_id
      );

      if (!Number.isInteger(id) || id <= 0) {
        return;
      }

      const unidades = Number(
        entrada.unidades_disponibles ??
          entrada.disponibles ??
          entrada.disponible
      );

      if (Number.isFinite(unidades)) {
        state.disponibilidadProductos.set(
          id,
          Math.max(0, Math.floor(unidades))
        );
      }
    });
  }

  function obtenerDisponibilidadRestante(menuId) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0) {
      return null;
    }

    const valor = state.disponibilidadProductos.get(id);
    return Number.isFinite(valor) ? valor : null;
  }

  function actualizarBotonesDisponibilidad() {
    if (elementos.listadoProductos) {
      elementos.listadoProductos
        .querySelectorAll("button[data-menu-id]")
        .forEach((boton) => {
          const id = Number(boton.dataset.menuId);
          const disponibles = obtenerDisponibilidadRestante(id);
          const hayDisponibilidad =
            disponibles === null ? true : disponibles > 0;

          boton.disabled = !hayDisponibilidad;

          if (hayDisponibilidad) {
            boton.classList.add("btn-outline-primary");
            boton.classList.remove("btn-outline-secondary");
            boton.textContent = "Agregar";
          } else {
            boton.classList.remove("btn-outline-primary");
            boton.classList.add("btn-outline-secondary");
            boton.textContent = "Sin stock";
          }
        });
    }

    elementos.tablaProductos
      ?.querySelectorAll("tr[data-producto-id]")
      .forEach((fila) => {
        const id = Number(fila.dataset.productoId);
        const disponibles = obtenerDisponibilidadRestante(id);
        const puedeAgregar = disponibles === null ? true : disponibles > 0;
        const botonIncrementar = fila.querySelector(
          'button[data-accion="incrementar"]'
        );

        if (botonIncrementar) {
          botonIncrementar.disabled = !puedeAgregar;
        }
      });
  }

  async function actualizarDisponibilidadDesdeServidor(menuIds = null) {
    const idsEntrada = Array.isArray(menuIds) ? menuIds : null;
    const ids =
      idsEntrada && idsEntrada.length > 0
        ? idsEntrada
        : menuDisponible
            .map((item) => Number(item.id))
            .filter((id) => Number.isInteger(id) && id > 0);

    if (!disponibilidadEndpoint || ids.length === 0) {
      return;
    }

    try {
      const respuesta = await fetch(disponibilidadEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ menuIds: ids }),
      });

      let datos;
      try {
        datos = await respuesta.json();
      } catch (_error) {
        throw new Error(
          "No se pudo interpretar la respuesta de disponibilidad."
        );
      }

      if (!respuesta.ok || !datos?.exito) {
        throw new Error(
          datos?.mensaje || "No se pudo obtener la disponibilidad actual."
        );
      }

      actualizarDisponibilidadMapa(datos.disponibilidad || {});
      actualizarBotonesDisponibilidad();
    } catch (error) {
      console.error("No se pudo actualizar la disponibilidad:", error);
    }
  }

  function aplicarReservasDesdeServidor(reservas, disponibilidad) {
    state.seleccionProductos.clear();

    const entradas = Array.isArray(reservas)
      ? reservas
      : Object.values(reservas || {});

    entradas.forEach((entrada) => {
      if (!entrada) {
        return;
      }

      const id = Number(entrada.menu_id ?? entrada.id ?? entrada.menuId);
      const cantidad = Number(entrada.cantidad ?? entrada.qty);

      if (
        !Number.isInteger(id) ||
        id <= 0 ||
        !Number.isFinite(cantidad) ||
        cantidad <= 0
      ) {
        return;
      }

      const producto = obtenerProductoPorId(id);
      if (!producto) {
        return;
      }

      state.seleccionProductos.set(id, {
        id,
        nombre: producto.nombre,
        precio: Number(producto.precio) || 0,
        cantidad: Math.max(0, Math.floor(cantidad)),
      });
    });

    actualizarDisponibilidadMapa(disponibilidad || {});
    actualizarTablaSeleccion();
    actualizarBotonesDisponibilidad();
  }

  async function solicitarAccionReservas(payload) {
    if (!reservasEndpoint) {
      throw new Error("No se configuró el servicio de reservas.");
    }

    state.sincronizandoReservas = true;

    try {
      const respuesta = await fetch(reservasEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      let datos;
      try {
        datos = await respuesta.json();
      } catch (_error) {
        throw new Error("No se pudo interpretar la respuesta del servidor.");
      }

      if (!respuesta.ok || !datos?.exito) {
        throw new Error(
          datos?.mensaje || "No se pudo actualizar la reserva seleccionada."
        );
      }

      aplicarReservasDesdeServidor(
        datos.reservas || {},
        datos.disponibilidad || {}
      );

      return datos;
    } catch (error) {
      throw error;
    } finally {
      state.sincronizandoReservas = false;
    }
  }

  async function inicializarReservas() {
    try {
      await solicitarAccionReservas({ accion: "limpiar" });
    } catch (error) {
      console.error("No se pudieron inicializar las reservas:", error);
      await actualizarDisponibilidadDesdeServidor();
    }
  }

  function obtenerPuntosIngresados() {
    if (!elementos.inputPuntosPersonalizados) {
      return 0;
    }

    const valor = Number(elementos.inputPuntosPersonalizados.value);
    if (!Number.isFinite(valor)) {
      return 0;
    }

    return Math.max(0, Math.floor(valor));
  }

  function establecerPuntosIngresados(valor) {
    if (!elementos.inputPuntosPersonalizados) {
      return;
    }

    const actual = Number(elementos.inputPuntosPersonalizados.value);
    if (Number.isFinite(actual) && actual === valor) {
      return;
    }

    elementos.inputPuntosPersonalizados.value = String(valor);
  }

  function crearTarjetaProducto(producto) {
    const col = document.createElement("div");
    col.className = "col-12 col-sm-6 col-xl-4";

    const card = document.createElement("div");
    card.className = "card h-100 shadow-sm";

    const placeholder = document.createElement("div");
    placeholder.className =
      "canje-producto-placeholder d-flex align-items-center justify-content-center";
    placeholder.innerHTML =
      '<i class="fa-solid fa-burger fa-2x" aria-hidden="true"></i>';
    card.appendChild(placeholder);

    const cuerpo = document.createElement("div");
    cuerpo.className = "card-body d-flex flex-column";

    const titulo = document.createElement("h5");
    titulo.className = "card-title";
    titulo.textContent = producto.nombre;

    const categoria = document.createElement("p");
    categoria.className = "card-text text-muted small mb-2";
    categoria.textContent = producto.categoria;

    const precio = document.createElement("p");
    precio.className = "card-text fw-semibold";
    precio.textContent = formatearMoneda(producto.precio);

    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "btn btn-outline-primary mt-auto";
    boton.textContent = "Agregar";
    boton.dataset.menuId = String(producto.id);

    cuerpo.append(titulo, categoria, precio, boton);
    card.appendChild(cuerpo);
    col.appendChild(card);

    return col;
  }

  function filtrarProductos() {
    const texto = (elementos.filtroNombre?.value || "").toLowerCase();
    const categoriaSeleccionada = elementos.filtroCategoria?.value || "";

    const filtrados = menuDisponible.filter((producto) => {
      const coincideCategoria =
        !categoriaSeleccionada || producto.categoria === categoriaSeleccionada;

      const coincideTexto =
        !texto ||
        producto.nombre.toLowerCase().includes(texto) ||
        (producto.categoria || "").toLowerCase().includes(texto);

      return coincideCategoria && coincideTexto;
    });

    return filtrados;
  }

  function renderizarProductos() {
    if (!elementos.listadoProductos) {
      return;
    }

    const productos = filtrarProductos();

    elementos.listadoProductos.innerHTML = "";

    if (productos.length === 0) {
      if (elementos.estadoProductos) {
        elementos.estadoProductos.textContent =
          "No encontramos productos que coincidan con tu búsqueda.";
        elementos.estadoProductos.classList.remove("d-none");
      }
      return;
    }

    if (elementos.estadoProductos) {
      elementos.estadoProductos.classList.add("d-none");
    }

    const fragmento = document.createDocumentFragment();
    productos.forEach((producto) => {
      fragmento.appendChild(crearTarjetaProducto(producto));
    });

    elementos.listadoProductos.appendChild(fragmento);
    actualizarBotonesDisponibilidad();
  }

  async function agregarProducto(menuId) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0) {
      return false;
    }

    try {
      await solicitarAccionReservas({ accion: "agregar", menuId: id });
      mostrarAlerta("", "");
      return true;
    } catch (error) {
      mostrarAlerta(
        "warning",
        error.message || "No se pudo agregar el producto seleccionado."
      );
      await actualizarDisponibilidadDesdeServidor([id]);
      return false;
    }
  }

  async function eliminarProducto(menuId) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0) {
      return false;
    }

    try {
      await solicitarAccionReservas({ accion: "eliminar", menuId: id });
      mostrarAlerta("", "");
      return true;
    } catch (error) {
      mostrarAlerta(
        "warning",
        error.message || "No se pudo quitar el producto seleccionado."
      );
      await actualizarDisponibilidadDesdeServidor([id]);
      return false;
    }
  }

  async function ajustarCantidad(menuId, delta) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0 || delta === 0) {
      return false;
    }

    const accion = delta > 0 ? "agregar" : "restar";
    try {
      await solicitarAccionReservas({ accion, menuId: id });
      mostrarAlerta("", "");
      return true;
    } catch (error) {
      mostrarAlerta(
        "warning",
        error.message || "No se pudo actualizar la cantidad del producto."
      );
      await actualizarDisponibilidadDesdeServidor([id]);
      return false;
    }
  }

  function crearFilaProducto({ id, nombre, precio, cantidad }) {
    const fila = document.createElement("tr");
    fila.dataset.productoId = String(id);

    const celdaNombre = document.createElement("td");
    celdaNombre.innerHTML = `
      <div class="fw-semibold">${nombre}</div>
      <div class="text-muted small">${formatearMoneda(precio)} c/u</div>
    `;

    const celdaCantidad = document.createElement("td");
    celdaCantidad.className = "text-center";
    celdaCantidad.innerHTML = `
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-secondary" data-accion="decrementar" aria-label="Restar">-</button>
        <span class="px-2" data-role="cantidad">${cantidad}</span>
        <button type="button" class="btn btn-outline-secondary" data-accion="incrementar" aria-label="Sumar">+</button>
      </div>
    `;

    const celdaSubtotal = document.createElement("td");
    celdaSubtotal.className = "text-end";
    celdaSubtotal.textContent = formatearMoneda(precio * cantidad);

    const celdaAcciones = document.createElement("td");
    celdaAcciones.className = "text-center";
    celdaAcciones.innerHTML = `
      <button type="button" class="btn btn-outline-danger btn-sm" data-accion="eliminar" aria-label="Quitar">
        <i class="fa-solid fa-trash"></i>
      </button>
    `;

    fila.append(celdaNombre, celdaCantidad, celdaSubtotal, celdaAcciones);
    return fila;
  }

  function actualizarTablaSeleccion() {
    if (!elementos.tablaProductos) {
      return;
    }

    elementos.tablaProductos.innerHTML = "";

    const productos = Array.from(state.seleccionProductos.values());
    if (productos.length === 0) {
      const vacio = document.createElement("tr");
      vacio.dataset.estado = "vacio";
      vacio.innerHTML =
        '<td colspan="4" class="text-center text-muted py-4">Todavía no agregaste productos.</td>';
      elementos.tablaProductos.appendChild(vacio);
      state.resumenDescuento = {
        totalParcial: 0,
        puntosAplicados: 0,
        descuento: 0,
        totalFinal: 0,
        maximoPuntosPermitidos: 0,
      };
      actualizarResumenDescuento();
      actualizarBotonesDisponibilidad();
      return;
    }

    const fragmento = document.createDocumentFragment();
    productos.forEach((producto) => {
      fragmento.appendChild(crearFilaProducto(producto));
    });

    elementos.tablaProductos.appendChild(fragmento);
    actualizarResumenDescuento();
    actualizarBotonesDisponibilidad();
  }

  function calcularResumenDescuento() {
    const productos = Array.from(state.seleccionProductos.values());
    const totalParcial = productos.reduce(
      (acum, item) => acum + item.precio * item.cantidad,
      0
    );

    const valorPunto = Math.max(0, valoresConfig.valorPunto);
    const maximoPorcentaje = Math.min(
      1,
      Math.max(0, valoresConfig.maximoPorcentaje)
    );
    const minimoPuntos = Math.max(0, valoresConfig.minimo);

    const maximoDescuentoPesos =
      maximoPorcentaje > 0 ? totalParcial * maximoPorcentaje : 0;
    const maximoPuntosPorVenta =
      valorPunto > 0 ? Math.floor(maximoDescuentoPesos / valorPunto) : 0;
    const maximoPermitido = Math.max(
      0,
      Math.min(maximoPuntosPorVenta, state.puntosDisponibles)
    );

    const sinMinimoDisponible = state.puntosDisponibles < minimoPuntos;
    const maximoInferiorAlMinimo =
      maximoPermitido > 0 && maximoPermitido < minimoPuntos && totalParcial > 0;

    let puntosIngresados = state.puntosEditadosManualmente
      ? obtenerPuntosIngresados()
      : maximoPermitido;

    if (!Number.isFinite(puntosIngresados) || puntosIngresados < 0) {
      puntosIngresados = 0;
    }

    if (puntosIngresados > maximoPermitido) {
      puntosIngresados = maximoPermitido;
    }

    const puedeCanjear =
      totalParcial > 0 &&
      maximoPermitido > 0 &&
      !sinMinimoDisponible &&
      !maximoInferiorAlMinimo;

    if (!state.puntosEditadosManualmente && puedeCanjear) {
      puntosIngresados = maximoPermitido;
    }

    let error = "";
    if (puntosIngresados > 0 && puntosIngresados < minimoPuntos) {
      error = `Debés ingresar al menos ${formatearEntero(
        minimoPuntos
      )} puntos.`;
    }

    if (!puedeCanjear) {
      puntosIngresados = 0;
    }

    const puntosAplicados = !error && puedeCanjear ? puntosIngresados : 0;

    const descuento = Math.min(
      maximoDescuentoPesos,
      puntosAplicados * valorPunto,
      totalParcial
    );
    const totalFinal = Math.max(0, totalParcial - descuento);

    return {
      totalParcial,
      puntosIngresados,
      puntosAplicados,
      descuento,
      totalFinal,
      maximoPuntosPermitidos: maximoPermitido,
      errorPuntos: error,
      faltaMinimo: sinMinimoDisponible,
      maximoInferiorAlMinimo,
      minimoRequerido: minimoPuntos,
      valorPunto,
    };
  }

  function actualizarResumenDescuento() {
    const resumen = calcularResumenDescuento();
    state.resumenDescuento = resumen;

    if (elementos.resumenTotalParcial) {
      elementos.resumenTotalParcial.textContent = formatearMoneda(
        resumen.totalParcial
      );
    }

    if (elementos.resumenPuntosAplicados) {
      const textoPuntos = `${formatearEntero(resumen.puntosAplicados)} pts`;
      const textoMoneda = formatearMoneda(resumen.descuento);
      elementos.resumenPuntosAplicados.textContent = `${textoPuntos} (${textoMoneda})`;
    }

    if (elementos.resumenDescuento) {
      elementos.resumenDescuento.textContent = `-${formatearMoneda(
        resumen.descuento
      )}`;
    }

    if (elementos.resumenTotalFinal) {
      elementos.resumenTotalFinal.textContent = formatearMoneda(
        resumen.totalFinal
      );
    }

    const valorManualActual = obtenerPuntosIngresados();

    establecerPuntosIngresados(resumen.puntosIngresados);

    if (elementos.textoAyudaPuntos) {
      const partes = [
        `Disponible: ${formatearEntero(state.puntosDisponibles)} pts`,
      ];
      if (resumen.maximoPuntosPermitidos > 0) {
        partes.push(
          `Máximo por venta: ${formatearEntero(
            resumen.maximoPuntosPermitidos
          )} pts`
        );
      }
      if (resumen.minimoRequerido > 0) {
        partes.push(
          `Mínimo para canjear: ${formatearEntero(resumen.minimoRequerido)} pts`
        );
      }
      elementos.textoAyudaPuntos.textContent = partes.join(" · ");
    }

    if (elementos.errorPuntosPersonalizados) {
      if (resumen.errorPuntos) {
        elementos.errorPuntosPersonalizados.textContent = resumen.errorPuntos;
        elementos.errorPuntosPersonalizados.classList.remove("d-none");
        elementos.inputPuntosPersonalizados?.classList.add("is-invalid");
      } else {
        elementos.errorPuntosPersonalizados.classList.add("d-none");
        elementos.inputPuntosPersonalizados?.classList.remove("is-invalid");
      }
    }

    const ingresoManualPuntos =
      state.puntosEditadosManualmente && valorManualActual > 0;
    const mostrarAlertaMinimo =
      resumen.faltaMinimo || resumen.maximoInferiorAlMinimo;
    const bloquearPorMinimo = ingresoManualPuntos && mostrarAlertaMinimo;

    if (elementos.alertaMinimoPuntos) {
      if (mostrarAlertaMinimo) {
        const texto = resumen.faltaMinimo
          ? "El cliente aún no tiene puntos disponibles para realizar un canje por descuento. Todavía puede canjear premios."
          : "El monto del pedido no permite alcanzar el mínimo de puntos para aplicar un descuento.";
        if (elementos.alertaMinimoPuntosTexto) {
          elementos.alertaMinimoPuntosTexto.textContent = texto;
        } else {
          elementos.alertaMinimoPuntos.innerText = texto;
        }
        elementos.alertaMinimoPuntos.classList.remove("d-none");
      } else {
        elementos.alertaMinimoPuntos.classList.add("d-none");
      }
    }

    const puedeEditarPuntos =
      state.seleccionProductos.size > 0 &&
      !resumen.faltaMinimo &&
      !resumen.maximoInferiorAlMinimo &&
      resumen.maximoPuntosPermitidos > 0;

    if (elementos.inputPuntosPersonalizados) {
      elementos.inputPuntosPersonalizados.disabled = !puedeEditarPuntos;
      if (!puedeEditarPuntos) {
        elementos.inputPuntosPersonalizados.classList.remove("is-invalid");
      }
    }

    if (elementos.btnMaximoPuntos) {
      elementos.btnMaximoPuntos.disabled = !puedeEditarPuntos;
    }

    if (elementos.btnRegistrarVenta) {
      const hayProductos = state.seleccionProductos.size > 0;
      const puedeRegistrar =
        hayProductos &&
        !resumen.errorPuntos &&
        !bloquearPorMinimo &&
        !state.sincronizandoReservas;
      elementos.btnRegistrarVenta.disabled = !puedeRegistrar;
    }
  }

  async function vaciarSeleccionProductos({ mostrarMensajes = true } = {}) {
    if (state.seleccionProductos.size === 0) {
      state.puntosEditadosManualmente = false;
      establecerPuntosIngresados(0);
      actualizarTablaSeleccion();
      return;
    }

    try {
      await solicitarAccionReservas({ accion: "limpiar" });
      if (mostrarMensajes) {
        mostrarAlerta("", "");
      }
    } catch (error) {
      if (mostrarMensajes) {
        mostrarAlerta(
          "warning",
          error.message || "No se pudo vaciar la selección de productos."
        );
      } else {
        console.error("No se pudo vaciar la selección de productos:", error);
      }
      await actualizarDisponibilidadDesdeServidor();
    } finally {
      state.puntosEditadosManualmente = false;
      establecerPuntosIngresados(0);
      if (state.seleccionProductos.size === 0) {
        actualizarTablaSeleccion();
      }
    }
  }

  function seleccionarMaximoPuntos() {
    const maximo = state.resumenDescuento.maximoPuntosPermitidos || 0;
    establecerPuntosIngresados(maximo);
    actualizarResumenDescuento();
  }

  function toggleCamposDelivery() {
    if (!elementos.selectTipoEntrega || !elementos.camposDelivery) {
      return;
    }

    const requiereDelivery = elementos.selectTipoEntrega.value === "Delivery";
    elementos.camposDelivery.classList.toggle("d-none", !requiereDelivery);
  }

  function recolectarProductosSeleccionados() {
    return Array.from(state.seleccionProductos.values()).map((item) => ({
      id: item.id,
      cantidad: item.cantidad,
    }));
  }

  function validarFormularioVenta() {
    let esValido = true;

    if (!elementos.selectMetodoPago || !elementos.selectTipoEntrega) {
      return false;
    }

    if (!elementos.selectMetodoPago.value) {
      elementos.selectMetodoPago.classList.add("is-invalid");
      esValido = false;
    } else {
      elementos.selectMetodoPago.classList.remove("is-invalid");
    }

    if (!elementos.selectTipoEntrega.value) {
      elementos.selectTipoEntrega.classList.add("is-invalid");
      esValido = false;
    } else {
      elementos.selectTipoEntrega.classList.remove("is-invalid");
    }

    if (elementos.selectTipoEntrega.value === "Delivery") {
      const direccion = elementos.inputDireccion?.value.trim();
      if (!direccion) {
        elementos.inputDireccion?.classList.add("is-invalid");
        esValido = false;
      } else {
        elementos.inputDireccion?.classList.remove("is-invalid");
      }
    } else if (elementos.inputDireccion) {
      elementos.inputDireccion.classList.remove("is-invalid");
    }

    if (state.seleccionProductos.size === 0) {
      mostrarAlerta(
        "warning",
        "Agregá al menos un producto para registrar la venta."
      );
      esValido = false;
    }

    if (esValido && state.resumenDescuento.errorPuntos) {
      mostrarAlerta("warning", state.resumenDescuento.errorPuntos);
      esValido = false;
    }

    return esValido;
  }

  function actualizarPuntosDisponibles(nuevoValor) {
    state.puntosDisponibles = Math.max(0, Number(nuevoValor) || 0);
    if (elementos.puntosClienteResumen) {
      elementos.puntosClienteResumen.textContent = formatearEntero(
        state.puntosDisponibles
      );
    }
    if (elementos.puntosDisponiblesPremios) {
      elementos.puntosDisponiblesPremios.textContent = `${formatearEntero(
        state.puntosDisponibles
      )} pts`;
    }
    actualizarResumenDescuento();
    actualizarResumenPremios();
  }

  function mostrarModalResumenVenta(resumen = {}) {
    if (!modalResumenInstance || !elementos.modalResumen) {
      return;
    }

    const items = Array.isArray(resumen.items) ? resumen.items : [];
    if (elementos.modalResumenProductos) {
      elementos.modalResumenProductos.innerHTML = "";
      if (items.length === 0) {
        const fila = document.createElement("tr");
        fila.className = "text-muted";
        fila.innerHTML =
          '<td colspan="3" class="text-center py-3">Sin productos.</td>';
        elementos.modalResumenProductos.appendChild(fila);
      } else {
        const fragmento = document.createDocumentFragment();
        items.forEach((item) => {
          const fila = document.createElement("tr");

          const nombre = document.createElement("td");
          nombre.textContent = item.nombre || "Producto";

          const cantidad = document.createElement("td");
          cantidad.className = "text-end";
          cantidad.textContent = formatearEntero(item.cantidad || 0);

          const subtotal = document.createElement("td");
          subtotal.className = "text-end";
          const precio = Number(item.precio) || 0;
          const unidades = Number(item.cantidad) || 0;
          subtotal.textContent = formatearMoneda(precio * unidades);

          fila.append(nombre, cantidad, subtotal);
          fragmento.appendChild(fila);
        });
        elementos.modalResumenProductos.appendChild(fragmento);
      }
    }

    const totalOriginal = Number(resumen.total_original) || 0;
    const puntosUsados = Number(resumen.puntos_usados) || 0;
    const descuento =
      Number(resumen.descuento) || puntosUsados * valoresConfig.valorPunto;
    const totalFinal =
      Number(resumen.total_final) || Math.max(0, totalOriginal - descuento);
    const puntosGanados = Number(resumen.puntos_ganados) || 0;
    const puntosActuales =
      Number(resumen.puntos_actuales) || state.puntosDisponibles;

    if (elementos.modalResumenTotalOriginal) {
      elementos.modalResumenTotalOriginal.textContent =
        formatearMoneda(totalOriginal);
    }
    if (elementos.modalResumenPuntosUsados) {
      elementos.modalResumenPuntosUsados.textContent = `${formatearEntero(
        puntosUsados
      )} pts`;
    }
    if (elementos.modalResumenDescuento) {
      elementos.modalResumenDescuento.textContent = `-${formatearMoneda(
        descuento
      )}`;
    }
    if (elementos.modalResumenTotalFinal) {
      elementos.modalResumenTotalFinal.textContent =
        formatearMoneda(totalFinal);
    }
    if (elementos.modalResumenPuntosGanados) {
      elementos.modalResumenPuntosGanados.textContent = `${formatearEntero(
        puntosGanados
      )} pts`;
    }
    if (elementos.modalResumenPuntosActuales) {
      elementos.modalResumenPuntosActuales.textContent = `${formatearEntero(
        puntosActuales
      )} pts`;
    }
    if (elementos.modalResumenPedidoId) {
      const pedidoId = resumen.pedido_id;
      elementos.modalResumenPedidoId.textContent = pedidoId
        ? `#${pedidoId}`
        : "-";
    }
    if (elementos.modalResumenMetodoPago) {
      elementos.modalResumenMetodoPago.textContent = resumen.metodo_pago || "-";
    }
    if (elementos.modalResumenTipoEntrega) {
      elementos.modalResumenTipoEntrega.textContent =
        resumen.tipo_entrega || "-";
    }

    modalResumenInstance.show();
  }

  function mostrarModalCanjePremios(resumen = {}, mensaje = "") {
    if (!modalCanjePremiosInstance || !elementos.modalCanjePremios) {
      return;
    }

    if (elementos.modalCanjePremiosMensaje) {
      elementos.modalCanjePremiosMensaje.textContent =
        mensaje || "El canje de premios se registró correctamente.";
    }

    const detalle = Array.isArray(resumen.detalle) ? resumen.detalle : [];
    if (elementos.modalCanjePremiosDetalle) {
      elementos.modalCanjePremiosDetalle.innerHTML = "";

      if (detalle.length === 0) {
        const filaVacia = document.createElement("tr");
        filaVacia.className = "text-muted";
        filaVacia.innerHTML =
          '<td colspan="4" class="text-center py-3">Sin información disponible.</td>';
        elementos.modalCanjePremiosDetalle.appendChild(filaVacia);
      } else {
        const fragmento = document.createDocumentFragment();
        detalle.forEach((item) => {
          const fila = document.createElement("tr");

          const premio = document.createElement("td");
          premio.textContent = item.nombre || "Premio";

          const cantidad = document.createElement("td");
          cantidad.className = "text-end";
          cantidad.textContent = formatearEntero(item.cantidad || 0);

          const costo = document.createElement("td");
          costo.className = "text-end";
          costo.textContent = `${formatearEntero(item.costo_puntos || 0)} pts`;

          const total = document.createElement("td");
          total.className = "text-end";
          total.textContent = `${formatearEntero(item.total_puntos || 0)} pts`;

          fila.append(premio, cantidad, costo, total);
          fragmento.appendChild(fila);
        });
        elementos.modalCanjePremiosDetalle.appendChild(fragmento);
      }
    }

    const puntosUsados = Math.max(
      0,
      Number.isFinite(Number(resumen.puntos_usados))
        ? Number(resumen.puntos_usados)
        : 0
    );
    const puntosRestantes = Math.max(
      0,
      Number.isFinite(Number(resumen.puntos_actuales))
        ? Number(resumen.puntos_actuales)
        : Number(resumen.puntos_restantes) || 0
    );

    if (elementos.modalCanjePremiosPuntosUsados) {
      elementos.modalCanjePremiosPuntosUsados.textContent = `${formatearEntero(
        puntosUsados
      )} pts`;
    }

    if (elementos.modalCanjePremiosPuntosRestantes) {
      elementos.modalCanjePremiosPuntosRestantes.textContent = `${formatearEntero(
        puntosRestantes
      )} pts`;
    }

    if (elementos.modalCanjePremiosFecha) {
      elementos.modalCanjePremiosFecha.textContent = formatearFechaCompleta(
        resumen.fecha
      );
    }

    if (elementos.modalCanjePremiosDescripcion) {
      elementos.modalCanjePremiosDescripcion.textContent =
        resumen.descripcion ||
        "Canje registrado en el sistema de fidelización.";
    }

    modalCanjePremiosInstance.show();
  }

  async function registrarVenta() {
    if (!validarFormularioVenta()) {
      return;
    }

    const payload = {
      accion: "registrar_descuento",
      csrf_token: data.csrf_token || "",
      clienteId: cliente.id,
      items: recolectarProductosSeleccionados(),
      puntos_utilizados: state.resumenDescuento.puntosAplicados || 0,
      metodo_pago: elementos.selectMetodoPago?.value || "",
      tipo_entrega: elementos.selectTipoEntrega?.value || "",
      direccion:
        elementos.selectTipoEntrega?.value === "Delivery"
          ? elementos.inputDireccion?.value || ""
          : null,
      referencias:
        elementos.selectTipoEntrega?.value === "Delivery"
          ? elementos.inputReferencias?.value || ""
          : null,
      nota: elementos.textareaNota?.value || "",
    };

    if (elementos.btnRegistrarVenta) {
      elementos.btnRegistrarVenta.disabled = true;
      elementos.btnRegistrarVenta.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registrando...';
    }

    try {
      const respuesta = await fetch(
        "clientes_canje.php?id=" + encodeURIComponent(cliente.id),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        }
      );

      const datos = await respuesta.json();

      if (!respuesta.ok || !datos.exito) {
        throw new Error(datos.mensaje || "No se pudo registrar la venta.");
      }

      mostrarAlerta("", "");

      const resumen = datos.resumen || {};
      if (Number.isFinite(Number(resumen.puntos_actuales))) {
        actualizarPuntosDisponibles(Number(resumen.puntos_actuales));
      }

      mostrarModalResumenVenta(resumen);

      await vaciarSeleccionProductos({ mostrarMensajes: false });
      state.puntosEditadosManualmente = false;

      if (elementos.selectMetodoPago) {
        elementos.selectMetodoPago.value = "";
      }
      if (elementos.selectTipoEntrega) {
        elementos.selectTipoEntrega.value = "";
      }
      if (elementos.inputDireccion) {
        elementos.inputDireccion.value = "";
      }
      if (elementos.inputReferencias) {
        elementos.inputReferencias.value = "";
      }
      if (elementos.textareaNota) {
        elementos.textareaNota.value = "";
      }

      toggleCamposDelivery();
      actualizarResumenDescuento();
    } catch (error) {
      mostrarAlerta("danger", error.message || "Ocurrió un error inesperado.");
    } finally {
      if (elementos.btnRegistrarVenta) {
        elementos.btnRegistrarVenta.innerHTML =
          '<i class="fa-solid fa-cash-register me-1" aria-hidden="true"></i> Registrar venta con descuento';
        actualizarResumenDescuento();
      }
    }
  }

  function crearTarjetaPremio(premio) {
    const col = document.createElement("div");
    col.className = "col-12 col-md-6 col-xl-4";

    const card = document.createElement("div");
    card.className = "card h-100 shadow-sm premio-card";
    card.dataset.premioId = String(premio.id);

    if (premio.imagen) {
      const img = document.createElement("img");
      img.src = premio.imagen;
      img.alt = `Imagen de ${premio.nombre}`;
      img.className = "card-img-top";
      card.appendChild(img);
    } else {
      const placeholder = document.createElement("div");
      placeholder.className =
        "canje-premio-placeholder d-flex align-items-center justify-content-center";
      placeholder.innerHTML =
        '<i class="fa-solid fa-gift fa-2x" aria-hidden="true"></i>';
      card.appendChild(placeholder);
    }

    const cuerpo = document.createElement("div");
    cuerpo.className = "card-body d-flex flex-column";

    const titulo = document.createElement("h5");
    titulo.className = "card-title";
    titulo.textContent = premio.nombre;

    if (premio.descripcion) {
      const descripcion = document.createElement("p");
      descripcion.className = "card-text text-muted small";
      descripcion.textContent = premio.descripcion;
      cuerpo.appendChild(descripcion);
    }

    const costo = document.createElement("p");
    costo.className = "fw-semibold mb-3";
    costo.textContent = `${formatearEntero(premio.costo_puntos)} pts`;

    const controles = document.createElement("div");
    controles.className =
      "d-flex align-items-center justify-content-between mt-auto";
    controles.innerHTML = `
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-secondary" data-accion="decrementar" aria-label="Quitar">-</button>
        <span class="badge bg-primary" data-role="cantidad">0</span>
        <button type="button" class="btn btn-outline-secondary" data-accion="incrementar" aria-label="Agregar">+</button>
      </div>
    `;

    cuerpo.append(titulo, costo, controles);
    card.appendChild(cuerpo);
    col.appendChild(card);

    return col;
  }

  function renderizarPremios() {
    if (!elementos.listadoPremios) {
      return;
    }

    elementos.listadoPremios.innerHTML = "";

    if (premiosDisponibles.length === 0) {
      if (elementos.estadoPremios) {
        elementos.estadoPremios.textContent =
          "Todavía no hay premios cargados.";
        elementos.estadoPremios.classList.remove("d-none");
      }
      return;
    }

    if (elementos.estadoPremios) {
      elementos.estadoPremios.classList.add("d-none");
    }

    const fragmento = document.createDocumentFragment();
    premiosDisponibles.forEach((premio) => {
      fragmento.appendChild(crearTarjetaPremio(premio));
    });
    elementos.listadoPremios.appendChild(fragmento);
  }

  function actualizarResumenPremios() {
    const seleccion = Array.from(state.seleccionPremios.entries());
    let puntosUsados = 0;

    seleccion.forEach(([premioId, cantidad]) => {
      const premio = premiosDisponibles.find(
        (item) => Number(item.id) === Number(premioId)
      );
      if (premio) {
        puntosUsados += (Number(premio.costo_puntos) || 0) * cantidad;
      }
    });

    if (elementos.puntosDisponiblesPremios) {
      elementos.puntosDisponiblesPremios.textContent = `${formatearEntero(
        state.puntosDisponibles
      )} pts`;
    }

    if (elementos.puntosUsadosPremios) {
      elementos.puntosUsadosPremios.textContent = `${formatearEntero(
        puntosUsados
      )} pts`;
    }

    if (elementos.btnConfirmarPremios) {
      elementos.btnConfirmarPremios.disabled = puntosUsados <= 0;
    }

    return puntosUsados;
  }

  function ajustarPremio(premioId, delta) {
    const premio = premiosDisponibles.find(
      (item) => Number(item.id) === Number(premioId)
    );
    if (!premio) {
      return;
    }

    const actual = state.seleccionPremios.get(premioId) || 0;
    const nuevoValor = Math.max(0, actual + delta);

    const costo = Number(premio.costo_puntos) || 0;
    const puntosActuales = Array.from(state.seleccionPremios.entries()).reduce(
      (total, [id, cantidad]) => {
        const premioRelacionado = premiosDisponibles.find(
          (item) => Number(item.id) === Number(id)
        );
        if (!premioRelacionado) {
          return total;
        }
        const costoUnidad = Number(premioRelacionado.costo_puntos) || 0;
        return total + costoUnidad * cantidad;
      },
      0
    );

    const totalSinEste = puntosActuales - costo * actual;
    const nuevoTotal = totalSinEste + costo * nuevoValor;

    if (nuevoTotal > state.puntosDisponibles) {
      mostrarAlerta("warning", "No podés exceder los puntos disponibles.");
      return;
    }

    if (nuevoValor === 0) {
      state.seleccionPremios.delete(premioId);
    } else {
      state.seleccionPremios.set(premioId, nuevoValor);
    }

    const tarjeta = elementos.listadoPremios?.querySelector(
      `[data-premio-id="${premioId}"]`
    );
    const indicador = tarjeta?.querySelector('[data-role="cantidad"]');
    if (indicador) {
      indicador.textContent = String(nuevoValor);
    }

    actualizarResumenPremios();
  }

  async function confirmarPremios() {
    const puntosUsados = actualizarResumenPremios();
    if (puntosUsados <= 0) {
      mostrarAlerta("warning", "Seleccioná al menos un premio para canjear.");
      return;
    }

    const premios = Array.from(state.seleccionPremios.entries()).map(
      ([id, cantidad]) => ({ id: Number(id), cantidad })
    );

    if (elementos.btnConfirmarPremios) {
      elementos.btnConfirmarPremios.disabled = true;
      elementos.btnConfirmarPremios.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
    }

    try {
      const respuesta = await fetch(
        "clientes_canje.php?id=" + encodeURIComponent(cliente.id),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            accion: "canjear_premios",
            csrf_token: data.csrf_token || "",
            clienteId: cliente.id,
            premios,
          }),
        }
      );

      const datos = await respuesta.json();

      if (!respuesta.ok || !datos.exito) {
        throw new Error(
          datos.mensaje || "No se pudo registrar el canje de premios."
        );
      }

      const resumen = datos.resumen || {};
      if (Number.isFinite(Number(resumen.puntos_actuales))) {
        actualizarPuntosDisponibles(Number(resumen.puntos_actuales));
      }

      mostrarAlerta("info", "");
      mostrarModalCanjePremios(
        resumen,
        datos.mensaje || "El canje de premios se registró correctamente."
      );

      state.seleccionPremios.clear();
      elementos.listadoPremios
        ?.querySelectorAll('[data-role="cantidad"]')
        .forEach((elemento) => {
          elemento.textContent = "0";
        });
      actualizarResumenPremios();
    } catch (error) {
      mostrarAlerta("danger", error.message || "Ocurrió un error inesperado.");
    } finally {
      if (elementos.btnConfirmarPremios) {
        elementos.btnConfirmarPremios.disabled = false;
        elementos.btnConfirmarPremios.innerHTML =
          '<i class="fa-solid fa-check me-1" aria-hidden="true"></i> Confirmar canje de premios';
      }
    }
  }

  async function manejarClickProductos(evento) {
    const boton = evento.target.closest("button");
    if (!boton) {
      return;
    }

    if (boton.dataset.menuId) {
      evento.preventDefault();
      const menuId = boton.dataset.menuId;
      boton.disabled = true;
      try {
        await agregarProducto(menuId);
      } finally {
        if (document.body.contains(boton)) {
          boton.disabled = false;
        }
        actualizarBotonesDisponibilidad();
      }
      return;
    }

    const fila = evento.target.closest("tr[data-producto-id]");
    if (!fila) {
      return;
    }

    const menuId = fila.dataset.productoId;
    const accion = boton.dataset.accion;

    if (!accion) {
      return;
    }

    boton.disabled = true;

    try {
      if (accion === "decrementar") {
        await ajustarCantidad(menuId, -1);
      } else if (accion === "incrementar") {
        await ajustarCantidad(menuId, 1);
      } else if (accion === "eliminar") {
        await eliminarProducto(menuId);
      }
    } finally {
      if (document.body.contains(boton)) {
        boton.disabled = false;
      }
      actualizarBotonesDisponibilidad();
    }
  }

  function manejarClickPremios(evento) {
    const boton = evento.target.closest("button[data-accion]");
    if (!boton) {
      return;
    }

    const tarjeta = boton.closest("[data-premio-id]");
    if (!tarjeta) {
      return;
    }

    const premioId = tarjeta.dataset.premioId;
    const accion = boton.dataset.accion;

    if (accion === "incrementar") {
      ajustarPremio(premioId, 1);
    } else if (accion === "decrementar") {
      ajustarPremio(premioId, -1);
    }
  }

  function cambiarModo(evento) {
    const valor = evento.target.value;
    if (valor === "premios") {
      elementos.panelPremios?.classList.remove("d-none");
      elementos.panelDescuento?.classList.add("d-none");
    } else {
      elementos.panelDescuento?.classList.remove("d-none");
      elementos.panelPremios?.classList.add("d-none");
    }
  }

  function inicializarEventos() {
    elementos.modoRadios?.forEach((radio) => {
      radio.addEventListener("change", cambiarModo);
    });

    elementos.filtroNombre?.addEventListener("input", renderizarProductos);
    elementos.filtroCategoria?.addEventListener("change", renderizarProductos);

    elementos.listadoProductos?.addEventListener(
      "click",
      manejarClickProductos
    );
    elementos.tablaProductos?.addEventListener("click", manejarClickProductos);

    elementos.btnVaciar?.addEventListener("click", async (evento) => {
      evento.preventDefault();
      if (!elementos.btnVaciar) {
        return;
      }

      elementos.btnVaciar.disabled = true;
      try {
        await vaciarSeleccionProductos();
      } finally {
        elementos.btnVaciar.disabled = false;
      }
    });

    elementos.inputPuntosPersonalizados?.addEventListener("input", () => {
      state.puntosEditadosManualmente = true;
      actualizarResumenDescuento();
    });
    elementos.btnMaximoPuntos?.addEventListener("click", () => {
      state.puntosEditadosManualmente = true;
      seleccionarMaximoPuntos();
    });

    elementos.selectTipoEntrega?.addEventListener(
      "change",
      toggleCamposDelivery
    );

    elementos.btnRegistrarVenta?.addEventListener("click", registrarVenta);

    elementos.listadoPremios?.addEventListener("click", manejarClickPremios);
    elementos.btnConfirmarPremios?.addEventListener("click", confirmarPremios);
  }

  async function iniciar() {
    renderizarProductos();
    actualizarTablaSeleccion();
    renderizarPremios();
    actualizarResumenPremios();
    toggleCamposDelivery();
    if (
      elementos.modalResumen &&
      typeof bootstrap !== "undefined" &&
      typeof bootstrap.Modal !== "undefined"
    ) {
      modalResumenInstance = bootstrap.Modal.getOrCreateInstance(
        elementos.modalResumen
      );
    }

    if (
      elementos.modalCanjePremios &&
      typeof bootstrap !== "undefined" &&
      typeof bootstrap.Modal !== "undefined"
    ) {
      modalCanjePremiosInstance = bootstrap.Modal.getOrCreateInstance(
        elementos.modalCanjePremios
      );
    }

    actualizarResumenDescuento();
    inicializarEventos();
    await inicializarReservas();
    await actualizarDisponibilidadDesdeServidor();
  }

  document.addEventListener("DOMContentLoaded", () => {
    iniciar().catch((error) => {
      console.error("No se pudo inicializar la sección de canje:", error);
    });
  });
})();
