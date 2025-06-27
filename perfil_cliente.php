<?php
include("admin/bd.php");
session_start();

if (!isset($_SESSION["cliente"])) {
    header("Location: login_cliente.php");
    exit;
}

$cliente = $_SESSION["cliente"];
$cliente_id = $cliente["id"];

// Obtener datos actualizados del cliente
$stmt = $conexion->prepare("SELECT nombre, telefono, email, fecha_registro, puntos FROM tbl_clientes WHERE ID = ?");
$stmt->execute([$cliente_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mi Perfil - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

  
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

.navbar-brand {
  font-family: var(--font-title);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-size: 1.5rem;
}

.navbar {
  background-color: #111;
}

.navbar-brand, .nav-link {
  font-family: var(--font-main);
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

  .card {
    background-color: var(--gray-bg);
    border-radius: 16px;
    border: none;
    box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
  }

  .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.4);
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
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    border-left: 4px solid var(--main-gold);
    padding-left: 12px;
  }

  .card-footer::before {
    content: "👤 ";
  }

  .card p,
  .card li,
  .card span,
  .card strong {
    color: var(--text-light);
  }

  .card-info {
  background-color: #343434; /* más claro que --gray-bg */
  border-left: 5px solid var(--main-gold);
  position: relative;
  padding-left: 1.5rem !important;
  box-shadow: 0 8px 20px rgba(250, 195, 12, 0.2);
  padding-top: 2.5rem;
}

.card-info::before {
  content: "👤";
  position: absolute;
  top: -0.8rem;
  left: -0.4rem;
  font-size: 4rem;
  color: var(--main-gold);
  filter: drop-shadow(0 0 4px rgba(250, 195, 12, 0.3));
  opacity: 0.1;
}

.card-info strong {
  color: var(--main-gold);
}

.card-info p:first-child strong {
  font-size: 1.2rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

  .alert-info {
  background-color: #2c2c2c;
  color: var(--text-light);
  border: 1px solid var(--gold-hover);
}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPerfil">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarPerfil">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a href="perfil_cliente.php" class="nav-link"><i class="fas fa-user"></i> Mi Perfil</a>
        </li>
        <li class="nav-item">
          <a href="logout_cliente.php" class="btn btn-gold ms-3">Cerrar sesión</a>
        </li>
      </ul>
    </div>
  </div>
</nav>


<div class="container mt-5">
  <h2 class="mb-4 text-center">👤 Información del Cliente</h2>
  <div class="card card-info p-4 mb-5">

    <p><strong>Nombre:</strong> <?= htmlspecialchars($datos["nombre"]) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($datos["telefono"]) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($datos["email"]) ?: "No registrado" ?></p>
    <p><strong>Fecha de Registro:</strong> <?= date("d/m/Y", strtotime($datos["fecha_registro"])) ?></p>
    <p><strong>Puntos disponibles:</strong> <?= $datos["puntos"] ?> ⭐</p>
  </div>

  <h2 class="mt-5 mb-4 text-center">📜 Historial de Pedidos</h2>
  <div id="historial-pedidos"></div>

</div>

<script>
  async function actualizarHistorial() {
    try {
      const response = await fetch('admin/obtener_pedidos_cliente.php'); // Endpoint que devuelve los pedidos JSON
      const pedidos = await response.json();

      const contenedor = document.querySelector('.container.mt-5'); // o algún div que contenga el historial

      let htmlPedidos = '';

      if (pedidos.length === 0) {
        htmlPedidos = `<div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>`;
      } else {
        pedidos.forEach(pedido => {
          let estadoHtml = '';
          switch(pedido.estado) {
            case 'Cancelado':
              estadoHtml = `<span class="text-danger">Cancelado ❌ — Lamentamos que tu pedido haya sido cancelado. Esperamos servirte mejor la próxima vez</span>`;
              break;
            case 'Listo':
              estadoHtml = `<span class="text-success">Listo ✅</span>`;
              break;
            case 'En preparación':
              estadoHtml = `<span class="text-warning">En preparación ⏳</span>`;
              break;
            default:
              estadoHtml = pedido.estado;
          }

          let productosHtml = '<ul>';
          pedido.detalles.forEach(detalle => {
            productosHtml += `<li>${detalle.nombre} - $${Number(detalle.precio).toFixed(2)} x ${detalle.cantidad}</li>`;
          });

          productosHtml += '</ul>';

          htmlPedidos += `
            <div class="card mb-4 shadow p-3">
              <p><strong>Fecha:</strong> ${new Date(pedido.fecha).toLocaleString()}</p>
              <p><strong>Total:</strong> $${Number(pedido.total).toFixed(2)}</p>
              <p><strong>Entrega:</strong> ${pedido.tipo_entrega}</p>
              <p><strong>Método de pago:</strong> ${pedido.metodo_pago}</p>
              <p><strong>Estado:</strong> ${estadoHtml}</p>
              <p><strong>Nota:</strong> ${pedido.nota.replace(/\n/g, '<br>')}</p>
              <strong>Productos:</strong>
              ${productosHtml}
            </div>
          `;
        });
      }

      document.getElementById('historial-pedidos').innerHTML = htmlPedidos;
    } catch (e) {
      console.error('Error al actualizar historial:', e);
    }
  }

  // Actualizar cada 10 segundos
  setInterval(actualizarHistorial, 10000);

  // Ejecutar al cargar
  document.addEventListener('DOMContentLoaded', actualizarHistorial);
</script>


</body>
</html>
