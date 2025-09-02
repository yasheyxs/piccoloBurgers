<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
  $txtID = $_GET["txtID"] ?? "";

  // Obtener datos de la compra
  $sentencia = $conexion->prepare("
    SELECT c.fecha, p.nombre AS proveedor, p.telefono
    FROM tbl_compras c
    JOIN tbl_proveedores p ON c.proveedor_id = p.ID
    WHERE c.ID = :id
  ");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $compra = $sentencia->fetch(PDO::FETCH_ASSOC);

  // Obtener detalles de la compra
  $sentencia = $conexion->prepare("
    SELECT mp.nombre AS materia, cd.cantidad, cd.precio_unitario,
           (cd.cantidad * cd.precio_unitario) AS subtotal
    FROM tbl_compras_detalle cd
    JOIN tbl_materias_primas mp ON cd.materia_prima_id = mp.ID
    WHERE cd.compra_id = :id
  ");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $detalles = $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <strong>Detalles de compra</strong>
  </div>
  <div class="card-body">

    <p><strong>Fecha:</strong> <?= $compra["fecha"] ?></p>
    <p><strong>Proveedor:</strong> <?= $compra["proveedor"] ?> (<?= $compra["telefono"] ?>)</p>

    <div class="table-responsive">
      <table class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Materia prima</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $total = 0;
          foreach ($detalles as $item) {
            $total += $item["subtotal"];
          ?>
            <tr>
              <td><?= $item["materia"] ?></td>
              <td><?= $item["cantidad"] ?></td>
              <td>$<?= number_format($item["precio_unitario"], 2) ?></td>
              <td>$<?= number_format($item["subtotal"], 2) ?></td>
            </tr>
          <?php } ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total:</th>
            <th>$<?= number_format($total, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <a class="btn btn-primary" href="index.php" role="button">Volver al historial</a>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
