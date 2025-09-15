(() => {
  const perfilData = window.perfilClienteData || {};
  const errores = Array.isArray(perfilData.errores) ? perfilData.errores : [];
  const mensajeError = perfilData.mensajeError || '';
  const mensajeExito = perfilData.mensajeExito || '';

  function mostrarToast(texto, tipo = 'success') {
    const toastEl = document.getElementById('toastMsg');
    if (!toastEl || typeof bootstrap === 'undefined') {
      return;
    }

    const toastBody = toastEl.querySelector('.toast-body');
    if (toastBody) {
      toastBody.textContent = texto;
    }

    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(tipo === 'error' ? 'bg-danger' : 'bg-success', 'text-white');

    const bsToast = new bootstrap.Toast(toastEl, { delay: 2500 });
    bsToast.show();
  }

  function initMensajes() {
    if (mensajeExito) {
      mostrarToast(mensajeExito, 'success');
    }

    if (mensajeError) {
      mostrarToast(mensajeError, 'error');
    }

    if (errores.length > 0) {
      const prioridad = errores.find((e) => e.toLowerCase().includes('contraseña actual es incorrecta'));
      if (prioridad) {
        mostrarToast(prioridad, 'error');

        const campo = document.getElementById('password_actual');
        if (campo) {
          campo.classList.add('shake');
          setTimeout(() => campo.classList.remove('shake'), 500);
        }
      } else {
        mostrarToast(errores[0], 'error');
      }
    }
  }

  function crearHtmlDetalle(pedido) {
    let estadoHtml = pedido.estado;
    switch (pedido.estado) {
      case 'Cancelado':
        estadoHtml = '<span class="text-danger">Cancelado ❌ - Esperamos poder servirte mejor en el futuro.</span>';
        break;
      case 'Listo':
        estadoHtml = '<span class="text-success">Listo ✅</span>';
        break;
      case 'En preparación':
        estadoHtml = '<span class="text-warning">En preparación ⏳</span>';
        break;
      case 'En camino':
        estadoHtml = '<span class="text-info">En camino 🚚</span>';
        break;
      default:
        break;
    }

    const productosHtml = (pedido.detalles || [])
      .map((detalle) => `
        <div class="producto-tarjeta mb-2">
          <strong>${detalle.nombre}</strong><br>
          <small>Precio: $${Number(detalle.precio).toFixed(2)} | Cantidad: ${detalle.cantidad} | Subtotal: $${(detalle.precio * detalle.cantidad).toFixed(2)}</small>
        </div>
      `)
      .join('');

    return `
      <div class="detalle-card bg-dark text-light p-3 border border-secondary rounded">
        <p><strong>Entrega:</strong> ${pedido.tipo_entrega}</p>
        <p><strong>Método de pago:</strong> ${pedido.metodo_pago}</p>
        <p><strong>Estado:</strong> ${estadoHtml}</p>
        <p><strong>Nota:</strong> ${pedido.nota ? pedido.nota.replace(/\n/g, '<br>') : 'Sin nota'}</p>
        <strong>Productos:</strong>
        <div class="mt-2">${productosHtml}</div>
      </div>
    `;
  }

  const historialContenedor = document.getElementById('historial-pedidos');
  const modalEl = document.getElementById('modalDetallePedido');
  let pedidos = [];

  async function actualizarHistorial() {
    if (!historialContenedor) {
      return;
    }

    try {
      const response = await fetch('../admin/pedidos/obtener_pedidos_cliente.php');
      pedidos = await response.json();

      let htmlPedidos = '';

      if (!Array.isArray(pedidos) || pedidos.length === 0) {
        htmlPedidos = `
          <div class="col-12">
            <div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>
          </div>`;
      } else {
        htmlPedidos = pedidos
          .map((pedido, index) => {
            const pedidoId = pedido.id ?? pedidos.length - index;
            return `
              <div class="col-md-6 col-lg-3 mb-3">
                <div class="pedido-card glass-card card h-100 p-3 shadow-sm text-center" data-index="${index}" style="border-radius: 15px;">
                  <i class="fas fa-receipt fa-2x mb-3 text-warning"></i>
                  <h5 class="card-title">Pedido #${pedidoId}</h5>
                  <p><strong>Fecha:</strong><br>${new Date(pedido.fecha).toLocaleDateString()}</p>
                  <p><strong>Total:</strong> $${Number(pedido.total).toFixed(2)}</p>
                  <button class="btn btn-outline-warning btn-sm mt-2 ver-detalle-btn" data-index="${index}">Ver detalle</button>
                </div>
              </div>`;
          })
          .join('');
      }

      historialContenedor.innerHTML = htmlPedidos;

      if (modalEl && modalEl.classList.contains('show')) {
        const idx = Number(modalEl.getAttribute('data-index'));
        const pedido = pedidos[idx];
        const modalBody = modalEl.querySelector('.modal-body');
        if (pedido && modalBody) {
          modalBody.innerHTML = crearHtmlDetalle(pedido);
        }
      }
    } catch (error) {
      console.error('Error al actualizar historial:', error);
    }
  }

  function manejarClickDetalle(event) {
    if (!modalEl || typeof bootstrap === 'undefined') {
      return;
    }

    const btn = event.target.closest('.ver-detalle-btn');
    if (!btn) {
      return;
    }

    const idx = Number(btn.getAttribute('data-index'));
    const pedido = pedidos[idx];
    if (!pedido) {
      return;
    }

    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('#modalDetallePedidoLabel');
    if (modalTitle) {
      const pedidoId = pedido.id ?? idx + 1;
      modalTitle.textContent = `Detalle del Pedido #${pedidoId}`;
    }

    if (modalBody) {
      modalBody.innerHTML = crearHtmlDetalle(pedido);
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalEl.setAttribute('data-index', String(idx));
    modal.show();
  }

  function initHistorial() {
    if (!historialContenedor) {
      return;
    }

    historialContenedor.addEventListener('click', manejarClickDetalle);
    actualizarHistorial();
    setInterval(actualizarHistorial, 10000);
  }

  function initTelefono() {
    const selectPais = document.getElementById('codigo_pais_editar');
    const inputTelefono = document.getElementById('telefono_editar');
    if (!selectPais || !inputTelefono) {
      return;
    }

    const longitudesPorPais = {
      54: 10,
      598: 9,
      55: 11,
      56: 9,
      595: 9,
      591: 8,
      51: 9,
      1: 10,
      34: 9
    };

    const actualizarMaxLength = () => {
      const codigo = selectPais.value;
      const max = longitudesPorPais[codigo] || 15;
      inputTelefono.setAttribute('maxlength', String(max));
    };

    selectPais.addEventListener('change', actualizarMaxLength);
    actualizarMaxLength();
  }

  function actualizarContadorCarrito() {
    const contador = document.getElementById('contador-carrito');
    if (!contador) {
      return;
    }

    const carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
    contador.textContent = carrito.length;
  }

  document.addEventListener('DOMContentLoaded', () => {
    initMensajes();
    initHistorial();
    initTelefono();
    actualizarContadorCarrito();
  });
})();
