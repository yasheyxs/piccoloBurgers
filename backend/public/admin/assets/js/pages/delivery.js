// Panel de delivery: pedidos en camino, se actualizan solos y se marcan como entregados
  const pedidosContainer = document.getElementById('lista-pedidos');
  const alertaSinPedidos = document.getElementById('alerta-sin-pedidos');
  // Se agrega a cada dirección para el enlace al mapa (viene de los datos del local)
  const LOCALIDAD_PREDETERMINADA = pedidosContainer?.dataset.localidad || '';
  const formateadorPesos = new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  let sincronizacionEnCurso = false;

  function actualizarVisibilidadMensaje() {
    if (!alertaSinPedidos || !pedidosContainer) {
      return;
    }

    if (pedidosContainer.children.length === 0) {
      alertaSinPedidos.classList.remove('d-none');
    } else {
      alertaSinPedidos.classList.add('d-none');
    }
  }

  function normalizarEstadoPago(valor) {
    if (valor === null || valor === undefined) {
      return 'No';
    }

    let texto = valor.toString().trim().toLowerCase();

    try {
      texto = texto.normalize('NFD');
    } catch (error) {
      // Algunos navegadores pueden no soportar normalize.
    }

    texto = texto.replace(/[\u0300-\u036f]/g, '');
    texto = texto.replace(/[\s'"`’]/g, '');

    const valoresPositivos = new Set(['si', 'sí', 'yes', 'pagado', 'pago', 'abonado', '1', 'true']);
    const valoresNegativos = new Set(['no', '0', '', 'false', 'pendiente']);

    if (valoresPositivos.has(texto)) {
      return 'Si';
    }

    if (valoresNegativos.has(texto)) {
      return 'No';
    }

    return 'No';
  }

  function obtenerTextoEstadoPago(estado) {
    return estado === 'Si' ? 'Sí' : 'No';
  }

  function obtenerClaseEstadoPago(estado) {
    return estado === 'Si' ? 'text-success fw-semibold' : 'text-warning fw-semibold';
  }

  function escapeHtml(texto) {
    if (texto === null || texto === undefined) {
      return '';
    }

    return texto.toString().replace(/[&<>"']/g, (caracter) => {
      switch (caracter) {
        case '&':
          return '&amp;';
        case '<':
          return '&lt;';
        case '>':
          return '&gt;';
        case '"':
          return '&quot;';
        default:
          return '&#039;';
      }
    });
  }

  function formatearTotal(total) {
    const numero = Number(total);
    const totalValido = Number.isFinite(numero) ? numero : 0;
    return formateadorPesos.format(totalValido);
  }

  function normalizarTelefonoParaMostrar(telefono) {
    if (telefono === null || telefono === undefined) {
      return '';
    }

    const texto = String(telefono).trim();
    if (texto === '') {
      return '';
    }

    const sinPrefijo = texto.replace(/^\++/, '');
    return `+${sinPrefijo}`;
  }

  function obtenerTelefonoEnlace(telefono) {
    if (telefono === null || telefono === undefined) {
      return '';
    }

    const numeros = String(telefono).trim().replace(/[^0-9+]/g, '');
    if (numeros === '') {
      return '';
    }

    const sinPrefijo = numeros.replace(/^\++/, '');
    return `+${sinPrefijo}`;
  }

  function crearTarjetaPedido(pedido) {
    const tarjeta = document.createElement('div');
    tarjeta.className = 'glass-card';

    const pedidoId = pedido && pedido.id !== undefined ? String(pedido.id) : '';
    if (pedidoId === '') {
      return null;
    }

    tarjeta.dataset.pedidoId = pedidoId;

    const estadoPago = (pedido.pagado ? 'Si' : 'No');
    tarjeta.dataset.estaPago = estadoPago;

    const telefono = pedido && pedido.telefono != null ? String(pedido.telefono).trim() : '';
    const telefonoNormalizado = normalizarTelefonoParaMostrar(telefono);
    const telefonoEnlace = obtenerTelefonoEnlace(telefono);

    const direccionBase = pedido && pedido.direccion != null ? String(pedido.direccion).trim() : '';
    const mapaQuery = direccionBase !== '' ? `${direccionBase}, ${LOCALIDAD_PREDETERMINADA}` : '';
    const mapUrl = mapaQuery !== '' ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(mapaQuery)}` : '';

    const metodoPago = pedido && pedido.metodo_pago != null ? String(pedido.metodo_pago).trim() : '';
    const referencias = pedido && pedido.referencias != null ? String(pedido.referencias).trim() : '';

    const productos = Array.isArray(pedido.productos) ? pedido.productos : [];
    const productosHtml = productos.length > 0 ?
      productos
      .map((producto) => {
        const cantidad = producto && producto.cantidad != null ? String(producto.cantidad) : '';
        const nombre = producto && producto.nombre != null ? String(producto.nombre) : '';
        return `<li>${escapeHtml(cantidad)} x ${escapeHtml(nombre)}</li>`;
      })
      .join('') :
      '<li class="text-muted">Sin productos</li>';

    const telefonoHtml = (telefonoNormalizado !== '' && telefonoEnlace !== '')
      ? `<a class="contact-link" href="tel:${escapeHtml(telefonoEnlace)}">${escapeHtml(telefonoNormalizado)}</a>`:
      '<span class="text-muted">Sin teléfono</span>';

    const direccionHtml = mapUrl !== '' ?
      `<a class="contact-link" href="${escapeHtml(mapUrl)}" target="_blank" rel="noopener noreferrer"> ${escapeHtml(direccionBase)}</a>` :
      '<span class="text-muted">Sin dirección</span>';

    const metodoPagoHtml = metodoPago !== '' ?
      escapeHtml(metodoPago) :
      '<span class="text-muted">Sin método de pago</span>';

    const referenciasHtml = referencias !== '' ? escapeHtml(referencias) : 'Sin referencias';

    const totalFormateado = formatearTotal(pedido.total);

    tarjeta.innerHTML = `
      <h5>#${escapeHtml(pedidoId)} - ${escapeHtml(pedido.nombre ?? '')}</h5>
      <p class="mb-2">
        <i class="fas fa-phone me-2"></i>
        ${telefonoHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-map-marker-alt me-2"></i>
        ${direccionHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-credit-card me-2"></i>
        ${metodoPagoHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-money-bill-wave me-2"></i>
        Total: $${escapeHtml(totalFormateado)}
      </p>
      <p class="mb-2">
        <i class="fas fa-receipt me-2"></i>
        ¿Está pago?: <span class="${obtenerClaseEstadoPago(estadoPago)}" data-estado-pago>${obtenerTextoEstadoPago(estadoPago)}</span>
      </p>
      <p class="fw-semibold mb-2"><i class="fa-solid fa-bag-shopping me-2" aria-hidden="true"></i>Productos:</p>
      <ul class="mb-3">
        ${productosHtml}
      </ul>
        <p class="mb-0"><strong><i class="fa-solid fa-thumbtack me-2" aria-hidden="true"></i>Referencias:</strong> ${referenciasHtml}</p>
        <div class="mt-4">
          <button class="btn btn-gold w-100" data-id="${escapeHtml(pedidoId)}" data-action="entregado">Entregado</button>
        </div>
    `;

    return tarjeta;
  }

  function actualizarTarjetaConDatos(pedido) {
    if (!pedidosContainer || !pedido || pedido.id === undefined || pedido.id === null) {
      return;
    }

    const pedidoId = String(pedido.id);
    const estadoPago = (pedido.pagado ? 'Si' : 'No');
    const selector = `.glass-card[data-pedido-id="${pedidoId}"]`;
    const tarjetaExistente = pedidosContainer.querySelector(selector);

    if (tarjetaExistente) {
      tarjetaExistente.dataset.estaPago = estadoPago;
      const spanEstadoPago = tarjetaExistente.querySelector('[data-estado-pago]');
      if (spanEstadoPago) {
        spanEstadoPago.textContent = obtenerTextoEstadoPago(estadoPago);
        spanEstadoPago.className = obtenerClaseEstadoPago(estadoPago);
      }
      return;
    }

    const nuevaTarjeta = crearTarjetaPedido(pedido);
    if (nuevaTarjeta) {
      pedidosContainer.prepend(nuevaTarjeta);
    }
  }

  async function sincronizarPedidos() {
    if (!pedidosContainer || sincronizacionEnCurso) {
      return;
    }

    sincronizacionEnCurso = true;

    try {
      const response = await fetch('obtener_pedidos_delivery.php', {
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error('Error al obtener los pedidos.');
      }

      const data = await response.json();
      if (!data || data.success !== true || !Array.isArray(data.pedidos)) {
        const mensaje = data && data.message ? data.message : 'La respuesta del servidor no es válida.';
        throw new Error(mensaje);
      }

      const idsVigentes = new Set();

      data.pedidos.forEach((pedido) => {
        if (!pedido || pedido.id === undefined || pedido.id === null) {
          return;
        }

        const id = String(pedido.id);
        idsVigentes.add(id);
        actualizarTarjetaConDatos(pedido);
      });

      pedidosContainer.querySelectorAll('.glass-card').forEach((tarjeta) => {
        const id = tarjeta.getAttribute('data-pedido-id');
        if (id && !idsVigentes.has(id)) {
          tarjeta.remove();
        }
      });

      actualizarVisibilidadMensaje();
    } catch (error) {
      console.error('No se pudo sincronizar la información de los pedidos:', error);
    } finally {
      sincronizacionEnCurso = false;
    }
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  function crearFormularioProtegido() {
    if (!csrfToken) {
      throw new Error('No se encontró el token CSRF en la página.');
    }
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    return formData;
  }

  async function marcarEntregado(pedidoId, boton) {

    const textoOriginal = boton.textContent;
    boton.disabled = true;
    boton.textContent = 'Actualizando...';

    let pedidoActualizado = false;

    try {
      const formData = crearFormularioProtegido();
      formData.append('pedido_id', pedidoId);
      formData.append('nuevo_estado', 'Listo');

      const response = await fetch('actualizar_estado.php', {
        method: 'POST',
        body: formData
      });

      let resultado;
      try {
        resultado = await response.json();
      } catch (errorJson) {
        console.error('Error al analizar la respuesta JSON:', errorJson);
        throw new Error('La respuesta del servidor no es válida.');
      }

      if (resultado && resultado.success) {
        pedidoActualizado = true;
        const tarjeta = boton.closest('.glass-card');
        if (tarjeta) {
          tarjeta.remove();
          actualizarVisibilidadMensaje();
        }
      } else {
        const mensaje = resultado && resultado.message ? resultado.message : 'La respuesta del servidor no es válida.';
        throw new Error(mensaje);
      }
    } catch (error) {
      const mensajeError = error instanceof Error && error.message && error.message !== 'Failed to fetch' ?
        error.message :
        'Error al conectar con el servidor.';
      alert(mensajeError);
      console.error(error);
    } finally {
      if (!pedidoActualizado) {
        boton.disabled = false;
        boton.textContent = textoOriginal;
      }
    }
  }

  document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-action="entregado"]');
    if (!boton || !pedidosContainer || !pedidosContainer.contains(boton)) {
      return;
    }

    const pedidoId = boton.getAttribute('data-id');
    if (pedidoId) {
      marcarEntregado(pedidoId, boton);
    }
  });

  // Primero los pedidos que vinieron con la página; después se actualizan cada 4 segundos
  JSON.parse(document.getElementById('datosDelivery').textContent).forEach(actualizarTarjetaConDatos);
  actualizarVisibilidadMensaje();
  setInterval(sincronizarPedidos, 4000);
