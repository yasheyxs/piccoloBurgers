(function () {
  'use strict';

  function initAOS() {
    if (typeof AOS !== 'undefined') {
      AOS.init({
        once: false,
        duration: 800,
      });
    }
  }

  function initTestimoniosControles() {
    const wrapper = document.getElementById('testimonios-wrapper');
    const btnLeft = document.getElementById('btn-left');
    const btnRight = document.getElementById('btn-right');

    if (!wrapper || !btnLeft || !btnRight) {
      return;
    }

    const scrollAmount = 340;

    btnLeft.addEventListener('click', () => {
      wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    btnRight.addEventListener('click', () => {
      wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });
  }

  function initMenu() {
    const buscadorInput = document.getElementById('buscador-menu');
    const categoriaSelect = document.getElementById('categoria');
    const contenedor = document.getElementById('contenedor-menu');
    const contenedorBotonMas = document.getElementById('contenedor-boton-mas');
    const limpiarFiltroBtn = document.getElementById('limpiar-filtro-menu');

    if (!buscadorInput || !categoriaSelect || !contenedor || !contenedorBotonMas) {
      return;
    }

    let offset = 0;
    let totalItems = 0;
    let isLoadingMore = false;
    const limit = 8;

    const actualizarContador = () => {
      const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      const contador = document.getElementById('contador-carrito');
      if (contador) {
        contador.textContent = carrito.length;
      }
    };

    const onAddClick = (event) => {
      const boton = event.currentTarget;
      const item = {
        id: boton.dataset.id,
        nombre: boton.dataset.nombre,
        precio: parseFloat(boton.dataset.precio),
        img: boton.dataset.img,
      };

      const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      carrito.push(item);
      localStorage.setItem('carrito', JSON.stringify(carrito));
      actualizarContador();

      const toastNombre = document.getElementById('toastProductoNombre');
      if (toastNombre) {
        toastNombre.textContent = item.nombre;
      }

      const toastEl = document.getElementById('toastAgregado');
      if (toastEl && typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();
      }
    };

    const reattachAddButtons = () => {
      document.querySelectorAll('.btn-agregar').forEach((button) => {
        button.removeEventListener('click', onAddClick);
        if (button.disabled) {
          return;
        }
        button.addEventListener('click', onAddClick);
      });
    };

    const debounce = (fn, wait = 200) => {
      let timeoutId;
      return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), wait);
      };
    };

    const renderTarjetas = (htmlString) => {
      if (!htmlString) {
        return 0;
      }

      const temp = document.createElement('div');
      temp.innerHTML = htmlString;

      const tarjetas = temp.querySelectorAll('.col');
      tarjetas.forEach((tarjeta) => contenedor.appendChild(tarjeta));

      return tarjetas.length;
    };

     const actualizarEstadoLimpiar = () => {
      if (!limpiarFiltroBtn) {
        return;
      }

      const tieneTexto = buscadorInput.value.trim().length > 0;
      limpiarFiltroBtn.disabled = !tieneTexto;
    };

    function filtrarMenu(reset = true) {
      const texto = buscadorInput.value.trim();
      const categoria = categoriaSelect.value;

      if (reset) {
        offset = 0;
        totalItems = 0;
        isLoadingMore = false;
        contenedor.innerHTML = '';
        contenedorBotonMas.innerHTML = '';
      }

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then((resp) => {
          if (!resp.ok) {
            throw new Error(`HTTP ${resp.status}`);
          }
return resp.json();
        })
        .then((data) => {
          const html = data && typeof data.html === 'string' ? data.html : '';
          const nuevasTarjetas = renderTarjetas(html);

          if (reset) {
            offset = nuevasTarjetas;
          } else {
            offset += nuevasTarjetas;
          }

          const total = Number(data && data.totalItems !== undefined ? data.totalItems : 0);
          totalItems = Number.isFinite(total) ? total : 0;

          actualizarBotonMas();
          reattachAddButtons();
          if (typeof AOS !== 'undefined') {
            AOS.refresh();
          }
        })
        .catch((error) => {
          console.error('Error al filtrar menú:', error);
        });
    }

    const filtrarMenuDebounced = debounce(() => filtrarMenu(true), 300);

    function cargarMasProductos() {
      if (isLoadingMore || offset >= totalItems) {
        return;
      }

      isLoadingMore = true;
      const categoria = categoriaSelect.value;
      const texto = buscadorInput.value.trim();

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          const html = data && typeof data.html === 'string' ? data.html : '';
          const nuevasTarjetas = renderTarjetas(html);
          offset += nuevasTarjetas;

          const total = Number(data && data.totalItems !== undefined ? data.totalItems : totalItems);
          if (Number.isFinite(total)) {
            totalItems = total;
          }

          actualizarBotonMas();
          reattachAddButtons();
          if (typeof AOS !== 'undefined') {
            AOS.refresh();
          }
        })
        .catch((error) => {
          console.error('Error al cargar más productos:', error);
          })
        .finally(() => {
          isLoadingMore = false;
        });
    }

    function actualizarBotonMas() {
      contenedorBotonMas.innerHTML = '';
      if (offset < totalItems) {
        const boton = document.createElement('button');
        boton.id = 'btn-mostrar-mas';
        boton.className = 'btn btn-gold';
        boton.textContent = 'Mostrar más';
        boton.addEventListener('click', cargarMasProductos);
        contenedorBotonMas.appendChild(boton);
      }
    }

    buscadorInput.addEventListener('input', () => {
      actualizarEstadoLimpiar();
      filtrarMenuDebounced();
    });
    categoriaSelect.addEventListener('change', () => filtrarMenu(true));

    if (limpiarFiltroBtn) {
      limpiarFiltroBtn.addEventListener('click', () => {
        if (!buscadorInput.value.trim()) {
          return;
        }

        buscadorInput.value = '';
        actualizarEstadoLimpiar();
        filtrarMenu(true);
        buscadorInput.focus();
      });
    }

    actualizarContador();
    actualizarEstadoLimpiar();
    filtrarMenu(true);
  }

  function initContactoToast() {
    const toastEl = document.getElementById('toastContacto');
    if (toastEl && typeof bootstrap !== 'undefined') {
      const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 2500,
      });
      toast.show();
    }
  }

  function cerrarBurbuja() {
    const burbuja = document.getElementById('registro-burbuja');
    if (!burbuja) {
      return;
    }

    burbuja.classList.add('fade-out');
    setTimeout(() => {
      burbuja.style.display = 'none';
    }, 500);
  }

  window.cerrarBurbuja = cerrarBurbuja;

  document.addEventListener('DOMContentLoaded', () => {
    initAOS();
    initTestimoniosControles();
    initMenu();
    initContactoToast();
  });
})();