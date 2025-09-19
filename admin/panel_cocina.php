<?php
include("../admin/bd.php");
include("../admin/templates/header.php");

// Consultar pedidos "En preparación" y "En camino"
$sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado IN ('En preparación', 'En camino') ORDER BY fecha DESC");
$sentencia->execute();
$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  tr.en-camino {
    background-color: #fff3cd !important;
  }

  .camion-icono {
    font-size: 1.2rem;
    margin-right: 4px;
    display: inline-block;
  }
</style>

<br>
<div class="card">
  <div class="card-header bg-dark text-white">
    <h4 class="mb-0">Panel de Cocina - Pedidos en preparación</h4>
  </div>
  <div class="card-body">
    <?php if (count($pedidos) === 0): ?>
      <div class="alert alert-info">No hay pedidos en preparación por el momento.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle text-center w-100" id="tabla-pedidos">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Entrega</th>
              <th>Pago</th>
              <th>Dirección</th>
              <th>Productos</th>
              <th>Nota</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pedidos as $pedido): ?>
              <?php
              $stmt_detalle = $conexion->prepare("SELECT nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
              $stmt_detalle->execute([$pedido['ID']]);
              $productos = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
              $metodoPago = trim((string)($pedido['metodo_pago'] ?? ''));

              ?>
              <tr data-pedido-id="<?= $pedido['ID'] ?>" class="<?= $pedido['estado'] === 'En camino' ? 'en-camino' : '' ?>">
                <td><?= $pedido['ID'] ?></td>
                <td><?= htmlspecialchars($pedido['nombre']) ?></td>
                <td><?= htmlspecialchars($pedido['tipo_entrega']) ?></td>

                <td><?= $metodoPago !== '' ? htmlspecialchars($metodoPago) : '-' ?></td>


                <td>
                  <?= $pedido['tipo_entrega'] === 'Delivery' && !empty($pedido['direccion'])
                    ? htmlspecialchars($pedido['direccion'])
                    : '-' ?>
                </td>
                <td>
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($productos as $producto): ?>
                      <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= htmlspecialchars($pedido['nota']) ?: '-' ?></td>
                <td>
  <div class="d-flex flex-wrap justify-content-center gap-1 align-items-center">
    <?php if ($pedido['estado'] === 'En camino' && $pedido['tipo_entrega'] === 'Delivery'): ?>
      <span class="camion-icono">🚚</span>
    <?php endif; ?>
    <button class="btn btn-success btn-sm btn-estado" data-estado="Listo" data-id="<?= $pedido['ID'] ?>">Listo</button>
    <?php if ($pedido['estado'] !== 'En camino' && $pedido['tipo_entrega'] === 'Delivery'): ?>
      <button class="btn btn-warning btn-sm btn-estado" data-estado="En camino" data-id="<?= $pedido['ID'] ?>">En camino</button>
    <?php endif; ?>
    <button class="btn btn-danger btn-sm btn-estado" data-estado="Cancelado" data-id="<?= $pedido['ID'] ?>">Cancelar</button>
  </div>
</td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDataTable('#tabla-pedidos');
  });

  async function actualizarEstado(pedidoId, nuevoEstado, boton) {
    try {
      const formData = new FormData();
      formData.append('pedido_id', pedidoId);
      formData.append('nuevo_estado', nuevoEstado);

      const response = await fetch('../admin/actualizar_estado.php', {
        method: 'POST',
        body: formData
      });
      const resultado = await response.json();

      if (resultado.success) {
        alert(`Pedido #${pedidoId} actualizado a "${nuevoEstado}" correctamente.`);
        const fila = boton.closest('tr');

        if (nuevoEstado === 'En camino') {
          fila.classList.add('en-camino');

          const btnEnCamino = fila.querySelector('button[data-estado="En camino"]');
          if (btnEnCamino) btnEnCamino.remove();

        } else {
          fila.remove();
        }

        // Insertar ícono 🚚 en la columna de acciones
const celdaAcciones = fila.cells[6];
if (celdaAcciones && !celdaAcciones.querySelector('.camion-icono')) {
  const icono = document.createElement('span');
  icono.className = 'camion-icono';
  icono.textContent = '🚚';
  const contenedorBotones = celdaAcciones.querySelector('.d-flex');
if (contenedorBotones) {
  contenedorBotones.insertBefore(icono, contenedorBotones.firstChild);

}

}


        const tbody = document.querySelector('#tabla-pedidos tbody');
        if (tbody.children.length === 0) {
          const contenedor = document.querySelector('.card-body');
          contenedor.innerHTML = '<div class="alert alert-info">No hay pedidos en preparación por el momento.</div>';
        }
      } else {
        alert('Error: ' + resultado.message);
      }
    } catch (error) {
      alert('Error al conectar con el servidor.');
      console.error(error);
    }
  }

  document.querySelectorAll('.btn-estado').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedidoId = btn.getAttribute('data-id');
      const nuevoEstado = btn.getAttribute('data-estado');

      if (nuevoEstado === 'Cancelado') {
        if (!confirm('¿Estás seguro de cancelar este pedido? Esto notificará al cliente.')) return;
      }

      actualizarEstado(pedidoId, nuevoEstado, btn);
    });
  });

  setInterval(() => location.reload(), 10000);
</script>

<?php include("../admin/templates/footer.php"); ?>
