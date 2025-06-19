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
    <button class="btn btn-danger me-2" onclick="vaciarCarrito()">Vaciar Carrito</button>
    <a href="confirmar_pedido.php" class="btn btn-gold">🧾 Finalizar Pedido</a>
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


window.onload = () => {
  console.log("Contenido del carrito:", localStorage.getItem('carrito'));
  cargarCarrito();
  actualizarContador();
};

</script>

</body>
</html>
