<?php
include("../../bd.php");

// Obtener lista de proveedores con manejo de errores
$lista_proveedores = [];
try {
  $sentencia = $conexion->prepare("SELECT ID, nombre FROM tbl_proveedores");
  $sentencia->execute();
  $lista_proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener proveedores: " . $e->getMessage());
  echo "<script>alert('Error al cargar la lista de proveedores.');</script>";
}

// Procesar modificación
if ($_POST) {
  $txtID = $_POST["txtID"] ?? "";
  $nombre = $_POST["nombre"] ?? "";
  $unidad_medida = $_POST["unidad_medida"] ?? "";
  $cantidad = $_POST["cantidad"] ?? 0;
  $proveedor_id = $_POST["proveedor_id"] ?? null;

  if (!is_numeric($txtID)) {
    echo "<script>alert('ID inválido.');</script>";
  } else {
    try {
      $sentencia = $conexion->prepare("UPDATE tbl_materias_primas SET 
        nombre = :nombre,
        unidad_medida = :unidad_medida,
        cantidad = :cantidad,
        proveedor_id = :proveedor_id
        WHERE ID = :id");

      $sentencia->bindParam(":nombre", $nombre);
      $sentencia->bindParam(":unidad_medida", $unidad_medida);
      $sentencia->bindParam(":cantidad", $cantidad);
      $sentencia->bindParam(":proveedor_id", $proveedor_id);
      $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
      $sentencia->execute();

      header("Location:index.php");
      exit;
    } catch (Exception $e) {
      error_log("Error al modificar materia prima: " . $e->getMessage());
      echo "<script>alert('Error al modificar la materia prima. Intenta nuevamente.');</script>";
    }
  }
}

// Obtener datos para edición
$nombre = "";
$unidad_medida = "";
$cantidad = "";
$proveedor_id = "";

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_materias_primas WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
      $nombre = $registro["nombre"];
      $unidad_medida = $registro["unidad_medida"];
      $cantidad = $registro["cantidad"];
      $proveedor_id = $registro["proveedor_id"];
    } else {
      echo "<script>alert('Materia prima no encontrada.'); window.location.href='index.php';</script>";
      exit;
    }
  } catch (Exception $e) {
    error_log("Error al obtener materia prima: " . $e->getMessage());
    echo "<script>alert('Error al cargar los datos.'); window.location.href='index.php';</script>";
    exit;
  }
} else {
  echo "<script>alert('ID inválido o no proporcionado.'); window.location.href='index.php';</script>";
  exit;
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    Editar materia prima
  </div>
  <div class="card-body">

    <form action="" method="post">

      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($txtID) ?>" name="txtID" id="txtID" readonly>
      </div>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($nombre) ?>" name="nombre" id="nombre" required>
      </div>

      <div class="mb-3">
        <label for="unidad_medida" class="form-label">Unidad de medida:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($unidad_medida) ?>" name="unidad_medida" id="unidad_medida" required>
      </div>

      <div class="mb-3">
        <label for="cantidad" class="form-label">Cantidad:</label>
        <input type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($cantidad) ?>" name="cantidad" id="cantidad" required>
      </div>

      <div class="mb-3">
        <label for="proveedor_id" class="form-label">Proveedor:</label>
        <select class="form-select" name="proveedor_id" id="proveedor_id">
          <option value="">Sin proveedor</option>
          <?php foreach ($lista_proveedores as $proveedor) { ?>
            <option value="<?= htmlspecialchars($proveedor['ID']) ?>" <?= ($proveedor_id == $proveedor['ID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($proveedor['nombre']) ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <button type="submit" class="btn btn-success">Modificar materia prima</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
