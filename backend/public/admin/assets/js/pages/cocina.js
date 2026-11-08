// Panel de cocina: cambiar estado y pago de los pedidos en curso (se recarga cada 10 segundos)
  document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#tabla-pedidos', {
      order: [],
      ordering: false
    });
  });

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  function agregarProtecciones(formData) {
    if (!csrfToken) {
      throw new Error('No se encontró el token CSRF en la página.');
    }
    formData.append('csrf_token', csrfToken);
    return formData;
  }

  async function actualizarEstado(pedidoId, nuevoEstado, boton) {
    try {
      const formData = agregarProtecciones(new FormData());
      formData.append('pedido_id', pedidoId);
      formData.append('nuevo_estado', nuevoEstado);

      const response = await fetch('actualizar_estado.php', {
        method: 'POST',
        body: formData
      });
      const resultado = await response.json();

      if (resultado.success) {
        alert(`Pedido #${pedidoId} actualizado a "${nuevoEstado}" correctamente.`);
        const fila = boton.closest('tr');

        if (nuevoEstado === 'En camino') {
          fila.classList.add('en-camino');

          const btnEnCamino = fila.querySelector('button[data-estado="En camino"]');
          if (btnEnCamino) btnEnCamino.remove();

        } else {
          fila.remove();
        }

        // Insertar icono de delivery en la columna de acciones
        const celdaAcciones = fila.cells[8];
        if (celdaAcciones && !celdaAcciones.querySelector('.camion-icono')) {
          const icono = document.createElement('span');
          icono.className = 'camion-icono';
          icono.innerHTML = '<i class="fa-solid fa-truck" aria-hidden="true"></i>';
          const contenedorBotones = celdaAcciones.querySelector('.d-flex');
          if (contenedorBotones) {
            contenedorBotones.insertBefore(icono, contenedorBotones.firstChild);
          }
        }

        const tbody = document.querySelector('#tabla-pedidos tbody');
        if (tbody.children.length === 0) {
          const contenedor = document.querySelector('.card-body');
          contenedor.innerHTML = '<div class="alert alert-info">No hay pedidos en preparación por el momento.</div>';
        }
      } else {
        alert('Error: ' + resultado.message);
      }
    } catch (error) {
      alert('Error al conectar con el servidor.');
      console.error(error);
    }
  }

  async function actualizarEstadoPago(pedidoId, estadoPago, selectElemento) {
    const valorAnterior = selectElemento.dataset.valorAnterior ?? selectElemento.value;
    selectElemento.disabled = true;

    try {
      const formData = agregarProtecciones(new FormData());
      formData.append('pedido_id', pedidoId);
      formData.append('esta_pago', estadoPago);

      const response = await fetch('actualizar_estado.php', {
        method: 'POST',
        body: formData
      });

      let resultado;
      try {
        resultado = await response.json();
      } catch (errorJson) {
        throw new Error('La respuesta del servidor no es válida.');
      }

      if (!resultado.success) {
        throw new Error(resultado.message || 'No se pudo actualizar el estado de pago.');
      }

      selectElemento.dataset.valorAnterior = estadoPago;
    } catch (error) {
      const mensaje = error instanceof Error && error.message ? error.message : 'Error al actualizar el estado de pago.';
      alert(mensaje);
      selectElemento.value = valorAnterior;
    } finally {
      selectElemento.disabled = false;
    }
  }

  document.querySelectorAll('.btn-estado').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedidoId = btn.getAttribute('data-id');
      const nuevoEstado = btn.getAttribute('data-estado');

      if (nuevoEstado === 'Cancelado') {
        if (!confirm('¿Estás seguro de cancelar este pedido? Esto notificará al cliente.')) return;
      }

      actualizarEstado(pedidoId, nuevoEstado, btn);
    });
  });

  document.querySelectorAll('.estado-pago').forEach(select => {
    select.dataset.valorAnterior = select.value;
    select.addEventListener('change', () => {
      const pedidoId = select.getAttribute('data-id');
      actualizarEstadoPago(pedidoId, select.value, select);
    });
  });

  setInterval(() => location.reload(), 10000);
