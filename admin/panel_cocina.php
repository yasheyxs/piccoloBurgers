<?php
include("../admin/bd.php");
include("../admin/templates/header.php");

// Consultar pedidos "En preparación"
$sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En preparación' ORDER BY fecha DESC");
$sentencia->execute();// Obtener todos los pedidos en preparación
$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<br>
<div class="card">
  <div class="card-header bg-dark text-white">
    <h4 class="mb-0">Panel de Cocina - Pedidos en preparación</h4>
  </div>
  <div class="card-body">
    <?php if (count($pedidos) === 0): ?>// Si no hay pedidos en preparación, mostrar mensaje
      <div class="alert alert-info">No hay pedidos en preparación por el momento.</div>
    <?php else: ?>// Si hay pedidos, mostrar la tabla
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center" id="tabla-pedidos">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Entrega</th>
              <th><strong>Dirección</strong></th>
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
              ?>// Si no hay productos, mostrar mensaje
              <tr data-pedido-id="<?= $pedido['ID'] ?>">
                <td><?= $pedido['ID'] ?></td>
                <td><?= htmlspecialchars($pedido['nombre']) ?></td>
                <td><?= htmlspecialchars($pedido['tipo_entrega']) ?></td>
                <td>
                  <?= $pedido['tipo_entrega'] === 'Delivery' && !empty($pedido['direccion'])
                    ? htmlspecialchars($pedido['direccion'])
                    : '-' ?>
                </td>

                <td>
                  <ul class="list-unstyled">
                    <?php foreach ($productos as $producto): ?>
                      <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= htmlspecialchars($pedido['nota']) ?: '-' ?></td>
                <td>
                  <button class="btn btn-success btn-sm btn-estado" data-estado="Listo" data-id="<?= $pedido['ID'] ?>">Listo</button>
                  <button class="btn btn-danger btn-sm btn-estado" data-estado="Cancelado" data-id="<?= $pedido['ID'] ?>">Cancelar</button>
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
  // Función para actualizar el estado del pedido
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

      if (resultado.success) {// Si la actualización fue exitosa, mostrar mensaje
        alert(`Pedido #${pedidoId} actualizado a "${nuevoEstado}" correctamente.`);

        // Remover la fila del pedido actualizado porque ya no debe verse en "En preparación"
        const fila = boton.closest('tr');
        fila.remove();

        // Si la tabla quedó vacía, mostrar mensaje
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

  // Asignar evento a los botones de estado
  document.querySelectorAll('.btn-estado').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedidoId = btn.getAttribute('data-id');
      const nuevoEstado = btn.getAttribute('data-estado');

      if (nuevoEstado === 'Cancelado') {// Si el estado es "Cancelado", preguntar confirmación
        if (!confirm('¿Estás seguro de cancelar este pedido? Esto notificará al cliente.')) return;
      }

      actualizarEstado(pedidoId, nuevoEstado, btn);// Llamar a la función para actualizar el estado
    });
  });

  // Refrescar automáticamente cada 10 segundos
  setInterval(() => location.reload(), 10000);
</script>

<?php include("../admin/templates/footer.php"); ?>