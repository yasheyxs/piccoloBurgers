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

// Obtener historial de pedidos del cliente
$stmt = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE telefono = ? ORDER BY fecha DESC");
$stmt->execute([$datos["telefono"]]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

  <h2 class="mt-5 mb-4 text-center">📜 Historial de Pedidos</h2>

<?php if (count($pedidos) > 0): ?>
  <?php foreach ($pedidos as $pedido): ?>
    <div class="card mb-4 shadow p-3">
      <p><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($pedido["fecha"])) ?></p>
      <p><strong>Total:</strong> $<?= number_format($pedido["total"], 2) ?></p>
      <p><strong>Entrega:</strong> <?= htmlspecialchars($pedido["tipo_entrega"]) ?></p>
      <p><strong>Método de pago:</strong> <?= htmlspecialchars($pedido["metodo_pago"]) ?></p>
      <p><strong>Estado:</strong> 
            <?php if ($pedido["estado"] === "Cancelado"): ?>
                <span class="text-danger">Cancelado ❌ — Lamentamos que tu pedido haya sido cancelado. Esperamos servirte mejor la próxima vez</span>
            <?php elseif ($pedido["estado"] === "Listo"): ?>
                <span class="text-success">Listo ✅</span>
            <?php elseif ($pedido["estado"] === "En preparación"): ?>
                <span class="text-warning">En preparación ⏳</span>
            <?php else: ?>
                <?= htmlspecialchars($pedido["estado"]) ?>
            <?php endif; ?>
        </p>

      <p><strong>Nota:</strong> <?= nl2br(htmlspecialchars($pedido["nota"])) ?></p>
      <strong>Productos:</strong>
      <ul>
        <?php
        $stmt = $conexion->prepare("SELECT nombre, precio, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
        $stmt->execute([$pedido["ID"]]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($detalles as $detalle):
        ?>
          <li><?= htmlspecialchars($detalle["nombre"]) ?> - $<?= number_format($detalle["precio"], 2) ?> x <?= $detalle["cantidad"] ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>
<?php endif; ?>

</div>

</body>
</html>
