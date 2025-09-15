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

    if (!buscadorInput || !categoriaSelect || !contenedor || !contenedorBotonMas) {
      return;
    }

    let offset = 0;
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

    const filtrarMenu = (reset = true) => {
      const texto = buscadorInput.value.trim();
      const categoria = categoriaSelect.value;

      if (reset) {
        offset = 0;
        contenedor.innerHTML = '';
        contenedorBotonMas.innerHTML = '';
      }

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then((resp) => resp.text())
        .then((html) => {
          const temp = document.createElement('div');
          temp.innerHTML = html;

          const tarjetas = temp.querySelectorAll('.col');
          tarjetas.forEach((tarjeta) => contenedor.appendChild(tarjeta));

          const boton = temp.querySelector('#btn-mostrar-mas');
          if (boton) {
            contenedorBotonMas.innerHTML = '';
            contenedorBotonMas.appendChild(boton);
            boton.addEventListener('click', cargarMasProductos);
          }

          reattachAddButtons();
          if (typeof AOS !== 'undefined') {
            AOS.refresh();
          }
        })
        .catch((error) => {
          console.error('Error al filtrar menú:', error);
        });
    };

    const cargarMasProductos = () => {
      const categoria = categoriaSelect.value;
      const texto = buscadorInput.value.trim();

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then((response) => response.text())
        .then((html) => {
          const temp = document.createElement('div');
          temp.innerHTML = html;

          const tarjetas = temp.querySelectorAll('.col');
          tarjetas.forEach((tarjeta) => contenedor.appendChild(tarjeta));

          const boton = temp.querySelector('#btn-mostrar-mas');
          contenedorBotonMas.innerHTML = '';
          if (boton) {
            contenedorBotonMas.appendChild(boton);
            boton.addEventListener('click', cargarMasProductos);
          }

          offset += limit;
          reattachAddButtons();
          if (typeof AOS !== 'undefined') {
            AOS.refresh();
          }
        })
        .catch((error) => {
          console.error('Error al cargar más productos:', error);
        });
    };

    buscadorInput.addEventListener('input', debounce(() => filtrarMenu(true), 300));
    categoriaSelect.addEventListener('change', () => filtrarMenu(true));

    actualizarContador();
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