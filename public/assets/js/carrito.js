const estadoTotalCarrito = {
  items: [],
  total: 0,
};


function cargarCarrito() {
  const items = JSON.parse(localStorage.getItem('carrito')) || [];
  const contenedor = document.getElementById('carrito-contenido');
  const totalSpan = document.getElementById('total');

  if (!contenedor || !totalSpan) {
    return;
  }

  contenedor.innerHTML = '';
  let total = 0;

  const agrupado = {};
  items.forEach((item) => {
    const key = item.id;
    if (!agrupado[key]) {
      agrupado[key] = {
        id: item.id,
        nombre: item.nombre,
        precio: item.precio,
        img: item.img,
        cantidad: 1,
      };
    } else {
      agrupado[key].cantidad += 1;
      agrupado[key].precio += item.precio;
    }
  });

  const productos = Object.values(agrupado);

  if (productos.length === 0) {
    estadoTotalCarrito.items = [];
    estadoTotalCarrito.total = 0;
    contenedor.innerHTML = "<p class='text-center'>Tu carrito está vacío.</p>";
    totalSpan.textContent = '0.00';

    const finalizarBtn = document.getElementById('btnFinalizar');
    const cancelarBtn = document.getElementById('btnCancelar');
    if (finalizarBtn) {
      finalizarBtn.disabled = true;
    }
    if (cancelarBtn) {
      cancelarBtn.disabled = true;
    }
    return;
  }

  const finalizarBtn = document.getElementById('btnFinalizar');
  const cancelarBtn = document.getElementById('btnCancelar');
  if (finalizarBtn) {
    finalizarBtn.disabled = false;
  }
  if (cancelarBtn) {
    cancelarBtn.disabled = false;
  }

  const html = productos
    .map((item) => {
      const subtotal = Number(item.precio) || 0;
      const cantidad = Number(item.cantidad) || 1;
      total += subtotal;
      return `
      <div class="col d-flex" data-aos="fade-up">
        <div class="card position-relative d-flex flex-column h-100 w-100">
          <img src="${item.img}" class="card-img-top" alt="Foto de ${item.nombre}">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${item.nombre}</h5>
            <p class="card-text mb-1"><strong>Precio unitario:</strong> $${(subtotal / cantidad).toFixed(2)}</p>
            <p class="card-text mb-1"><strong>Cantidad:</strong> ${cantidad}</p>
            <p class="card-text"><strong>Subtotal:</strong> $${subtotal.toFixed(2)}</p>
            <div class="mt-auto d-flex flex-wrap gap-2">
              <button class="btn btn-secondary" onclick="disminuirCantidad(${item.id})">-</button>
              <button class="btn btn-secondary" onclick="aumentarCantidad(${item.id})">+</button>
              <button class="btn btn-danger" onclick="eliminarProducto(${item.id})">Eliminar</button>
            </div>
          </div>
        </div>
      </div>`;
  })
    .join('');

  contenedor.innerHTML = html;
  estadoTotalCarrito.items = productos;
  estadoTotalCarrito.total = total;
  totalSpan.textContent = total.toFixed(2);
  actualizarTotal();
  actualizarTotal(productos, total);

  totalSpan.textContent = total.toFixed(2);
  actualizarTotal();
}

function aumentarCantidad(id) {
  const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  const producto = carrito.find((p) => p.id.toString() === id.toString());
  if (!producto) {
    return;
  }

  carrito.push({ ...producto });
  localStorage.setItem('carrito', JSON.stringify(carrito));
  cargarCarrito();
  actualizarContador();
}

function disminuirCantidad(id) {
  const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  const index = carrito.findIndex((p) => p.id.toString() === id.toString());
  if (index === -1) {
    return;
  }

  carrito.splice(index, 1);
  localStorage.setItem('carrito', JSON.stringify(carrito));
  cargarCarrito();
  actualizarContador();
}

function eliminarProducto(id) {
  let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  carrito = carrito.filter((p) => p.id.toString() !== id.toString());
  localStorage.setItem('carrito', JSON.stringify(carrito));
  cargarCarrito();
  actualizarContador();
}

function vaciarCarrito() {
  localStorage.removeItem('carrito');
  cargarCarrito();
  actualizarContador();
}

function actualizarContador() {
  const items = JSON.parse(localStorage.getItem('carrito')) || [];
  const contador = document.getElementById('contador-carrito');
  if (contador) {
    contador.textContent = items.length;
  }
}

