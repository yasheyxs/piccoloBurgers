<?php
include("../admin/bd.php");

// Obtener lista de clientes junto con métricas de pedidos
$sentencia = $conexion->prepare("SELECT c.*, COALESCE(p.total_pedidos, 0) AS total_pedidos, p.ultimo_pedido
  FROM tbl_clientes c
  LEFT JOIN (
    SELECT cliente_id, COUNT(*) AS total_pedidos, MAX(fecha) AS ultimo_pedido
    FROM tbl_pedidos
    GROUP BY cliente_id
  ) p ON p.cliente_id = c.ID
  ORDER BY c.fecha_registro DESC");
$sentencia->execute();
$lista_clientes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../admin/templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Clientes registrados</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaClientes" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Fecha de registro</th>
            <th>Cantidad de pedidos</th>
            <th>Último pedido</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_clientes as $cliente) { ?>
            <tr>
              <td><?= $cliente["nombre"] ?></td>
              <td><?= $cliente["telefono"] ?></td>
              <td><?= $cliente["email"] ?></td>
              <td><?= date("d/m/Y", strtotime($cliente["fecha_registro"])) ?></td>
              <td><?= $cliente["total_pedidos"] ?></td>
              <td>
                <?php if (!empty($cliente["ultimo_pedido"])) { ?>
                  <?= date("d/m/Y H:i", strtotime($cliente["ultimo_pedido"])) ?>
                <?php } else { ?>
                  <span class="text-muted">Sin pedidos</span>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDataTable('#tablaClientes');
  });
</script>

<?php include("../admin/templates/footer.php"); ?>
