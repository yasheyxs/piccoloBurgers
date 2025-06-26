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
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-user"></i> Volver al inicio</a>
    <a class="btn btn-gold ms-auto" href="logout_cliente.php">Cerrar Sesión</a>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="mb-4 text-center">👤 Información del Cliente</h2>
  <div class="card p-4 shadow">
    <p><strong>Nombre:</strong> <?= htmlspecialchars($datos["nombre"]) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($datos["telefono"]) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($datos["email"]) ?: "No registrado" ?></p>
    <p><strong>Fecha de Registro:</strong> <?= date("d/m/Y", strtotime($datos["fecha_registro"])) ?></p>
    <p><strong>Puntos disponibles:</strong> <?= $datos["puntos"] ?> ⭐</p>
  </div>

  <div id="historial-pedidos">
    <h2 class="mt-5 mb-4 text-center">📜 Historial de Pedidos</h2>
  </div>
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
