<?php
session_start();

include("admin/bd.php"); ?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

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
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: var(--font-main);
      color: var(--text-light);
      background: url('img/HamLoginCliente.jpg') no-repeat center center fixed;
      background-size: cover;
      background-attachment: fixed;
      overflow-x: hidden;
      overflow-y: auto;
    }

    .navbar {
      background-color: #111;
      transition: background-color 0.4s ease, backdrop-filter 0.4s ease, box-shadow 0.4s ease;
    }

    /* Estilos de texto del navbar */
    .navbar-brand,
    .nav-link {
      font-family: var(--font-main);
      font-size: 1.2rem;
    }

    /* Sticky al hacer scroll */
    .sticky-navbar {
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 999;
    }

    /* Estado al cargar */
    .sticky-navbar:not(.scrolled) {
      background-color: rgba(17, 17, 17, 1);
      /* #111 sólido */
      backdrop-filter: none;
      box-shadow: none;
    }

    .sticky-navbar.scrolled {
      background-color: rgba(17, 17, 17, 0.6);
      /* semitransparente */
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      /* Safari support */
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
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

    /* Navbar fija */
    .navbar {
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      --bs-bg-opacity: 0.1;
      padding: 0.5rem;
    }

    /* Contenedor con efecto Glass */
    .container {
      background: rgba(44, 44, 44, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 0.5rem 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      margin: 0 auto;
      margin-top: 0;
      padding-top: 30px;
      flex-grow: 1;
    }

    .respedido {
      background: rgba(44, 44, 44, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 0.5rem 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      margin: 0 auto;
      flex-grow: 1;
      padding-top: 50px;
    }

    /* Cards */
    .card {
      background-color: var(--gray-bg);
      border-radius: 16px;
      border: none;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
      background: rgba(26, 26, 26, 0.7);
      /* fondo semi-transparente */
      backdrop-filter: blur(8px)
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

    .btn-gold-circle {
      background-color: var(--main-gold);
      color: #000;
      font-size: 1.5rem;
      font-weight: bold;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      transition: all 0.3s ease;
    }

    .btn-gold-circle:hover {
      background-color: var(--gold-hover);
      transform: scale(1.1);
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark sticky-navbar bg-dark">
    <div class="container">
      <a class="navbar-brand" href="#"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <!-- Enlaces principales -->
          <li class="nav-item"><a class="nav-link" href="./index.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#menu">Menú</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#testimonio">Testimonio</a></li>

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="./index.php#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="./index.php#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="./index.php#contacto">Contacto</a></li>
              <li><a class="dropdown-item" href="./index.php#horario">Horarios</a></li>
            </ul>
          </li>

          <!-- Carrito -->
          <li class="nav-item">
            <a class="nav-link position-relative" href="carrito.php">
              <i class="fas fa-shopping-cart"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
                0
              </span>
            </a>
          </li>

          <!-- Sesión -->
          <?php if (isset($_SESSION["cliente"])): ?>
            <li class="nav-item">
              <a href="perfil_cliente.php" class="nav-link" title="<?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>">
                <i class="fas fa-user-circle"></i>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" title="Cerrar sesión" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="fas fa-sign-out-alt"></i>
              </a>

            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-2">
                Iniciar sesión / Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Modal de cierre de sesión -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-0 shadow-lg">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title fw-bold" id="logoutModalLabel">
            <i class="fas fa-sign-out-alt me-2 text-warning"></i> ¿Cerrar sesión?
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <p class="fs-5 mb-0">¿Estás seguro de que querés cerrar sesión?</p>
        </div>
        <div class="modal-footer justify-content-center border-top-0">
          <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
            <i></i> Quedarme
          </button>
          <a href="./logout_cliente.php" class="btn btn-danger px-4">
            <i class="fas fa-door-open me-1"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container contenido-ajustado">
    <div class="container">
      <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>
      <div id="carrito-contenido" class="row row-cols-2 row-cols-md-4 g-4"></div>
      <div id="btnAgregarMas" class="mt-4"></div>


      <div class="d-flex justify-content-center mt-4">
        <a href="./index.php#menu" class="btn btn-gold-circle" title="Agregar más">
          <i class="fas fa-plus"></i>
        </a>
      </div>


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
  </div>


  <footer class="bg-dark text-light text-center py-3 mt-5">
    <p>&copy; 2025 Piccolo Burgers — Developed by: <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong></p>
  </footer>

  <script>
    // Actualizar el contador del carrito al cargar la página
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

        } else { // Si ya existe, solo aumentamos la cantidad y sumamos el precio
          agrupado[key].cantidad++;
          agrupado[key].precio += item.precio;
        }
      });

      const productos = Object.values(agrupado);

      if (productos.length === 0) { // Si no hay productos, mostrar mensaje
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


      productos.forEach(item => { // Mostrar cada producto en el carrito
        total += item.precio;
        contenedor.innerHTML += `
  <div class="col d-flex" data-aos="fade-up">
    <div class="card position-relative d-flex flex-column h-100 w-100">
      <img src="${item.img}" class="card-img-top" alt="Foto de ${item.nombre}">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title">${item.nombre}</h5>
        <p class="card-text mb-1"><strong>Precio unitario:</strong> $${(item.precio / item.cantidad).toFixed(2)}</p>
        <p class="card-text mb-1"><strong>Cantidad:</strong> ${item.cantidad}</p>
        <p class="card-text"><strong>Subtotal:</strong> $${item.precio.toFixed(2)}</p>
        <div class="mt-auto d-flex flex-wrap gap-2">
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

    function aumentarCantidad(id) { // Aumentar la cantidad de un producto en el carrito
      let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      // Convertir el id a cadena para asegurar la comparación
      const producto = carrito.find(p => p.id.toString() === id.toString());
      if (producto) {
        carrito.push({
          ...producto
        });
        localStorage.setItem('carrito', JSON.stringify(carrito)); // agregamos una unidad más
        cargarCarrito();
        actualizarContador();
      }
    }


    function disminuirCantidad(id) { // Disminuir la cantidad de un producto en el carrito
      let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      const index = carrito.findIndex(p => p.id.toString() === id.toString());
      if (index !== -1) { // Si el producto existe en el carrito
        carrito.splice(index, 1); // removemos solo una unidad
        localStorage.setItem('carrito', JSON.stringify(carrito));
        cargarCarrito();
        actualizarContador();
      }
    }

    function eliminarProducto(id) { // Eliminar un producto del carrito
      let carrito = JSON.parse(localStorage.getItem('carrito')) || []; // Convertir el id a cadena para asegurar la comparación
      // Filtrar el carrito para eliminar el producto con el id especificado
      carrito = carrito.filter(p => p.id.toString() !== id.toString());
      localStorage.setItem('carrito', JSON.stringify(carrito));
      cargarCarrito();
      actualizarContador();
    }

    function vaciarCarrito() { // Vaciar el carrito completamente
      localStorage.removeItem("carrito");
      cargarCarrito();
      actualizarContador();
    }

    function actualizarContador() { // Actualizar el contador de productos en el carrito
      const items = JSON.parse(localStorage.getItem('carrito')) || [];
      const contador = document.getElementById("contador-carrito");
      if (contador) { // Si el contador existe, actualizamos su valor
        contador.textContent = items.length;
      }
    }

    function actualizarTotal() { // Actualizar el total del carrito y aplicar puntos si corresponde
      const usarPuntos = document.getElementById("usarPuntos")?.checked;
      localStorage.setItem("usar_puntos_activado", usarPuntos ? "1" : "0");

      const items = JSON.parse(localStorage.getItem('carrito')) || [];
      const totalSpan = document.getElementById("total");
      let total = items.reduce((acc, item) => acc + item.precio, 0);
      const puntosDisponibles = parseInt(document.getElementById("puntosDisponibles")?.value || 0);

      let descuento = 0;
      document.getElementById("puntos_usados")?.remove();
      document.getElementById("puntos_warning")?.remove();

      if (usarPuntos) { // Si se selecciona usar puntos, calculamos el descuento
        const valorPorPunto = 20;
        const minimoParaCanjear = 50;
        const maximoDescuento = total * 0.25;

        if (puntosDisponibles < minimoParaCanjear) { // Si no hay suficientes puntos para canjear
          totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_warning" class="text-danger">⚠️ Necesitás al menos ${minimoParaCanjear} puntos para canjear.</div>`);
        } else if (total < 1000) { // Si el total es menor a $1000, no se puede aplicar descuento
          totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_warning" class="text-danger">⚠️ El total debe ser al menos $1000 para canjear puntos.</div>`);
        } else { // Si hay suficientes puntos y el total es válido, calculamos el descuento
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

    // Cargar el carrito y actualizar el contador al cargar la página
    window.onload = () => {
      console.log("Contenido del carrito:", localStorage.getItem('carrito'));
      cargarCarrito();
      actualizarContador();
      document.getElementById("usarPuntos")?.addEventListener("change", actualizarTotal);
    };

    // Mostrar confirmación al cancelar el pedido
    function mostrarConfirmacionCancelar() {
      const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
      const detalleDiv = document.getElementById("detallePedidoModal");
      if (carrito.length === 0) { // Si el carrito está vacío, mostrar mensaje
        detalleDiv.innerHTML = "<p class='text-muted'>El carrito está vacío.</p>";
      } else { // Si hay productos, mostrar el resumen
        const resumen = carrito.reduce((acc, item) => {
          if (!acc[item.nombre]) { // Si no existe, lo inicializamos
            acc[item.nombre] = {
              cantidad: 1,
              precio: item.precio
            };
          } else { // Si ya existe, aumentamos la cantidad y sumamos el precio
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

      const modal = new bootstrap.Modal(document.getElementById("modalCancelarPedido")); // Crear una nueva instancia del modal
      modal.show();
    }

    function confirmarCancelacion() { // Confirmar la cancelación del pedido
      localStorage.removeItem("carrito"); // Limpiar el carrito
      const modal = bootstrap.Modal.getInstance(document.getElementById("modalCancelarPedido")); // Obtener la instancia del modal
      modal.hide();
      cargarCarrito();
      actualizarContador();
    }

    document.getElementById("formPedido").addEventListener("submit", function(e) { // Validar el formulario antes de enviar
      const usarPuntosChecked = document.getElementById("usarPuntos")?.checked;
      document.getElementById("usarPuntosInput").value = usarPuntosChecked ? "1" : "0";

      const items = JSON.parse(localStorage.getItem("carrito")) || [];
      const agrupado = {};

      items.forEach(item => { // Agrupar productos por ID para evitar duplicados
        const key = item.id;
        if (!agrupado[key]) {
          agrupado[key] = {
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            cantidad: 1
          };
        } else { // Si ya existe, solo aumentamos la cantidad y sumamos el precio
          agrupado[key].cantidad += 1;
          agrupado[key].precio += item.precio;
        }
      });


      const carritoFinal = Object.values(agrupado).map(p => ({ // Convertimos los datos a un formato adecuado para enviar
        id: String(p.id),
        nombre: p.nombre,
        precio: Number(p.precio),
        cantidad: Number(p.cantidad)
      }));

      const hayIncompletos = carritoFinal.some( // verificar si hay productos incompletos
        p => !p.id || !p.nombre || typeof p.precio !== "number" || !p.cantidad
      );

      if (hayIncompletos) { // Si hay productos incompletos, mostramos un mensaje de error
        console.error("⚠️ Hay productos incompletos:", carritoFinal);
        alert("Error: uno de los productos del carrito no tiene toda la información necesaria.");
        e.preventDefault();
        return;
      }

      console.log("✅ Carrito que se enviará:", carritoFinal);
      document.getElementById("carritoInput").value = JSON.stringify(carritoFinal);
    });
  </script>

  <!-- Modal para cancelar el pedido -->
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

  <!-- Modal de cierre de sesión -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-0 shadow-lg">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title fw-bold" id="logoutModalLabel">
            <i class="fas fa-sign-out-alt me-2 text-warning"></i> ¿Cerrar sesión?
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <p class="fs-5 mb-0">¿Estás seguro de que querés cerrar sesión?</p>
        </div>
        <div class="modal-footer justify-content-center border-top-0">
          <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
            <i></i> Quedarme
          </button>
          <a href="./logout_cliente.php" class="btn btn-danger px-4">
            <i class="fas fa-door-open me-1"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php include("componentes/whatsapp_button.php"); ?>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const navbar = document.querySelector(".navbar");
      const contenido = document.querySelector(".contenido-ajustado");

      if (navbar && contenido) {
        const alturaNavbar = navbar.getBoundingClientRect().height;
        contenido.style.paddingTop = alturaNavbar + 24 + "px"; // 24px extra para aire visual
      }
    });
  </script>

</body>

</html>