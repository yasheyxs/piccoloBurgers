<?php include("admin/bd.php"); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Confirmar Pedido - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.7rem;
    }
    .form-control {
      font-size: 1.2rem;
    }
    .btn-gold {
      background-color: #fac30c;
      color: #000;
      font-weight: bold;
      border: none;
    }
    .btn-gold:hover {
      background-color: #e0ae00;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
    <a class="btn btn-gold ms-auto" href="carrito.php"><i class="fas fa-chevron-left"></i> Volver</a>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="mb-4 text-center">Confirmar Pedido</h2>

  <form id="form-pedido" method="post">
    <div class="mb-3">
      <label for="nombre" class="form-label">Nombre completo:</label>
      <input type="text" class="form-control" id="nombre" name="nombre" required>
    </div>
    <div class="mb-3">
      <label for="telefono" class="form-label">Teléfono (obligatorio):</label>
      <input type="text" class="form-control" id="telefono" name="telefono" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email (opcional):</label>
      <input type="email" class="form-control" id="email" name="email">
    </div>
    <div class="mb-3">
      <label for="nota" class="form-label">Nota para el pedido:</label>
      <textarea class="form-control" id="nota" name="nota" rows="3"></textarea>
    </div>

    <div class="mb-3">
    <label for="metodo_pago" class="form-label">Método de pago:</label>
    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
        <option value="">Seleccionar</option>
        <option value="Efectivo">Efectivo</option>
        <option value="Tarjeta">Tarjeta</option>
        <option value="MercadoPago">Mercado Pago</option>
    </select>
    </div>

    <div class="mb-3">
    <label for="tipo_entrega" class="form-label">Tipo de entrega:</label>
    <select class="form-control" id="tipo_entrega" name="tipo_entrega" required onchange="mostrarDireccion(this.value)">
        <option value="">Seleccionar</option>
        <option value="Retiro">Retiro en el local</option>
        <option value="Delivery">Delivery</option>
    </select>
    <div class="alert alert-warning" id="aviso-delivery" style="display: none; font-size: 1.2rem;">
        🚚 El servicio de delivery tiene un costo adicional de entre <strong>$1000</strong> y <strong>$1500</strong>, dependiendo de la zona.
    </div>

    </div>

    <div class="mb-3" id="grupo-direccion" style="display: none;">
    <label for="direccion" class="form-label">Dirección (solo delivery):</label>
    <input type="text" class="form-control" id="direccion" name="direccion">
    </div>


    <input type="hidden" name="carrito" id="carrito">
    <button type="submit" class="btn btn-gold w-100">Enviar Pedido</button>
  </form>

  <div id="mensaje" class="mt-4 text-center"></div>
</div>

<script>
document.getElementById("form-pedido").addEventListener("submit", async function(e) {
  e.preventDefault();
  
  const carrito = JSON.parse(localStorage.getItem("carrito") || "[]");
  if (carrito.length === 0) {
    alert("Tu carrito está vacío.");
    return;
  }

  const form = e.target;
  const formData = new FormData(form);
  formData.set("carrito", JSON.stringify(carrito));

  const response = await fetch("guardar_pedido.php", {
    method: "POST",
    body: formData
  });

  const resultado = await response.text();
  document.getElementById("mensaje").innerHTML = resultado;

  if (resultado.includes("Gracias")) {
    localStorage.removeItem("carrito");
    document.getElementById("contador-carrito").textContent = "0";
    form.reset();
  }
});

function mostrarDireccion(valor) {
  const grupoDireccion = document.getElementById("grupo-direccion");
  const aviso = document.getElementById("aviso-delivery");

  if (valor === "Delivery") {
    grupoDireccion.style.display = "block";
    aviso.style.display = "block";
    document.getElementById("direccion").setAttribute("required", "required");
  } else {
    grupoDireccion.style.display = "none";
    aviso.style.display = "none";
    document.getElementById("direccion").removeAttribute("required");
  }
}


</script>

</body>
</html>
