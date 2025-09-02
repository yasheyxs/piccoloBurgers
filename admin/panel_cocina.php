<?php
include("../admin/bd.php");
include("../admin/templates/header.php");

// Consultar pedidos "En preparación"
$sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En preparación' ORDER BY fecha DESC");
$sentencia->execute();
$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  @media (max-width: 576px) {
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      width: 100% !important;
    }

    .dataTables_wrapper .dataTables_paginate {
      flex-wrap: wrap;
      justify-content: center;
    }

    .dataTables_length label {
      font-weight: 500;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
  }

  @media (min-width: 576px) {
    .dataTables_length label {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 0.5rem;
    }
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
              ?>
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
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($productos as $producto): ?>
                      <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= htmlspecialchars($pedido['nota']) ?: '-' ?></td>
                <td>
                  <div class="d-flex flex-wrap justify-content-center gap-1">
                    <button class="btn btn-success btn-sm btn-estado" data-estado="Listo" data-id="<?= $pedido['ID'] ?>">Listo</button>
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

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#tabla-pedidos')) {
      $('#tabla-pedidos').DataTable().clear().destroy();
    }

    $('#tabla-pedidos').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      responsive: true,
      fixedHeader: true,
      language: {
        decimal: "",
        emptyTable: "No hay datos disponibles en la tabla",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros totales)",
        lengthMenu: "Mostrar registros: _MENU_",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendente",
          sortDescending: ": activar para ordenar la columna descendente"
        }
      }
    });
  });

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

      if (resultado.success) {
        alert(`Pedido #${pedidoId} actualizado a "${nuevoEstado}" correctamente.`);
        const fila = boton.closest('tr');
        fila.remove();

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

      if (nuevoEstado === 'Cancelado') {
        if (!confirm('¿Estás seguro de cancelar este pedido? Esto notificará al cliente.')) return;
      }

      actualizarEstado(pedidoId, nuevoEstado, btn);
    });
  });

  // Refrescar automáticamente cada 10 segundos
  setInterval(() => location.reload(), 10000);
</script>

<?php include("../admin/templates/footer.php"); ?>
