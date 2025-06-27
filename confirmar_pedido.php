<?php include("admin/bd.php"); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Confirmar Pedido - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
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
    .form-control {
      font-size: 1.2rem;
    }
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

  .modal-content {
    background-color: var(--gray-bg);
    color: var(--text-light);
  }

  .modal-body .list-group-item {
    background-color: transparent;
    color: var(--text-light);
    border-color: rgba(255, 255, 255, 0.1);
  }

  .form-control {
  background-color: var(--gray-bg);
  color: var(--text-light);
  border: 1px solid #444;
  font-size: 1.2rem;
  border-radius: 8px;
}

.form-control::placeholder {
  color: var(--text-muted);
}

.form-control:focus {
  background-color: var(--gray-bg);
  color: var(--text-light);
  border-color: var(--main-gold);
  box-shadow: 0 0 0 0.2rem rgba(250, 195, 12, 0.25);
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
    <label for="direccion" class="form-label">Dirección:</label>
    <input type="text" class="form-control" id="direccion" name="direccion">
    </div>


    <input type="hidden" name="carrito" id="carrito">
    <input type="hidden" name="usar_puntos" id="usar_puntos" value="0">
    <button type="submit" class="btn btn-gold w-100">Enviar Pedido</button>
  </form>

  <div id="mensaje" class="mt-4 text-center"></div>
</div>

<script>

document.getElementById("form-pedido").addEventListener("submit", async function(e) {
  e.preventDefault();

  const carritoSinAgrupar = JSON.parse(localStorage.getItem("carrito") || "[]");
  if (carritoSinAgrupar.length === 0) {
    alert("Tu carrito está vacío.");
    return;
  }

  // Agrupar productos por id
  const agrupado = {};
  carritoSinAgrupar.forEach(item => {
    const key = item.id;
    if (!agrupado[key]) {
      agrupado[key] = {
        id: item.id,
        nombre: item.nombre,
        precio: item.precio,
        cantidad: 1
      };
    } else {
      agrupado[key].cantidad++;
      agrupado[key].precio += item.precio;
    }
  });

  // Convertir a array limpio para enviar
  const carritoFinal = Object.values(agrupado).map(p => ({
    id: String(p.id),
    nombre: p.nombre,
    precio: Number(p.precio),
    cantidad: Number(p.cantidad)
  }));

  // Verificación (opcional)
  console.log("✅ Carrito final que se enviará:", carritoFinal);

  const form = e.target;
  const formData = new FormData(form);
  formData.set("carrito", JSON.stringify(carritoFinal));

  const usarPuntosCheckbox = localStorage.getItem("usar_puntos_activado") === "1";
  formData.set("usar_puntos", usarPuntosCheckbox ? "1" : "0");

  const response = await fetch("guardar_pedido.php", {
    method: "POST",
    body: formData
  });

  const texto = await response.text();
  console.log("Respuesta cruda del servidor:", texto);
  let resultado;
try {
  resultado = JSON.parse(texto);
} catch (error) {
  console.error("La respuesta no es JSON válido:", texto);
  document.getElementById("mensaje").innerHTML =
    '<div class="alert alert-danger">Ocurrió un error al procesar tu pedido. Intentalo de nuevo más tarde.</div>';
  return;
}



  if (resultado.exito) {
    // Construir modal dinámicamente
    const modalHtml = `
      <div class="modal fade" id="modalGracias" tabindex="-1" aria-labelledby="modalGraciasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title" id="modalGraciasLabel">¡Gracias por tu pedido!</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body fs-5">
              <p>🎉 <strong>${resultado.nombre}</strong>, tu pedido está en preparación. 🍔</p>
              ${parseFloat(resultado.descuento) > 0 ? `
                <p>Total original: $${resultado.total_original}<br>
                Descuento por puntos: -$${resultado.descuento}</p>` : ""}
              <p>Total a pagar: <strong>$${resultado.total}</strong></p>
              ${resultado.puntos_ganados > 0 ? `<p>🎁 Puntos ganados: <strong>${resultado.puntos_ganados}</strong></p>` : ""}
            </div>
            <div class="modal-footer">
              <a href="index.php" class="btn btn-gold">Volver al inicio</a>
            </div>
          </div>
        </div>
      </div>
    `;

    // Insertar modal al body
    let modalContainer = document.getElementById("modal-container");
    if (!modalContainer) {
      modalContainer = document.createElement("div");
      modalContainer.id = "modal-container";
      document.body.appendChild(modalContainer);
    }
    modalContainer.innerHTML = modalHtml;

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById("modalGracias"));
    modal.show();

    // Limpiar carrito y contador
    localStorage.removeItem("carrito");
    const contador = document.getElementById("contador-carrito");
    if (contador) contador.textContent = "0";

    form.reset();
    document.getElementById("mensaje").innerHTML = "";

  } else {
    // Mostrar error en div mensaje
    document.getElementById("mensaje").innerHTML = `<div class="alert alert-danger">${resultado.mensaje || "Error desconocido"}</div>`;
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

// Validar que solo se escriban números en el campo teléfono
document.getElementById("telefono").addEventListener("input", function (e) {
  this.value = this.value.replace(/[^0-9]/g, ""); // elimina todo lo que no sea número
});

// Validar que solo se escriban letras (y espacios) en el campo nombre
document.getElementById("nombre").addEventListener("input", function (e) {
  this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ""); // elimina números y caracteres especiales
});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
