(() => {
  const form = document.getElementById('form-pedido');
  const mensajeDiv = document.getElementById('mensaje');
  const grupoDireccion = document.getElementById('grupo-direccion');
  const avisoDelivery = document.getElementById('aviso-delivery');
  const direccionInput = document.getElementById('direccion');
  const telefonoInput = document.getElementById('telefono');
  const nombreInput = document.getElementById('nombre');
  const radiosEntrega = document.querySelectorAll('input[name="tipo_entrega"]');

  function agruparCarrito(items) {
    const agrupado = items.reduce((acc, item) => {
      if (!acc[item.id]) {
        acc[item.id] = {
          id: item.id,
          nombre: item.nombre,
          precio: item.precio,
          cantidad: 1
        };
      } else {
        acc[item.id].cantidad += 1;
        acc[item.id].precio += item.precio;
      }
      return acc;
    }, {});

    return Object.values(agrupado).map((p) => ({
      id: String(p.id),
      nombre: p.nombre,
      precio: Number(p.precio),
      cantidad: Number(p.cantidad)
    }));
  }

  function toggleDireccion(valor) {
    if (!grupoDireccion || !avisoDelivery || !direccionInput) {
      return;
    }

    const mostrar = valor === 'Delivery';
    grupoDireccion.style.display = mostrar ? 'block' : 'none';
    avisoDelivery.style.display = mostrar ? 'block' : 'none';

    if (mostrar) {
      direccionInput.setAttribute('required', 'required');
    } else {
      direccionInput.removeAttribute('required');
      direccionInput.value = '';
    }
  }

  function mostrarModalExito(resultado, formData, mensajePago) {
    let modalContainer = document.getElementById('modal-container');
    if (!modalContainer) {
      modalContainer = document.createElement('div');
      modalContainer.id = 'modal-container';
      document.body.appendChild(modalContainer);
    }

    const modalHtml = `
      <div class="modal fade" id="modalGracias" tabindex="-1" aria-labelledby="modalGraciasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content glass-card border-0 shadow-lg" style="border-radius: 20px; background: rgba(30,30,30,0.85); color: #f8f9fa;">
            <div class="modal-header border-0 px-4 py-3">
              <h5 class="modal-title fw-bold d-flex align-items-center" id="modalGraciasLabel">
                <i class="fas fa-check-circle text-success me-2 fa-lg"></i>
                ¡Gracias por tu pedido!
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body fs-5 px-4 py-3">
              <p>🎉 <strong>${resultado.nombre}</strong>, tu pedido está en preparación. 🍔</p>
              ${parseFloat(resultado.descuento) > 0 ? `
                <div class="mb-2">
                  <p class="mb-0">💸 <strong>Total original:</strong> $${resultado.total_original}</p>
                  <p class="mb-0">🔻 <strong>Descuento por puntos:</strong> -$${resultado.descuento}</p>
                </div>` : ''}
              <p class="mt-3">💰 <strong>Total a pagar:</strong> $${resultado.total}</p>
              ${resultado.puntos_ganados > 0 ? `<p>🎁 <strong>Puntos ganados:</strong> ${resultado.puntos_ganados}</p>` : ''}
              ${mensajePago}
            </div>
            <div class="modal-footer border-0 px-4 py-3 justify-content-center">
              <a href="../index.php" class="btn btn-gold px-4">Volver al inicio</a>
            </div>
          </div>
        </div>
      </div>`;

    modalContainer.innerHTML = modalHtml;
    const modal = new bootstrap.Modal(document.getElementById('modalGracias'));
    modal.show();
  }

  function construirMensajePago(formData) {
    if (formData.get('metodo_pago') !== 'MercadoPago') {
      return '';
    }

    const esDelivery = formData.get('tipo_entrega') === 'Delivery';
    return `
      <div class="p-4 mt-4 rounded" style="background-color: var(--gray-bg); border: 1px solid rgba(255, 255, 255, 0.1);">
        <h5 class="mb-3" style="font-size: 1.6rem; color: var(--main-gold);">
          📲 Pagá por Mercado Pago
        </h5>
        <p class="mb-2" style="font-size: 1.1rem;"><strong>Alias:</strong> piccolovdr</p>
        <p class="mb-2" style="font-size: 1.1rem;"><strong>Nombre del titular:</strong> Mario Alberto Gaido</p>
        <p class="mb-2" style="font-size: 1.1rem;">
          Enviá el comprobante por WhatsApp a:
          <a href="https://wa.me/5493573438947" target="_blank" style="color: var(--main-gold); text-decoration: underline;">+54 9 3573 438947</a>
        </p>
        ${esDelivery ? `
          <p class="mt-3" style="color: var(--main-gold); font-size: 1.05rem;">
            💸 El costo del delivery varía entre <strong>$1000</strong> y <strong>$1500</strong> según la zona.
            Envianos un mensaje para confirmar el monto.
          </p>
        ` : ''}
      </div>`;
  }

  function mostrarError(mensaje, debeDesplazar) {
    if (!mensajeDiv) {
      return;
    }

    mensajeDiv.innerHTML = `
      <div id="mensaje-error" class="alert alert-danger">
        ${mensaje || 'Error desconocido'}
      </div>
    `;

    if (debeDesplazar) {
      setTimeout(() => {
        const errorDiv = document.getElementById('mensaje-error');
        if (errorDiv) {
          errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 100);
    }
  }

  async function enviarPedido(event) {
    event.preventDefault();

    const carritoSinAgrupar = JSON.parse(localStorage.getItem('carrito') || '[]');
    if (carritoSinAgrupar.length === 0) {
      alert('Tu carrito está vacío.');
      return;
    }

    const carritoFinal = agruparCarrito(carritoSinAgrupar);
    const formData = new FormData(form);
    formData.set('carrito', JSON.stringify(carritoFinal));

    const usarPuntos = localStorage.getItem('usar_puntos_activado') === '1';
    formData.set('usar_puntos', usarPuntos ? '1' : '0');

    try {
      const response = await fetch('../client/guardar_pedido.php', {
        method: 'POST',
        body: formData
      });

      const texto = await response.text();
      let resultado;
      try {
        resultado = JSON.parse(texto);
      } catch (error) {
        console.error('La respuesta no es JSON válido:', texto);
        mostrarError('Ocurrió un error al procesar tu pedido. Intentalo de nuevo más tarde.', true);
        return;
      }

      if (resultado.exito) {
        const mensajePago = construirMensajePago(formData);
        mostrarModalExito(resultado, formData, mensajePago);

        localStorage.removeItem('carrito');
        const contador = document.getElementById('contador-carrito');
        if (contador) {
          contador.textContent = '0';
        }

        form.reset();
        if (mensajeDiv) {
          mensajeDiv.innerHTML = '';
        }

        toggleDireccion('Retiro');
      } else {
        mostrarError(resultado.mensaje, Boolean(resultado.scroll));
      }
    } catch (error) {
      console.error('Error al enviar el pedido:', error);
      mostrarError('Ocurrió un error al procesar tu pedido. Intentalo de nuevo más tarde.', true);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (form) {
      form.addEventListener('submit', enviarPedido);
    }

    radiosEntrega.forEach((radio) => {
      radio.addEventListener('change', (event) => {
        toggleDireccion(event.target.value);
      });
    });

    const seleccionado = document.querySelector('input[name="tipo_entrega"]:checked');
    if (seleccionado) {
      toggleDireccion(seleccionado.value);
    } else {
      toggleDireccion('Retiro');
    }

    if (telefonoInput) {
      telefonoInput.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/[^0-9]/g, '');
      });
    }

    if (nombreInput) {
      nombreInput.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
      });
    }
  });
})();
