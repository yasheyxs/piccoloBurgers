<?php include("admin/bd.php"); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Carrito - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.7rem;
    }
    .btn-gold {
      background-color: #fac30c;
      color: #000;
      font-weight: bold;
      border: none;
    }
    .btn-gold:hover {
      background-color: #e0ae00;
      color: #000;
    }
    .card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
      border-radius: 12px;
    }
    .card:hover {
      transform: scale(1.03);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }
    .card-img-top {
      height: 200px;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .card:hover .card-img-top {
      transform: scale(1.05);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
    <ul class="navbar-nav ms-auto">
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
</nav>

<div class="container mt-5">
  <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>
  <div id="carrito-contenido" class="row"></div>

  <div class="text-end mt-4">
    <h4>Total: $<span id="total">0.00</span></h4>

    <?php
      session_start();
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



    <button class="btn btn-danger me-2" onclick="vaciarCarrito()">Vaciar Carrito</button>
    <form id="formPedido" action="confirmar_pedido.php" method="post">
      <input type="hidden" name="carrito" id="carritoInput">
      <input type="hidden" name="usar_puntos" id="usarPuntosInput" value="0">

      <button type="submit" class="btn btn-gold">🧾 Finalizar Pedido</button>
    </form>

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
      agrupado[key] = { ...item, cantidad: 1 };
    } else {
      agrupado[key].cantidad++;
      agrupado[key].precio += item.precio;
    }
  });

  const productos = Object.values(agrupado);

  if (productos.length === 0) {
    contenedor.innerHTML = "<p class='text-center'>Tu carrito está vacío.</p>";
    totalSpan.textContent = "0.00";
    return;
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
    carrito.push({ ...producto });
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
  carrito = carrito.filter(p => p.id !== id); // removemos todas las unidades del producto
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

document.getElementById("formPedido").addEventListener("submit", function (e) {
  const usarPuntosChecked = document.getElementById("usarPuntos")?.checked;
  document.getElementById("usarPuntosInput").value = usarPuntosChecked ? "1" : "0";

  const carrito = localStorage.getItem("carrito");
  document.getElementById("carritoInput").value = carrito;
});

</script>

</body>
</html>
