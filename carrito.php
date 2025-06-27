<?php
session_start();

include("admin/bd.php"); ?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Carrito - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="./img/favicon.png" type="image/x-icon" />

  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --gray-bg: #2c2c2c;
      --text-light: #ffffff;
      --text-muted: #cccccc;
      --font-main: 'Inter', sans-serif;
      --font-title: 'Bebas Neue', sans-serif;
    }

    body {
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-size: 1rem;
      line-height: 1.6;
    }

    /* Botón dorado */
    .btn-gold {
      background-color: var(--main-gold);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      padding: 10px 30px;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    .btn-outline-danger-rounded {
      color: #fff;
      background-color: transparent;
      border: 2px solid #dc3545;
      border-radius: 30px;
      padding: 10px 30px;
      font-weight: bold;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .btn-outline-danger-rounded:hover {
      background-color: #dc3545;
      color: #fff;
      transform: scale(1.05);
    }

    .navbar {
      background-color: #111;
    }

    .navbar-brand,
    .nav-link {
      font-family: var(--font-main);
      font-size: 1.2rem;
    }

    .navbar-brand {
      font-family: var(--font-title);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 1.5rem;
    }



    /* Cards */
    .card {
      background-color: var(--gray-bg);
      border-radius: 16px;
      border: none;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
    }

    .card-img-top {
      display: block;
      max-height: 200px;
      width: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card:hover .card-img-top {
      transform: scale(1.05);
    }

    .card-title {
      font-family: var(--font-title);
      font-size: 1.8rem;
      color: var(--text-light);
    }

    .card-text {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    .card-footer {
      background-color: transparent;
      color: var(--text-light);
      font-weight: 600;
      font-size: 1rem;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
      border-left: 4px solid var(--main-gold);
      padding-left: 12px;
    }

    .card-footer::before {
      content: "👤 ";
    }

    .modal-content {
      background-color: var(--gray-bg);
      color: var(--text-light);
    }

    .modal-body .list-group-item {
      background-color: transparent;
      color: var(--text-light);
      border-color: rgba(255, 255, 255, 0.1);
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCarrito">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCarrito">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item me-3">
            <a class="nav-link position-relative" href="carrito.php">
              <i class="fas fa-shopping-cart"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
                0
              </span>
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-gold" href="index.php"><i class="fas fa-chevron-left"></i> Volver</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


  <div class="container mt-5">
    <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>
    <div id="carrito-contenido" class="row"></div>

    <div class="text-end mt-4">
      <h4>Total: $<span id="total">0.00</span></h4>

      <?php

      $descuento_maximo = 0;
      $puntos_cliente = 0;

      if (isset($_SESSION["cliente"])) {
        $cliente_id = $_SESSION["cliente"]["id"];
        $stmt = $conexion->prepare("SELECT puntos FROM tbl_clientes WHERE ID = ?");
        $stmt->execute([$cliente_id]);
        $puntos_cliente = $stmt->fetchColumn();
      }
      ?>

      <?php if (isset($_SESSION["cliente"])): ?>
        <div class="form-check mt-3 d-flex justify-content-end align-items-center gap-2">
          <input class="form-check-input mt-0" type="checkbox" id="usarPuntos" onclick="actualizarTotal()">
          <label class="form-check-label mb-0" for="usarPuntos">
            Usar puntos (<?php echo $puntos_cliente; ?> disponibles)
          </label>
          <input type="hidden" id="puntosDisponibles" value="<?php echo $puntos_cliente; ?>">
        </div>

      <?php endif; ?>



      <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
        <form id="formPedido" action="confirmar_pedido.php" method="post" class="m-0">
          <input type="hidden" name="carrito" id="carritoInput">
          <input type="hidden" name="usar_puntos" id="usarPuntosInput" value="0">
          <button type="submit" class="btn btn-gold" id="btnFinalizar">🧾 Finalizar Pedido</button>
        </form>
        <button class="btn btn-outline-danger-rounded" id="btnCancelar" onclick="mostrarConfirmacionCancelar()">Cancelar Pedido</button>
      </div>


    </div>
  </div>

  <footer class="bg-dark text-light text-center py-3 mt-5">
    <p>&copy; 2025 Piccolo Burgers — Developed by: <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong></p>
  </footer>

  <script>
    function cargarCarrito() {
      const items = JSON.parse(localStorage.getItem('carrito')) || [];
      const contenedor = document.getElementById("carrito-contenido");
      const totalSpan = document.getElementById("total");
      contenedor.innerHTML = "";
      let total = 0;

      // Agrupar productos por ID
      const agrupado = {};
      items.forEach(item => {
        const key = item.id;
        if (!agrupado[key]) {
          agrupado[key] = {
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            img: item.img,
            cantidad: 1
          };

        } else {
          agrupado[key].cantidad++;
          agrupado[key].precio += item.precio;
        }
      });

      const productos = Object.values(agrupado);

      if (productos.length === 0) {
        contenedor.innerHTML = "<p class='text-center'>Tu carrito está vacío.</p>";
        totalSpan.textContent = "0.00";

        // Desactivar botones
        document.getElementById("btnFinalizar").disabled = true;
        document.getElementById("btnCancelar").disabled = true;
        return;
      } else {
        // Activar botones si hay productos
        document.getElementById("btnFinalizar").disabled = false;
        document.getElementById("btnCancelar").disabled = false;
      }


      productos.forEach(item => {
        total += item.precio;
        contenedor.innerHTML += `
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
          <img src="${item.img}" class="card-img-top" alt="${item.nombre}">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${item.nombre}</h5>
            <p class="card-text mb-1"><strong>Precio unitario:</strong> $${(item.precio / item.cantidad).toFixed(2)}</p>
            <p class="card-text mb-1"><strong>Cantidad:</strong> ${item.cantidad}</p>
            <p class="card-text"><strong>Subtotal:</strong> $${item.precio.toFixed(2)}</p>
            <div class="mt-auto d-flex gap-2">
              <button class="btn btn-secondary" onclick="disminuirCantidad(${item.id})">-</button>
              <button class="btn btn-secondary" onclick="aumentarCantidad(${item.id})">+</button>
              <button class="btn btn-danger" onclick="eliminarProducto(${item.id})">Eliminar</button>
            </div>
          </div>
        </div>
      </div>`;
      });

      totalSpan.textContent = total.toFixed(2);
      actualizarTotal(); // Para actualizar puntos y total correctamente
    }

    function aumentarCantidad(id) {
      let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      // Convertir el id a cadena para asegurar la comparación
      const producto = carrito.find(p => p.id.toString() === id.toString());
      if (producto) {
        carrito.push({
          ...producto
        });
        localStorage.setItem('carrito', JSON.stringify(carrito));
        cargarCarrito();
        actualizarContador();
      }
    }


    function disminuirCantidad(id) {
      let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      const index = carrito.findIndex(p => p.id.toString() === id.toString());
      if (index !== -1) {
        carrito.splice(index, 1); // removemos solo una unidad
        localStorage.setItem('carrito', JSON.stringify(carrito));
        cargarCarrito();
        actualizarContador();
      }
    }

    function eliminarProducto(id) {
      let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      carrito = carrito.filter(p => p.id.toString() !== id.toString());
      localStorage.setItem('carrito', JSON.stringify(carrito));
      cargarCarrito();
      actualizarContador();
    }

    function vaciarCarrito() {
      localStorage.removeItem("carrito");
      cargarCarrito();
      actualizarContador();
    }

    function actualizarContador() {
      const items = JSON.parse(localStorage.getItem('carrito')) || [];
      const contador = document.getElementById("contador-carrito");
      if (contador) {
        contador.textContent = items.length;
      }
    }

    function actualizarTotal() {
      const usarPuntos = document.getElementById("usarPuntos")?.checked;
      localStorage.setItem("usar_puntos_activado", usarPuntos ? "1" : "0");

      const items = JSON.parse(localStorage.getItem('carrito')) || [];
      const totalSpan = document.getElementById("total");
      let total = items.reduce((acc, item) => acc + item.precio, 0);
      const puntosDisponibles = parseInt(document.getElementById("puntosDisponibles")?.value || 0);

      let descuento = 0;
      // Remover avisos anteriores
      document.getElementById("puntos_usados")?.remove();
      document.getElementById("puntos_warning")?.remove();

      if (usarPuntos) {
        const valorPorPunto = 20;
        const minimoParaCanjear = 50;
        const maximoDescuento = total * 0.25;

        if (puntosDisponibles < minimoParaCanjear) {
          totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_warning" class="text-danger">⚠️ Necesitás al menos ${minimoParaCanjear} puntos para canjear.</div>`);
        } else if (total < 1000) {
          totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_warning" class="text-danger">⚠️ El total debe ser al menos $1000 para canjear puntos.</div>`);
        } else {
          const puntosPosibles = Math.floor(maximoDescuento / valorPorPunto);
          const puntosAUsar = Math.min(puntosDisponibles, puntosPosibles);
          descuento = puntosAUsar * valorPorPunto;
          totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_usados" class="text-success">- $${descuento.toFixed(2)} aplicados en puntos</div>`);
        }
      }

      totalSpan.textContent = (total - descuento).toFixed(2);

      // Bloquear o habilitar el botón de finalizar pedido
      const finalizarBtn = document.querySelector("#formPedido button[type='submit']");
      if (document.getElementById("puntos_warning")) {
        finalizarBtn.disabled = true;
      } else {
        finalizarBtn.disabled = false;
      }
    }


    window.onload = () => {
      console.log("Contenido del carrito:", localStorage.getItem('carrito'));
      cargarCarrito();
      actualizarContador();
      document.getElementById("usarPuntos")?.addEventListener("change", actualizarTotal);
    };

    function mostrarConfirmacionCancelar() {
      const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
      const detalleDiv = document.getElementById("detallePedidoModal");
      if (carrito.length === 0) {
        detalleDiv.innerHTML = "<p class='text-muted'>El carrito está vacío.</p>";
      } else {
        const resumen = carrito.reduce((acc, item) => {
          if (!acc[item.nombre]) {
            acc[item.nombre] = {
              cantidad: 1,
              precio: item.precio
            };
          } else {
            acc[item.nombre].cantidad += 1;
            acc[item.nombre].precio += item.precio;
          }
          return acc;
        }, {});
        detalleDiv.innerHTML = `
      <ul class="list-group">
        ${Object.entries(resumen).map(([nombre, datos]) => `
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>${nombre} (x${datos.cantidad})</span>
            <span>$${datos.precio.toFixed(2)}</span>
          </li>
        `).join("")}
      </ul>
    `;
      }

      const modal = new bootstrap.Modal(document.getElementById("modalCancelarPedido"));
      modal.show();
    }

    function confirmarCancelacion() {
      localStorage.removeItem("carrito");
      const modal = bootstrap.Modal.getInstance(document.getElementById("modalCancelarPedido"));
      modal.hide();
      cargarCarrito();
      actualizarContador();
    }

    document.getElementById("formPedido").addEventListener("submit", function(e) {
      const usarPuntosChecked = document.getElementById("usarPuntos")?.checked;
      document.getElementById("usarPuntosInput").value = usarPuntosChecked ? "1" : "0";

      const items = JSON.parse(localStorage.getItem("carrito")) || [];
      const agrupado = {};

      items.forEach(item => {
        const key = item.id;
        if (!agrupado[key]) {
          agrupado[key] = {
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            cantidad: 1
          };
        } else {
          agrupado[key].cantidad += 1;
          agrupado[key].precio += item.precio;
        }
      });


      const carritoFinal = Object.values(agrupado).map(p => ({
        id: String(p.id),
        nombre: p.nombre,
        precio: Number(p.precio),
        cantidad: Number(p.cantidad)
      }));

      const hayIncompletos = carritoFinal.some(
        p => !p.id || !p.nombre || typeof p.precio !== "number" || !p.cantidad
      );

      if (hayIncompletos) {
        console.error("⚠️ Hay productos incompletos:", carritoFinal);
        alert("Error: uno de los productos del carrito no tiene toda la información necesaria.");
        e.preventDefault();
        return;
      }

      console.log("✅ Carrito que se enviará:", carritoFinal);
      document.getElementById("carritoInput").value = JSON.stringify(carritoFinal);
    });
  </script>

  <!-- Modal de confirmación para cancelar el pedido -->
  <div class="modal fade" id="modalCancelarPedido" tabindex="-1" aria-labelledby="modalCancelarPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="modalCancelarPedidoLabel">¿Cancelar pedido?</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-3">Estás por cancelar tu pedido. Este es el detalle actual:</p>
          <div id="detallePedidoModal" class="mb-3"></div>
          <div class="text-end">
            <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Conservar</button>
            <button class="btn btn-danger" onclick="confirmarCancelacion()">Cancelar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>