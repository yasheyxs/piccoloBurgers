<?php
include("../admin/bd.php");

// Obtener lista de clientes
$sentencia = $conexion->prepare("SELECT * FROM tbl_clientes ORDER BY fecha_registro DESC");
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
            <th>Puntos</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_clientes as $cliente) { ?>
            <tr>
              <td><?= $cliente["nombre"] ?></td>
              <td><?= $cliente["telefono"] ?></td>
              <td><?= $cliente["email"] ?></td>
              <td><?= date("d/m/Y", strtotime($cliente["fecha_registro"])) ?></td>
              <td><?= $cliente["puntos"] ?></td>
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
