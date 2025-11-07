(function () {
  "use strict";

  const data = window.ClienteCanjeData || {};
  const cliente = data.cliente || {};
  const config = data.config || {};
  const menuDisponible = Array.isArray(data.menu) ? data.menu : [];
  const premiosDisponibles = Array.isArray(data.premios) ? data.premios : [];

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
    resumenDescuento: document.getElementById("resumenDescuento"),
    resumenTotalFinal: document.getElementById("resumenTotalFinal"),
    radioPuntosTodos: document.getElementById("radioPuntosTodos"),
    radioPuntosPersonalizados: document.getElementById(
      "radioPuntosPersonalizados"
    ),
    inputPuntosPersonalizados: document.getElementById(
      "inputPuntosPersonalizados"
    ),
    btnMaximoPuntos: document.getElementById("btnMaximoPuntos"),
    textoAyudaPuntos: document.getElementById("ayudaPuntos"),
    errorPuntosPersonalizados: document.getElementById(
      "errorPuntosPersonalizados"
    ),

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
      puntosAplicados: 0,
      descuento: 0,
      totalFinal: 0,
      maximoPuntosPermitidos: 0,
    },
  };

  const valoresConfig = {
    minimo: Number(config.minimo_puntos) || 0,
    valorPunto: Number(config.valor_punto) || 0,
    maximoPorcentaje: Number(config.maximo_porcentaje) || 0,
  };

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

  function crearTarjetaProducto(producto) {
    const col = document.createElement("div");
    col.className = "col-12 col-sm-6 col-xl-4";

    const card = document.createElement("div");
    card.className = "card h-100 shadow-sm";

    if (producto.imagen) {
      const imagen = document.createElement("img");
      imagen.src = producto.imagen;
      imagen.alt = `Imagen de ${producto.nombre}`;
      imagen.className = "card-img-top";
      card.appendChild(imagen);
    }

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
  }

  function agregarProducto(menuId) {
    const id = Number(menuId);
    if (!Number.isInteger(id) || id <= 0) {
      return;
    }

    const producto = menuDisponible.find((item) => Number(item.id) === id);
    if (!producto) {
      mostrarAlerta(
        "warning",
        "El producto seleccionado ya no está disponible."
      );
      return;
    }

    const actual = state.seleccionProductos.get(id) || {
      id,
      nombre: producto.nombre,
      precio: Number(producto.precio) || 0,
      cantidad: 0,
    };

    actual.cantidad += 1;
    state.seleccionProductos.set(id, actual);

    actualizarTablaSeleccion();
  }

  function eliminarProducto(menuId) {
    const id = Number(menuId);
    state.seleccionProductos.delete(id);
    actualizarTablaSeleccion();
  }

  function ajustarCantidad(menuId, delta) {
    const id = Number(menuId);
    const registro = state.seleccionProductos.get(id);
    if (!registro) {
      return;
    }

    const nuevaCantidad = registro.cantidad + delta;
    if (nuevaCantidad <= 0) {
      state.seleccionProductos.delete(id);
    } else {
      registro.cantidad = nuevaCantidad;
      state.seleccionProductos.set(id, registro);
    }

    actualizarTablaSeleccion();
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
      return;
    }

    const fragmento = document.createDocumentFragment();
    productos.forEach((producto) => {
      fragmento.appendChild(crearFilaProducto(producto));
    });

    elementos.tablaProductos.appendChild(fragmento);
    actualizarResumenDescuento();
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

    let puntosSolicitados = 0;
    const usaPersonalizado = elementos.radioPuntosPersonalizados?.checked;
    let error = "";

    if (usaPersonalizado) {
      puntosSolicitados = Number(
        elementos.inputPuntosPersonalizados?.value || 0
      );
      if (!Number.isFinite(puntosSolicitados) || puntosSolicitados < 0) {
        puntosSolicitados = 0;
      }

      if (puntosSolicitados > state.puntosDisponibles) {
        error = "No podés usar más puntos de los disponibles.";
      } else if (puntosSolicitados > maximoPermitido) {
        error = "Superás el máximo permitido para esta venta.";
      } else if (puntosSolicitados > 0 && puntosSolicitados < minimoPuntos) {
        error = `Debés ingresar al menos ${formatearEntero(
          minimoPuntos
        )} puntos.`;
      }
    }

    let puntosAplicados = 0;
    if (
      totalParcial > 0 &&
      maximoPermitido > 0 &&
      state.puntosDisponibles >= minimoPuntos
    ) {
      if (usaPersonalizado && !error) {
        puntosAplicados = puntosSolicitados;
      } else if (!usaPersonalizado) {
        puntosAplicados = maximoPermitido;
        if (puntosAplicados > 0 && puntosAplicados < minimoPuntos) {
          puntosAplicados = 0;
        }
      }
    }

    const descuento = Math.min(
      maximoDescuentoPesos,
      puntosAplicados * valorPunto,
      totalParcial
    );
    const totalFinal = Math.max(0, totalParcial - descuento);

    return {
      totalParcial,
      puntosAplicados,
      descuento,
      totalFinal,
      maximoPuntosPermitidos: maximoPermitido,
      errorPuntos: error,
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

    if (elementos.btnRegistrarVenta) {
      const hayProductos = state.seleccionProductos.size > 0;
      elementos.btnRegistrarVenta.disabled = !hayProductos;
    }
  }

  function vaciarSeleccionProductos() {
    state.seleccionProductos.clear();
    actualizarTablaSeleccion();
  }

  function sincronizarCamposPuntos() {
    if (!elementos.inputPuntosPersonalizados) {
      return;
    }

    const habilitado = !!elementos.radioPuntosPersonalizados?.checked;
    elementos.inputPuntosPersonalizados.disabled = !habilitado;
    if (!habilitado) {
      elementos.inputPuntosPersonalizados.value = "0";
    }

    actualizarResumenDescuento();
  }

  function seleccionarMaximoPuntos() {
    const maximo = state.resumenDescuento.maximoPuntosPermitidos || 0;
    if (elementos.inputPuntosPersonalizados) {
      elementos.inputPuntosPersonalizados.value = String(maximo);
    }
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

    if (state.resumenDescuento.errorPuntos) {
      esValido = false;
    }

    if (state.seleccionProductos.size === 0) {
      mostrarAlerta(
        "warning",
        "Agregá al menos un producto para registrar la venta."
      );
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

  async function registrarVenta() {
    if (!validarFormularioVenta()) {
      return;
    }

    const payload = {
      accion: "registrar_descuento",
      csrf_token: data.csrf_token || "",
      clienteId: cliente.id,
      items: recolectarProductosSeleccionados(),
      tipo_uso_puntos: elementos.radioPuntosPersonalizados?.checked
        ? "personalizado"
        : "todos",
      puntos_personalizados: elementos.radioPuntosPersonalizados?.checked
        ? Number(elementos.inputPuntosPersonalizados?.value || 0)
        : 0,
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

    if (state.resumenDescuento.totalParcial <= 0) {
      mostrarAlerta("warning", "El total de la venta debe ser mayor a cero.");
      return;
    }

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

      mostrarAlerta(
        "success",
        datos.mensaje || "La venta se registró correctamente."
      );

      const resumen = datos.resumen || {};
      if (Number.isFinite(Number(resumen.puntos_actuales))) {
        actualizarPuntosDisponibles(Number(resumen.puntos_actuales));
      }

      vaciarSeleccionProductos();
      elementos.selectMetodoPago.value = "";
      elementos.selectTipoEntrega.value = "";
      elementos.inputDireccion.value = "";
      elementos.inputReferencias.value = "";
      elementos.textareaNota.value = "";
      elementos.inputPuntosPersonalizados.value = "0";
      elementos.radioPuntosTodos.checked = true;
      toggleCamposDelivery();
      sincronizarCamposPuntos();
    } catch (error) {
      mostrarAlerta("danger", error.message || "Ocurrió un error inesperado.");
    } finally {
      if (elementos.btnRegistrarVenta) {
        elementos.btnRegistrarVenta.disabled =
          state.seleccionProductos.size === 0;
        elementos.btnRegistrarVenta.innerHTML =
          '<i class="fa-solid fa-cash-register me-1" aria-hidden="true"></i> Registrar venta con descuento';
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
        "d-flex align-items-center justify-content-center bg-light border-bottom py-4 text-muted";
      placeholder.innerHTML = '<i class="fa-solid fa-gift fa-2x"></i>';
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

      mostrarAlerta(
        "success",
        datos.mensaje || "El canje de premios se registró correctamente."
      );

      const resumen = datos.resumen || {};
      if (Number.isFinite(Number(resumen.puntos_actuales))) {
        actualizarPuntosDisponibles(Number(resumen.puntos_actuales));
      }

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

  function manejarClickProductos(evento) {
    const boton = evento.target.closest("button");
    if (!boton) {
      return;
    }

    if (boton.dataset.menuId) {
      agregarProducto(boton.dataset.menuId);
      return;
    }

    const fila = evento.target.closest("tr[data-producto-id]");
    if (!fila) {
      return;
    }

    const menuId = fila.dataset.productoId;
    const accion = boton.dataset.accion;

    switch (accion) {
      case "decrementar":
        ajustarCantidad(menuId, -1);
        break;
      case "incrementar":
        ajustarCantidad(menuId, 1);
        break;
      case "eliminar":
        eliminarProducto(menuId);
        break;
      default:
        break;
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

    elementos.btnVaciar?.addEventListener("click", vaciarSeleccionProductos);

    elementos.radioPuntosTodos?.addEventListener(
      "change",
      sincronizarCamposPuntos
    );
    elementos.radioPuntosPersonalizados?.addEventListener(
      "change",
      sincronizarCamposPuntos
    );
    elementos.inputPuntosPersonalizados?.addEventListener(
      "input",
      actualizarResumenDescuento
    );
    elementos.btnMaximoPuntos?.addEventListener(
      "click",
      seleccionarMaximoPuntos
    );

    elementos.selectTipoEntrega?.addEventListener(
      "change",
      toggleCamposDelivery
    );

    elementos.btnRegistrarVenta?.addEventListener("click", registrarVenta);

    elementos.listadoPremios?.addEventListener("click", manejarClickPremios);
    elementos.btnConfirmarPremios?.addEventListener("click", confirmarPremios);
  }

  function iniciar() {
    renderizarProductos();
    actualizarTablaSeleccion();
    renderizarPremios();
    actualizarResumenPremios();
    toggleCamposDelivery();
    sincronizarCamposPuntos();
    inicializarEventos();
  }

  document.addEventListener("DOMContentLoaded", iniciar);
})();
