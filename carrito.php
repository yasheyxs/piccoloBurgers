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

  if (items.length === 0) {
    contenedor.innerHTML = "<p class='text-center'>Tu carrito está vacío.</p>";
    totalSpan.textContent = "0.00";
    return;
  }

  items.forEach(item => {
    total += item.precio;
    contenedor.innerHTML += `
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
          <img src="${item.img}" class="card-img-top" alt="${item.nombre}">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${item.nombre}</h5>
            <p class="card-text"><strong>Precio:</strong> $${item.precio.toFixed(2)}</p>
          </div>
        </div>
      </div>`;
  });

  totalSpan.textContent = total.toFixed(2);
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

  if (usarPuntos && puntosDisponibles > 0) {
    const valorPorPunto = 20;
    const maximoDescuento = total * 0.25;
    const puntosPosibles = Math.floor(maximoDescuento / valorPorPunto);
    const puntosAUsar = Math.min(puntosDisponibles, puntosPosibles);

    descuento = puntosAUsar * valorPorPunto;
    document.getElementById("puntos_usados")?.remove();
    totalSpan.insertAdjacentHTML("afterend", `<div id="puntos_usados" class="text-success">- $${descuento.toFixed(2)} aplicados en puntos</div>`);
  } else {
    document.getElementById("puntos_usados")?.remove();
  }

  totalSpan.textContent = (total - descuento).toFixed(2);
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
