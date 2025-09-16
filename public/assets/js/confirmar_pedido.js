document.addEventListener('DOMContentLoaded', () => {
  const formPedido = document.getElementById('form-pedido');

  const telefonoInput = document.getElementById('telefono');
  const codigoPaisSelect = document.getElementById('codigo_pais');
  const longitudesTelefono = {
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

  const limpiarNumero = (valor) => valor.replace(/[^0-9]/g, '');

  const actualizarMaxlengthTelefono = () => {
    if (!telefonoInput || !codigoPaisSelect) {
      return;
    }

    const codigoSeleccionado = codigoPaisSelect.value;
    const longitudesPermitidas = longitudesTelefono[codigoSeleccionado];
    if (Array.isArray(longitudesPermitidas) && longitudesPermitidas.length > 0) {
      telefonoInput.maxLength = Math.max(...longitudesPermitidas);
    } else {
      telefonoInput.removeAttribute('maxLength');
    }
  };

  const obtenerTelefonoFormateado = () => {
    if (!telefonoInput || !codigoPaisSelect) {
      return null;
    }

    const codigo = codigoPaisSelect.value;
    const numero = limpiarNumero(telefonoInput.value);
    const longitudesPermitidas = longitudesTelefono[codigo];

    if (!Array.isArray(longitudesPermitidas) || !longitudesPermitidas.includes(numero.length)) {
      return null;
    }

    const telefonoCompleto = `+${codigo}${numero}`;
    if (!/^\+\d{10,15}$/.test(telefonoCompleto)) {
      return null;
    }

    return {
      telefonoCompleto,
      codigo,
      numero,
    };
  };

  const marcarTelefonoValido = (esValido) => {
    if (!telefonoInput) {
      return;
    }

    telefonoInput.classList.toggle('is-invalid', !esValido);
    telefonoInput.setCustomValidity(esValido ? '' : 'Ingresá un número de teléfono válido.');
  };

  if (telefonoInput) {
    telefonoInput.value = limpiarNumero(telefonoInput.value);
  }

  if (codigoPaisSelect && telefonoInput) {
    actualizarMaxlengthTelefono();

    codigoPaisSelect.addEventListener('change', () => {
      actualizarMaxlengthTelefono();
      marcarTelefonoValido(true);
    });

    telefonoInput.addEventListener('input', () => {
      telefonoInput.value = limpiarNumero(telefonoInput.value);
      marcarTelefonoValido(true);
    });

    telefonoInput.addEventListener('blur', () => {
      const telefono = obtenerTelefonoFormateado();
      if (!telefono) {
        marcarTelefonoValido(false);
      }
    });
  }

  if (formPedido) {
    formPedido.addEventListener('submit', async (e) => {
      e.preventDefault();

      const telefonoNormalizado = obtenerTelefonoFormateado();
      if (!telefonoNormalizado) {
        marcarTelefonoValido(false);
        telefonoInput?.reportValidity();
        telefonoInput?.focus();
        return;
      }

      const carritoSinAgrupar = JSON.parse(localStorage.getItem('carrito') || '[]');
      if (carritoSinAgrupar.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      // Agrupar productos por id
      const agrupado = {};
      carritoSinAgrupar.forEach((item) => {
        const key = item.id;
        if (!agrupado[key]) {
          agrupado[key] = {
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            cantidad: 1,
          };
        } else {
          // Si ya existe, sumar cantidad y precio
          agrupado[key].cantidad++;
          agrupado[key].precio += item.precio;
        }
      });

      // Convertir a array limpio para enviar
      const carritoFinal = Object.values(agrupado).map((producto) => ({
        id: String(producto.id),
        nombre: producto.nombre,
        precio: Number(producto.precio),
        cantidad: Number(producto.cantidad),
      }));

      // Validar que el carrito final no esté vacío
      console.log('✅ Carrito final que se enviará:', carritoFinal);

      const form = e.target;
      const formData = new FormData(form);
      formData.set('carrito', JSON.stringify(carritoFinal));
      formData.set('telefono', telefonoNormalizado.telefonoCompleto);
      formData.set('codigo_pais', telefonoNormalizado.codigo);

      const usarPuntosCheckbox = localStorage.getItem('usar_puntos_activado') === '1';
      formData.set('usar_puntos', usarPuntosCheckbox ? '1' : '0');

      const response = await fetch('guardar_pedido.php', {
        method: 'POST',
        body: formData,
      }); // Enviar datos del formulario al servidor

      const texto = await response.text();
      console.log('Respuesta cruda del servidor:', texto);
      let resultado;
      try {
        // Intentar parsear la respuesta como JSON
        resultado = JSON.parse(texto);
      } catch (error) {
        // Si falla, mostrar error
        console.error('La respuesta no es JSON válido:', texto);
        document.getElementById('mensaje').innerHTML =
          '<div class="alert alert-danger">Ocurrió un error al procesar tu pedido. Intentalo de nuevo más tarde.</div>';
        return; // salir si no es JSON
      }

      if (resultado.exito) {
        // Construir modal dinámicamente
        let mensajePago = '';
        if (formData.get('metodo_pago') === 'MercadoPago') {
          const esDelivery = formData.get('tipo_entrega') === 'Delivery';
          mensajePago = `
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
  </div>
`;
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
          <a href="index.php" class="btn btn-gold px-4">Volver al inicio</a>
        </div>
      </div>
    </div>
  </div>
`;

        // Insertar modal al body
        let modalContainer = document.getElementById('modal-container');
        if (!modalContainer) {
          modalContainer = document.createElement('div');
          modalContainer.id = 'modal-container';
          document.body.appendChild(modalContainer);
        }
        modalContainer.innerHTML = modalHtml;

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalGracias'));
        modal.show();

        // Limpiar carrito y contador
        localStorage.removeItem('carrito');
        const contador = document.getElementById('contador-carrito');
        if (contador) {
          contador.textContent = '0';
        }

        form.reset();
        actualizarMaxlengthTelefono();
        marcarTelefonoValido(true);
        document.getElementById('mensaje').innerHTML = '';
      } else {
        // Mostrar error en div mensaje
        document.getElementById('mensaje').innerHTML = `
  <div id="mensaje-error" class="alert alert-danger">
    ${resultado.mensaje || 'Error desconocido'}
  </div>
`;
        if (resultado.scroll) {
          setTimeout(() => {
            const errorDiv = document.getElementById('mensaje-error');
            if (errorDiv) {
              errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }, 100);
        }
      }
    });
  }

  // Validar que solo se escriban letras y espacios en el campo nombre
  const nombreInput = document.getElementById('nombre');
  if (nombreInput) {
    nombreInput.addEventListener('input', function () {
      this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    });
  }
});

// Mostrar/ocultar campo dirección según tipo de entrega
window.mostrarDireccion = function mostrarDireccion(valor) {
  const grupoDireccion = document.getElementById('grupo-direccion');
  const grupoReferencias = document.getElementById('grupo-referencias');
  const aviso = document.getElementById('aviso-delivery');

  if (valor === 'Delivery') {
    // Si es Delivery, mostrar campo dirección y aviso
    if (grupoDireccion) {
      grupoDireccion.style.display = 'block';
    }
    if (grupoReferencias) {
      grupoReferencias.style.display = 'block';
    }
    if (aviso) {
      aviso.style.display = 'block';
    }
    const direccion = document.getElementById('direccion');
    if (direccion) {
      direccion.setAttribute('required', 'required');
    }
  } else {
    // Si es Retiro, ocultar campo dirección y aviso
    if (grupoDireccion) {
      grupoDireccion.style.display = 'none';
    }
    if (grupoReferencias) {
      grupoReferencias.style.display = 'none';
    }
    if (aviso) {
      aviso.style.display = 'none';
    }
    const direccion = document.getElementById('direccion');
    if (direccion) {
      direccion.removeAttribute('required');
    }
  }
};
