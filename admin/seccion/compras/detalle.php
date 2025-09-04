<?php
include("../../bd.php");

$compra = null;
$detalles = [];

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    // Obtener datos de la compra
    $sentencia = $conexion->prepare("
      SELECT c.fecha, p.nombre AS proveedor, p.telefono
      FROM tbl_compras c
      JOIN tbl_proveedores p ON c.proveedor_id = p.ID
      WHERE c.ID = :id
    ");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
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
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();
    $detalles = $sentencia->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    error_log("Error al obtener detalles de compra: " . $e->getMessage());
    echo "<script>alert('Error al cargar los datos de la compra.');</script>";
  }
} else {
  echo "<script>alert('ID inválido o no proporcionado.'); window.location.href='index.php';</script>";
  exit;
}

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <strong>Detalles de compra</strong>
  </div>
  <div class="card-body">

    <?php if ($compra) { ?>
      <p><strong>Fecha:</strong> <?= htmlspecialchars($compra["fecha"]) ?></p>
      <p><strong>Proveedor:</strong> <?= htmlspecialchars($compra["proveedor"]) ?> (<?= htmlspecialchars($compra["telefono"]) ?>)</p>

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
                <td><?= htmlspecialchars($item["materia"]) ?></td>
                <td><?= htmlspecialchars($item["cantidad"]) ?></td>
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
    <?php } else { ?>
      <div class="alert alert-warning">No se encontraron datos para esta compra.</div>
    <?php } ?>

    <a class="btn btn-primary" href="index.php" role="button">Volver al historial</a>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