function actualizarTotal(items = estadoTotalCarrito.items, total = estadoTotalCarrito.total) {

  const usarPuntos = document.getElementById('usarPuntos')?.checked;
  localStorage.setItem('usar_puntos_activado', usarPuntos ? '1' : '0');
  const totalSpan = document.getElementById('total');
  if (!totalSpan) {
    return;
  }

  if (!Array.isArray(items)) {
    items = estadoTotalCarrito.items;
  } else {
    estadoTotalCarrito.items = items;
  }

  if (typeof total !== 'number' || Number.isNaN(total)) {
    total = items.reduce((acc, item) => acc + Number(item.precio || 0), 0);
  }
  estadoTotalCarrito.total = total;

  const puntosDisponibles = parseInt(document.getElementById('puntosDisponibles')?.value || '0', 10);
  let descuento = 0;

  document.getElementById('puntos_usados')?.remove();
  document.getElementById('puntos_warning')?.remove();

  if (usarPuntos) {
    const valorPorPunto = 20;
    const minimoParaCanjear = 50;
    const maximoDescuento = total * 0.25;

    if (puntosDisponibles < minimoParaCanjear) {
      totalSpan.insertAdjacentHTML(
        'afterend',
        `<div id="puntos_warning" class="text-danger">⚠️ Necesitás al menos ${minimoParaCanjear} puntos para canjear.</div>`,
      );
    } else if (total < 1000) {
      totalSpan.insertAdjacentHTML(
        'afterend',
        '<div id="puntos_warning" class="text-danger">⚠️ El total debe ser al menos $1000 para canjear puntos.</div>',
      );
    } else {
      const puntosPosibles = Math.floor(maximoDescuento / valorPorPunto);
      const puntosAUsar = Math.min(puntosDisponibles, puntosPosibles);
      descuento = puntosAUsar * valorPorPunto;
      totalSpan.insertAdjacentHTML(
        'afterend',
        `<div id="puntos_usados" class="text-success">- $${descuento.toFixed(2)} aplicados en puntos</div>`,
      );
    }
  }

  totalSpan.textContent = (total - descuento).toFixed(2);

  const finalizarBtn = document.querySelector('#formPedido button[type="submit"]');
  if (finalizarBtn) {
    finalizarBtn.disabled = Boolean(document.getElementById('puntos_warning'));
  }
}

function mostrarConfirmacionCancelar() {
  const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  const detalleDiv = document.getElementById('detallePedidoModal');
  if (!detalleDiv) {
    return;
  }

  if (carrito.length === 0) {
    detalleDiv.innerHTML = "<p class='text-muted'>El carrito está vacío.</p>";
  } else {
    const resumen = carrito.reduce((acc, item) => {
      if (!acc[item.nombre]) {
        acc[item.nombre] = {
          cantidad: 1,
          precio: item.precio,
        };
      } else {
        acc[item.nombre].cantidad += 1;
        acc[item.nombre].precio += item.precio;
      }
      return acc;
    }, {});

    detalleDiv.innerHTML = `
      <ul class="list-group">
        ${Object.entries(resumen)
          .map(
            ([nombre, datos]) => `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${nombre} (x${datos.cantidad})</span>
                <span>$${datos.precio.toFixed(2)}</span>
              </li>`
          )
          .join('')}
      </ul>`;
  }

  const modalElement = document.getElementById('modalCancelarPedido');
  if (modalElement && typeof bootstrap !== 'undefined') {
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }
}

function confirmarCancelacion() {
  localStorage.removeItem('carrito');

  const modalElement = document.getElementById('modalCancelarPedido');
  if (modalElement && typeof bootstrap !== 'undefined') {
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
      modal.hide();
    }
  }

  cargarCarrito();
  actualizarContador();
}

function prepararEnvioPedido(event) {
  const usarPuntosChecked = document.getElementById('usarPuntos')?.checked;
  const usarPuntosInput = document.getElementById('usarPuntosInput');
  if (usarPuntosInput) {
    usarPuntosInput.value = usarPuntosChecked ? '1' : '0';
  }

  const items = JSON.parse(localStorage.getItem('carrito')) || [];
  const agrupado = {};

  items.forEach((item) => {
    const key = item.id;
    if (!agrupado[key]) {
      agrupado[key] = {
        id: item.id,
        nombre: item.nombre,
        precio: item.precio,
        cantidad: 1,
      };
    } else {
      agrupado[key].cantidad += 1;
      agrupado[key].precio += item.precio;
    }
  });

  const carritoFinal = Object.values(agrupado).map((producto) => ({
    id: String(producto.id),
    nombre: producto.nombre,
    precio: Number(producto.precio),
    cantidad: Number(producto.cantidad),
  }));

  const hayIncompletos = carritoFinal.some(
    (producto) => !producto.id || !producto.nombre || typeof producto.precio !== 'number' || !producto.cantidad,
  );

  if (hayIncompletos) {
    console.error('⚠️ Hay productos incompletos:', carritoFinal);
    alert('Error: uno de los productos del carrito no tiene toda la información necesaria.');
    event.preventDefault();
    return;
  }

  const carritoInput = document.getElementById('carritoInput');
  if (carritoInput) {
    carritoInput.value = JSON.stringify(carritoFinal);
  }

  console.log('✅ Carrito que se enviará:', carritoFinal);
}

function ajustarPaddingContenido() {
  const navbar = document.querySelector('.navbar');
  const contenido = document.querySelector('.contenido-ajustado');

  if (navbar && contenido) {
    const alturaNavbar = navbar.getBoundingClientRect().height;
    contenido.style.paddingTop = `${alturaNavbar + 24}px`;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  console.log('Contenido del carrito:', localStorage.getItem('carrito'));

  const usarPuntosCheckbox = document.getElementById('usarPuntos');
  if (usarPuntosCheckbox) {
    const usarPuntosGuardado = localStorage.getItem('usar_puntos_activado') === '1';
    usarPuntosCheckbox.checked = usarPuntosGuardado;
    usarPuntosCheckbox.addEventListener('change', actualizarTotal);
  }

  const cancelarBtn = document.getElementById('btnCancelar');
  if (cancelarBtn) {
    cancelarBtn.addEventListener('click', mostrarConfirmacionCancelar);
  }

  const confirmarBtn = document.getElementById('btnConfirmarCancelacion');
  if (confirmarBtn) {
    confirmarBtn.addEventListener('click', confirmarCancelacion);
  }

  const formPedido = document.getElementById('formPedido');
  if (formPedido) {
    formPedido.addEventListener('submit', prepararEnvioPedido);
  }

  cargarCarrito();
  actualizarContador();
  ajustarPaddingContenido();
});