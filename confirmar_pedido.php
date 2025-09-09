<?php include("admin/bd.php"); ?>
<?php
session_start();
$cliente = isset($_SESSION['cliente']) ? $_SESSION['cliente'] : null;
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirmar Pedido - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

    /* Estilo global */
    html,
    body {
      height: 100%;
      /* Asegura que el body ocupe toda la altura */
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

    body {
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-size: 1rem;
      line-height: 1.6;

    }

    /* Navbar fija */
    .navbar {
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      --bs-bg-opacity: 0.1;
      padding: 0.5rem 1rem;
    }

    /* Contenedor con efecto Glass */
    .container {
      background: rgba(44, 44, 44, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 3rem 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      max-width: 600px;
      margin: 0 auto;
      flex-grow: 1;
      margin-top: 0;
    }

    .navbar>.container,
    .navbar>.container-fluid,
    .navbar>.container-lg,
    .navbar>.container-md,
    .navbar>.container-sm,
    .navbar>.container-xl,
    .navbar>.container-xxl {
      display: flex;
      flex-wrap: inherit;
      align-items: center;
      justify-content: space-between;
      padding: 0.5rem 1rem;
    }

    h2 {
      font-size: 2.5rem;
      color: var(--main-gold);
      text-align: center;
    }

    .form-control {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.2rem;
      border-radius: 8px;
    }

    .form-control:focus {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border-color: var(--main-gold);
      box-shadow: 0 0 0 0.2rem rgba(250, 195, 12, 0.25);
    }

    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 140 140' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M35 50l35 40 35-40' stroke='%23ccc' stroke-width='15' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 1rem;
      padding-right: 2.5rem;
      cursor: pointer;
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

    /* Contenedor con efecto glass */
    .container {
      background: rgba(44, 44, 44, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 3rem 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      max-width: 600px;
      margin: 0 auto;
      flex-grow: 1;
      /* Garantiza que este contenido crezca */
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
      background-color: #1e1e1e;
      color: #fff;
      border: 1px solid #444;
      border-radius: 0.75rem;
      font-size: 1.1rem;
      padding: 0.75rem 1rem;
      transition: all 0.3s ease;
    }

    .form-control::placeholder {
      color: var(--text-muted);
    }


    .form-control:focus {
      border-color: #fac30c;
      box-shadow: 0 0 8px rgba(250, 195, 12, 0.4);
      background-color: #222;
      color: #fff;
    }

    .form-label {
      font-weight: bold;
      color: #fac30c;
      font-size: 1.05rem;
      margin-bottom: 0.4rem;
    }

    .radio-card {
      display: inline-block;
      border: 2px solid transparent;
      border-radius: 1rem;
      padding: 0.5rem 0.5rem;
      margin-right: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      background-color: #1a1a1a;
      color: #fff;
    }

    .radio-card:hover {
      border-color: #999;
    }

    .form-check-input:checked+.radio-card {
      border-color: #fac30c;
      background-color: #2a2a2a;
      color: #fac30c;
      box-shadow: 0 0 12px rgba(250, 195, 12, 0.4);
      font-weight: bold;
    }

    .form-check-input {
      display: none;
    }

    
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> PICCOLO BURGERS</a>
      <a class="btn btn-gold ms-auto" href="carrito.php"><i class="fas fa-chevron-left"></i> Volver</a>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4 text-center">Confirmar Pedido</h2>

    <form id="form-pedido" method="post">
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre completo:</label>
        <input type="text" class="form-control" id="nombre" name="nombre" required
 value="<?php echo htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono (obligatorio):</label>
        <input type="text" class="form-control" id="telefono" name="telefono" required
 value="<?php echo htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control" id="email" name="email"
 value="<?php echo htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="nota" class="form-label">Nota para el pedido:</label>
        <textarea class="form-control" id="nota" name="nota" rows="3"></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Método de pago:</label>

        <input type="radio" class="form-check-input" id="pago_efectivo" name="metodo_pago" value="Efectivo" required>
        <label class="radio-card" for="pago_efectivo">💵 Efectivo</label>

        <input type="radio" class="form-check-input" id="pago_tarjeta" name="metodo_pago" value="Tarjeta">
        <label class="radio-card" for="pago_tarjeta">💳 Tarjeta</label>

        <input type="radio" class="form-check-input" id="pago_mp" name="metodo_pago" value="MercadoPago">
        <label class="radio-card" for="pago_mp">📱 Mercado Pago</label>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Tipo de entrega:</label>

        <input type="radio" class="form-check-input" id="entrega_retiro" name="tipo_entrega" value="Retiro" required onchange="mostrarDireccion(this.value)">
        <label class="radio-card" for="entrega_retiro">🏪 Retiro en el local</label>

        <input type="radio" class="form-check-input" id="entrega_delivery" name="tipo_entrega" value="Delivery" onchange="mostrarDireccion(this.value)">
        <label class="radio-card" for="entrega_delivery">🏍️ Delivery</label>

        <div class="alert alert-warning mt-3" id="aviso-delivery" style="display: none; font-size: 1rem;">
          🚨 El servicio de delivery tiene un costo adicional de entre <strong>$1000</strong> y <strong>$1500</strong>, dependiendo de la zona.
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
    // Manejar el envío del formulario
    // Validar que el carrito no esté vacío y agrupar productos por id
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
        } else {// Si ya existe, sumar cantidad y precio
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

      // Validar que el carrito final no esté vacío
      console.log("✅ Carrito final que se enviará:", carritoFinal);

      const form = e.target;
      const formData = new FormData(form);
      formData.set("carrito", JSON.stringify(carritoFinal));

      const usarPuntosCheckbox = localStorage.getItem("usar_puntos_activado") === "1";
      formData.set("usar_puntos", usarPuntosCheckbox ? "1" : "0");

      const response = await fetch("guardar_pedido.php", {
        method: "POST",
        body: formData
      });// Enviar datos del formulario al servidor

      const texto = await response.text();
      console.log("Respuesta cruda del servidor:", texto);
      let resultado;
      try {// Intentar parsear la respuesta como JSON
        resultado = JSON.parse(texto);
      } catch (error) {// Si falla, mostrar error
        console.error("La respuesta no es JSON válido:", texto);
        document.getElementById("mensaje").innerHTML =
          '<div class="alert alert-danger">Ocurrió un error al procesar tu pedido. Intentalo de nuevo más tarde.</div>';
        return;// salir si no es JSON
      }

      if (resultado.exito) {
        // Construir modal dinámicamente
        let mensajePago = "";
if (formData.get("metodo_pago") === "MercadoPago") {
  const esDelivery = formData.get("tipo_entrega") === "Delivery";
  mensajePago = `
  <div class="p-4 mt-4 rounded" style="background-color: var(--gray-bg); border: 1px solid rgba(255, 255, 255, 0.1);">
    <h5 class="mb-3" style="font-size: 1.6rem; color: var(--main-gold);">
      📲 Pagá por Mercado Pago
    </h5>
    <p class="mb-2" style="font-size: 1.1rem;"><strong>Alias:</strong> piccolovdr</p>
    <p class="mb-2" style="font-size: 1.1rem;"><strong>Nombre del titular:</strong> Mario Alberto Gaido</p>
    <p class="mb-2" style="font-size: 1.1rem;">
      Enviá el comprobante por WhatsApp a:
      <a href="https://wa.me/5493573438947" target="_blank" style="color: var(--main-gold); text-decoration: underline;">+54 9 3573 438947</a>
    </p>
    ${esDelivery ? `
      <p class="mt-3" style="color: var(--main-gold); font-size: 1.05rem;">
        💸 El costo del delivery varía entre <strong>$1000</strong> y <strong>$1500</strong> según la zona.
        Envianos un mensaje para confirmar el monto.
      </p>
    ` : ""}
  </div>
`;
}

const modalHtml = `
  <div class="modal fade" id="modalGracias" tabindex="-1" aria-labelledby="modalGraciasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content glass-card border-0 shadow-lg" style="border-radius: 20px; background: rgba(30,30,30,0.85); color: #f8f9fa;">
        <div class="modal-header border-0 px-4 py-3">
          <h5 class="modal-title fw-bold d-flex align-items-center" id="modalGraciasLabel">
            <i class="fas fa-check-circle text-success me-2 fa-lg"></i>
            ¡Gracias por tu pedido!
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body fs-5 px-4 py-3">
          <p>🎉 <strong>${resultado.nombre}</strong>, tu pedido está en preparación. 🍔</p>
          ${parseFloat(resultado.descuento) > 0 ? `
            <div class="mb-2">
              <p class="mb-0">💸 <strong>Total original:</strong> $${resultado.total_original}</p>
              <p class="mb-0">🔻 <strong>Descuento por puntos:</strong> -$${resultado.descuento}</p>
            </div>` : ""}
          <p class="mt-3">💰 <strong>Total a pagar:</strong> $${resultado.total}</p>
          ${resultado.puntos_ganados > 0 ? `<p>🎁 <strong>Puntos ganados:</strong> ${resultado.puntos_ganados}</p>` : ""}
          ${mensajePago}
        </div>
        <div class="modal-footer border-0 px-4 py-3 justify-content-center">
          <a href="index.php" class="btn btn-gold px-4">Volver al inicio</a>
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
document.getElementById("mensaje").innerHTML = `
  <div id="mensaje-error" class="alert alert-danger">
    ${resultado.mensaje || "Error desconocido"}
  </div>
`;
if (resultado.scroll) {
  setTimeout(() => {
    const errorDiv = document.getElementById("mensaje-error");
    if (errorDiv) {
      errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  }, 100);
}

      }
    });

    // Mostrar/ocultar campo dirección según tipo de entrega
    function mostrarDireccion(valor) {
      const grupoDireccion = document.getElementById("grupo-direccion");
      const aviso = document.getElementById("aviso-delivery");

      if (valor === "Delivery") {// Si es Delivery, mostrar campo dirección y aviso
        grupoDireccion.style.display = "block";
        aviso.style.display = "block";
        document.getElementById("direccion").setAttribute("required", "required");
      } else {// Si es Retiro, ocultar campo dirección y aviso
        grupoDireccion.style.display = "none";
        aviso.style.display = "none";
        document.getElementById("direccion").removeAttribute("required");
      }
    }

    // Validar que solo se escriban números en el campo teléfono
    document.getElementById("telefono").addEventListener("input", function(e) {
      this.value = this.value.replace(/[^0-9]/g, ""); // elimina todo lo que no sea número
    });

    // Validar que solo se escriban letras y espacios en el campo nombre
    document.getElementById("nombre").addEventListener("input", function(e) {
      this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ""); // elimina números y caracteres especiales
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>fetch("ruta-del-endpoint", { method: "POST", body: datos })
  .then(res => res.json())
  .then(data => {
    if (!data.exito) {
      const errorDiv = document.getElementById("mensaje-error");
      errorDiv.innerText = data.mensaje;
      errorDiv.style.display = "block";
      if (data.scroll) {
        setTimeout(() => {
          errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 100);
      }
    }
  });
</script>
  <?php include("componentes/whatsapp_button.php"); ?>

  
</body>

</html>