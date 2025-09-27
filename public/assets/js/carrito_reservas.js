(function (window) {
  'use strict';

  const API_ENDPOINT = 'api/carrito_actualizar.php';

  function leerCarritoDesdeStorage() {
    try {
      const data = localStorage.getItem('carrito');
      if (!data) {
        return [];
      }
      const parsed = JSON.parse(data);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      console.error('No se pudo leer el carrito desde localStorage:', error);
      return [];
    }
  }

  function normalizarDetalle(item) {
    if (!item || typeof item !== 'object') {
      return {};
    }

    const base = item.producto && typeof item.producto === 'object' ? item.producto : item;
    const id = base.id ?? base.ID ?? null;

    return {
      id: id,
      nombre: base.nombre ?? '',
      precio: Number(base.precioUnitario ?? base.precio ?? 0),
      img: base.img ?? '',
    };
  }

  function agruparCarrito(carrito = []) {
    const resultado = {};

    carrito.forEach((item) => {
      if (!item || typeof item !== 'object') {
        return;
      }

      const detalle = normalizarDetalle(item);
      if (!detalle.id) {
        return;
      }

      const clave = String(detalle.id);
      if (!resultado[clave]) {
        resultado[clave] = {
          id: detalle.id,
          cantidad: 0,
          detalle,
        };
      }

      const incremento = Number(item.cantidad ?? item.qty ?? 1);
      resultado[clave].cantidad += Number.isFinite(incremento) ? incremento : 1;
    });

    return resultado;
  }

  function normalizarReservas(reservas = {}) {
    const resultado = {};

    Object.entries(reservas).forEach(([clave, valor]) => {
      if (!valor) {
        return;
      }

      const menuId = valor.menu_id ?? valor.id ?? clave;
      if (!menuId) {
        return;
      }

      const cantidad = Number(valor.cantidad ?? valor.qty ?? valor);
      if (!Number.isFinite(cantidad) || cantidad <= 0) {
        return;
      }

      resultado[String(menuId)] = {
        menu_id: Number(menuId),
        cantidad: Math.max(0, Math.floor(cantidad)),
      };
    });

    return resultado;
  }

  function normalizarExtras(extras = {}) {
    if (Array.isArray(extras)) {
      const map = {};
      extras.forEach((item) => {
        const detalle = normalizarDetalle(item);
        if (detalle.id) {
          map[String(detalle.id)] = detalle;
        }
      });
      return map;
    }

    const resultado = {};
    Object.entries(extras).forEach(([clave, valor]) => {
      const detalle = normalizarDetalle(valor);
      if (detalle.id) {
        resultado[String(detalle.id)] = detalle;
      } else if (clave) {
        detalle.id = Number.isFinite(Number(clave)) ? Number(clave) : clave;
        resultado[String(detalle.id)] = detalle;
      }
    });
    return resultado;
  }

  function construirCarritoDesdeMapa(reservasNormalizadas, detallesExtras = {}) {
    const carritoActual = leerCarritoDesdeStorage();
    const agrupadoActual = agruparCarrito(carritoActual);
    const extras = normalizarExtras(detallesExtras);
    const nuevoCarrito = [];

    Object.values(reservasNormalizadas).forEach((entrada) => {
      const clave = String(entrada.menu_id);
      const cantidad = Number(entrada.cantidad);
      if (!Number.isFinite(cantidad) || cantidad <= 0) {
        return;
      }

      const detalleExistente = agrupadoActual[clave]?.detalle;
      const detalleExtra = extras[clave];
      const detalle = detalleExtra || detalleExistente || {
        id: entrada.menu_id,
        nombre: '',
        precio: 0,
        img: '',
      };

      const itemBase = {
        id: detalle.id,
        nombre: detalle.nombre ?? '',
        precio: Number(detalle.precio ?? 0),
        img: detalle.img ?? '',
      };

      for (let i = 0; i < cantidad; i += 1) {
        nuevoCarrito.push(itemBase);
      }
    });

    return nuevoCarrito;
  }

  function actualizarLocalStorageDesdeReservas(reservas = {}, extras = {}) {
    const normalizadas = normalizarReservas(reservas);
    const carrito = construirCarritoDesdeMapa(normalizadas, extras);

    if (carrito.length === 0) {
      localStorage.removeItem('carrito');
    } else {
      localStorage.setItem('carrito', JSON.stringify(carrito));
    }

    return carrito;
  }

  function procesarRespuesta(respuesta, extras = {}) {
    if (!respuesta || typeof respuesta !== 'object') {
      throw new Error('Respuesta inválida del servidor.');
    }

    if (!respuesta.exito) {
      const mensaje = respuesta.mensaje || 'No se pudo actualizar el carrito.';
      throw new Error(mensaje);
    }

    const reservas = respuesta.reservas || {};
    const carritoActual = actualizarLocalStorageDesdeReservas(reservas, extras);

    return {
      reservas,
      disponibilidad: respuesta.disponibilidad || {},
      carrito: carritoActual,
    };
  }

  function sincronizarConServidor() {
    const carrito = leerCarritoDesdeStorage();
    const agrupado = agruparCarrito(carrito);
    const items = Object.values(agrupado).map((entrada) => ({
      id: entrada.id,
      cantidad: entrada.cantidad,
    }));

    return fetch(API_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'sync',
        items,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Error al sincronizar el carrito.');
        }
        return response.json();
      })
      .then((data) => procesarRespuesta(data))
      .catch((error) => {
        console.error('Sincronización fallida:', error);
        throw error;
      });
  }

  window.CarritoReservas = {
    leerCarritoDesdeStorage,
    agruparCarrito,
    actualizarLocalStorageDesdeReservas,
    procesarRespuesta,
    sincronizarConServidor,
  };
})(window);