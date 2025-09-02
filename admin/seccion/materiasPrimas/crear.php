<?php
include("../../bd.php");

// Obtener lista de proveedores para el select
$sentencia = $conexion->prepare("SELECT ID, nombre FROM tbl_proveedores");
$sentencia->execute();
$lista_proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
  $nombre = $_POST["nombre"] ?? "";
  $unidad_medida = $_POST["unidad_medida"] ?? "";
  $cantidad = $_POST["cantidad"] ?? 0;
  $proveedor_id = $_POST["proveedor_id"] ?? null;

  $sentencia = $conexion->prepare("INSERT INTO tbl_materias_primas 
    (ID, nombre, unidad_medida, cantidad, proveedor_id) 
    VALUES (NULL, :nombre, :unidad_medida, :cantidad, :proveedor_id)");

  $sentencia->bindParam(":nombre", $nombre);
  $sentencia->bindParam(":unidad_medida", $unidad_medida);
  $sentencia->bindParam(":cantidad", $cantidad);
  $sentencia->bindParam(":proveedor_id", $proveedor_id);

  $sentencia->execute();
  header("Location:index.php");
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    Agregar materia prima
  </div>
  <div class="card-body">

    <form action="" method="post">

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ej: Harina, Tomate" required>
      </div>

      <div class="mb-3">
        <label for="unidad_medida" class="form-label">Unidad de medida:</label>
        <input type="text" class="form-control" name="unidad_medida" id="unidad_medida" placeholder="Ej: kg, litros" required>
      </div>

      <div class="mb-3">
        <label for="cantidad" class="form-label">Cantidad:</label>
        <input type="number" step="0.01" class="form-control" name="cantidad" id="cantidad" placeholder="Ej: 5.00" required>
      </div>

      <div class="mb-3">
        <label for="proveedor_id" class="form-label">Proveedor:</label>
        <select class="form-select" name="proveedor_id" id="proveedor_id">
          <option value="">Sin proveedor</option>
          <?php foreach ($lista_proveedores as $proveedor) { ?>
            <option value="<?= $proveedor['ID'] ?>"><?= $proveedor['nombre'] ?></option>
          <?php } ?>
        </select>
      </div>

      <button type="submit" class="btn btn-success">Agregar materia prima</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
