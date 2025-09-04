<?php
include("../admin/bd.php");
include("../admin/templates/header.php");

$sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En camino' ORDER BY fecha DESC");
$sentencia->execute();
$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  body {
    background-color: #1a1a1a;
    color: #fff;
    font-family: 'Inter', sans-serif;
  }

  .card-pedido {
    background-color: #2c2c2c;
    border-left: 5px solid #fac30c;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(250, 195, 12, 0.2);
  }

  .card-pedido h5 {
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #fac30c;
  }

  .card-pedido p,
  .card-pedido li {
    font-size: 1rem;
    margin-bottom: 0.3rem;
    color: #ddd;
  }

  .btn-entregado {
    background-color: #fac30c;
    color: #000;
    font-weight: bold;
    border-radius: 30px;
    padding: 0.6rem 1.2rem;
    font-size: 1rem;
    width: 100%;
    margin-top: 0.5rem;
  }

  .btn-entregado:hover {
    background-color: #e0ae00;
    transform: scale(1.03);
  }

  .alert-info {
    background-color: #2c2c2c;
    color: #fff;
    border: 1px solid #fac30c;
    text-align: center;
    padding: 1rem;
    border-radius: 12px;
  }
</style>

<div class="container mt-4">
  <h3 class="text-center mb-4">🚚 Pedidos en camino</h3>

  <?php if (count($pedidos) === 0): ?>
    <div class="alert alert-info">No hay pedidos en camino por el momento.</div>
  <?php else: ?>
    <?php foreach ($pedidos as $pedido): ?>
      http://localhost/phpmyadmin/index.php?route=/database/structure&db=piccolodb<?php
      $stmt_detalle = $conexion->prepare("SELECT nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
      $stmt_detalle->execute([$pedido['ID']]);
      $productos = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="card-pedido" data-pedido-id="<?= $pedido['ID'] ?>">
        <h5>#<?= $pedido['ID'] ?> - <?= htmlspecialchars($pedido['nombre']) ?></h5>
        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($pedido['telefono']) ?></p>
        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($pedido['direccion']) ?></p>
        <p><strong>🛍️ Productos:</strong></p>
        <ul class="mb-2">
          <?php foreach ($productos as $producto): ?>
            <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
          <?php endforeach; ?>
        </ul>
        <p><strong>📝 Nota:</strong> <?= htmlspecialchars($pedido['nota']) ?: 'Sin nota' ?></p>
        <button class="btn btn-entregado" data-id="<?= $pedido['ID'] ?>">Entregado</button>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  async function marcarEntregado(pedidoId, boton) {
    try {
      const formData = new FormData();
      formData.append('pedido_id', pedidoId);
      formData.append('nuevo_estado', 'Listo');

      const response = await fetch('../admin/actualizar_estado.php', {
        method: 'POST',
        body: formData
      });

      const resultado = await response.json();
      if (resultado.success) {
        boton.closest('.card-pedido').remove();
      } else {
        alert('Error: ' + resultado.message);
      }
    } catch (error) {
      alert('Error al conectar con el servidor.');
      console.error(error);
    }
  }

  document.querySelectorAll('.btn-entregado').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedidoId = btn.getAttribute('data-id');
      marcarEntregado(pedidoId, btn);
    });
  });

  setInterval(() => location.reload(), 10000);
</script>

<?php include("../admin/templates/footer.php"); ?>
