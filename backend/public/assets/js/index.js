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
    const productoCache = new Map();
    const reservasAPI = window.CarritoReservas || null;

    const registrarProductoEnCache = (button) => {
      if (!button) {
        return;
      }

      const id = button.dataset.id;
      if (!id) {
        return;
      }

      productoCache.set(String(id), {
        id: button.dataset.id,
        nombre: button.dataset.nombre,
        precio: Number(button.dataset.precio || 0),
        img: button.dataset.img,
      });
    };

    const actualizarBotonDisponibilidad = (button, unidadesDisponibles) => {
      if (!button) {
        return;
      }

      const unidades = Number.isFinite(Number(unidadesDisponibles))
        ? Number(unidadesDisponibles)
        : Number(button.dataset.disponibles || 0);

      const hayStock = unidades > 0;
      button.disabled = !hayStock;
      button.classList.toggle('btn-sin-stock', !hayStock);
      button.textContent = hayStock ? 'Agregar' : 'Sin stock';
      button.dataset.disponibles = String(Math.max(0, Math.floor(unidades)));
    };

    const mostrarToastAgregado = (nombreProducto) => {
      const toastNombre = document.getElementById('toastProductoNombre');
      if (toastNombre) {
        toastNombre.textContent = nombreProducto || '';
      }

      const toastEl = document.getElementById('toastAgregado');
      if (toastEl && typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();
      }
    };

    const procesarRespuestaServidor = (data, extras = {}) => {
      if (reservasAPI && typeof reservasAPI.procesarRespuesta === 'function') {
        return reservasAPI.procesarRespuesta(data, extras);
      }

      if (!data || typeof data !== 'object') {
        throw new Error('Respuesta inválida del servidor.');
      }

      if (!data.exito) {
        throw new Error(data.mensaje || 'No se pudo actualizar el carrito.');
      }

      return {
        reservas: data.reservas || {},
        disponibilidad: data.disponibilidad || {},
      };
    };

    const refrescarDisponibilidadBotones = () => {
      const botones = Array.from(document.querySelectorAll('.btn-agregar'));
      if (botones.length === 0) {
        return Promise.resolve();
      }

      const ids = Array.from(
        new Set(
          botones
            .map((btn) => btn.dataset.id)
            .filter((id) => id)
        )
      );

      if (ids.length === 0) {
        return Promise.resolve();
      }

      return fetch('api/disponibilidad_menu.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ menuIds: ids }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          if (!data || !data.exito || !data.disponibilidad) {
            return;
          }

          botones.forEach((button) => {
            const id = button.dataset.id;
            const info =
              data.disponibilidad[id] ||
              data.disponibilidad[String(Number(id))] ||
              null;
            const unidades = info ? info.unidades_disponibles : button.dataset.disponibles;
            actualizarBotonDisponibilidad(button, unidades);
          });
        })
        .catch((error) => {
          console.error('Error al actualizar disponibilidad:', error);
        });
    };

    const solicitarActualizacion = (payload, extras = {}) => {
      return fetch('api/carrito_actualizar.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          return response.json();
        })
        .then((data) => procesarRespuestaServidor(data, extras));
    };

    const actualizarContador = () => {
      const contador = document.getElementById('contador-carrito');
      if (!contador) {
        return;
      }
      let carrito = [];
      if (window.CarritoReservas && typeof window.CarritoReservas.leerCarritoDesdeStorage === 'function') {
        carrito = window.CarritoReservas.leerCarritoDesdeStorage();
      } else {
        try {
          carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
        } catch (error) {
          carrito = [];
        }
      }

      contador.textContent = carrito.length;
    };

    const onAddClick = (event) => {
      const boton = event.currentTarget;

      if (!boton) {
        return;
      }

      const item = {
        id: boton.dataset.id,
        nombre: boton.dataset.nombre,
        precio: parseFloat(boton.dataset.precio),
        img: boton.dataset.img,
      };

      if (!item.id) {
        return;
      }

      registrarProductoEnCache(boton);
      boton.disabled = true;

      const extras = {};
      extras[String(item.id)] = productoCache.get(String(item.id)) || item;

      solicitarActualizacion(
        {
          action: 'increment',
          menuId: Number(item.id),
        },
        extras
      )
        .then((resultado) => {
          actualizarContador();

          const disponibilidadActual = resultado.disponibilidad || {};
          const infoProducto =
            disponibilidadActual[item.id] ||
            disponibilidadActual[String(Number(item.id))] ||
            null;
          const unidades = infoProducto
            ? infoProducto.unidades_disponibles
            : boton.dataset.disponibles;

          actualizarBotonDisponibilidad(boton, unidades);
          mostrarToastAgregado(item.nombre);
          return refrescarDisponibilidadBotones();
        })
        .catch((error) => {
          console.error('No se pudo agregar al carrito:', error);
          alert(error.message || 'Sin stock disponible para este producto.');
        })
        .finally(() => {
          if (Number(boton.dataset.disponibles || 0) > 0) {
            boton.disabled = false;
          }
        });
    };

    const reattachAddButtons = () => {
      document.querySelectorAll('.btn-agregar').forEach((button) => {
        registrarProductoEnCache(button);
        button.removeEventListener('click', onAddClick);
        if (!button.disabled) {
          button.addEventListener('click', onAddClick);
        }
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
      tarjetas.forEach((tarjeta) => {
        contenedor.appendChild(tarjeta);
        const boton = tarjeta.querySelector('.btn-agregar');
        if (boton) {
          registrarProductoEnCache(boton);
          actualizarBotonDisponibilidad(boton, boton.dataset.disponibles);
        }
      });

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

      return fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
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
          return refrescarDisponibilidadBotones();
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

      return fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
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
          return refrescarDisponibilidadBotones();
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
    const sincronizacionInicial =
      reservasAPI && typeof reservasAPI.sincronizarConServidor === 'function'
        ? reservasAPI
            .sincronizarConServidor()
            .catch((error) => {
              console.error('No se pudo sincronizar el carrito:', error);
            })
        : Promise.resolve();

    sincronizacionInicial.finally(() => {
      filtrarMenu(true);
    });
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