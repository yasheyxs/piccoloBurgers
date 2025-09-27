<?php
include("../../bd.php");

// Inicializar listas
$lista_proveedores = [];
$lista_materias = [];

try {
  // Obtener proveedores
  $sentencia = $conexion->prepare("SELECT ID, nombre, telefono FROM tbl_proveedores");
  $sentencia->execute();
  $lista_proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener proveedores: " . $e->getMessage());
}

try {
  // Obtener materias primas
  $sentencia = $conexion->prepare("SELECT ID, nombre FROM tbl_materias_primas");
  $sentencia->execute();
  $lista_materias = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener materias primas: " . $e->getMessage());
}

if ($_POST) {
  $fecha = $_POST["fecha"] ?? date("Y-m-d");
  $proveedor_id = $_POST["proveedor_id"] ?? null;
  $materias = $_POST["materias"] ?? [];

  if (!$proveedor_id || !is_numeric($proveedor_id)) {
    echo "<script>alert('Proveedor inválido.');</script>";
  } else {
    try {
      $conexion->beginTransaction();

      // Insertar compra
      $sentencia = $conexion->prepare("INSERT INTO tbl_compras (fecha, proveedor_id) VALUES (:fecha, :proveedor_id)");
      $sentencia->bindParam(":fecha", $fecha);
      $sentencia->bindParam(":proveedor_id", $proveedor_id, PDO::PARAM_INT);
      $sentencia->execute();

      $compra_id = $conexion->lastInsertId();

      // Insertar detalles
      foreach ($materias as $item) {
        $materia_id = $item["materia_id"] ?? null;
        $cantidad = $item["cantidad"] ?? null;
        $precio = $item["precio"] ?? null;

        if (
          $materia_id && is_numeric($materia_id) &&
          is_numeric($cantidad) && $cantidad > 0 &&
          is_numeric($precio) && $precio > 0
        ) {
          $sentencia = $conexion->prepare("INSERT INTO tbl_compras_detalle 
            (compra_id, materia_prima_id, cantidad, precio_unitario) 
            VALUES (:compra_id, :materia_id, :cantidad, :precio)");

          $sentencia->bindParam(":compra_id", $compra_id, PDO::PARAM_INT);
          $sentencia->bindParam(":materia_id", $materia_id, PDO::PARAM_INT);
          $sentencia->bindParam(":cantidad", $cantidad);
          $sentencia->bindParam(":precio", $precio);
          $sentencia->execute();

          // Actualizar stock
          $sentencia = $conexion->prepare("UPDATE tbl_materias_primas 
            SET cantidad = cantidad + :cantidad WHERE ID = :materia_id");
          $sentencia->bindParam(":cantidad", $cantidad);
          $sentencia->bindParam(":materia_id", $materia_id, PDO::PARAM_INT);
          $sentencia->execute();
        }
      }

      $conexion->commit();
      header("Location:index.php");
      exit;
    } catch (Exception $e) {
      $conexion->rollBack();
      error_log("Error al registrar compra: " . $e->getMessage());
      echo "<script>alert('Error al registrar la compra. Intenta nuevamente.');</script>";
    }
  }
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">Registrar compra</div>
  <div class="card-body">
    <form method="post">

      <div class="mb-3">
        <label for="fecha" class="form-label">Fecha de compra:</label>
        <input type="date" class="form-control" name="fecha" id="fecha" value="<?= htmlspecialchars(date("Y-m-d")) ?>" required>
      </div>

      <div class="mb-3">
        <label for="proveedor_id" class="form-label">Proveedor:</label>
        <select class="form-select" name="proveedor_id" id="proveedor_id" required>
          <option value="">Seleccionar proveedor</option>
          <?php foreach ($lista_proveedores as $proveedor) { ?>
            <option value="<?= htmlspecialchars($proveedor['ID']) ?>">
              <?= htmlspecialchars($proveedor['nombre']) ?> (<?= htmlspecialchars($proveedor['telefono']) ?>)
            </option>
          <?php } ?>
        </select>
      </div>

      <hr>
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h5 class="mb-0 flex-grow-1">Materias primas</h5>
        <button type="button" class="btn btn-secondary" onclick="agregarMateria()">
          <span class="fw-semibold">+</span> Agregar materia prima
        </button>
      </div>
      <div id="materias-container"></div>
      <br> 
      <button type="submit" class="btn btn-success">Registrar compra</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div> 

<script>
  const materias = <?= json_encode($lista_materias) ?>;
  let materiaIndex = 0;

  function agregarMateria() {
    const container = document.getElementById("materias-container");

    const div = document.createElement("div");
    div.classList.add("row", "mb-2");

    div.innerHTML = `
      <div class="col-md-5">
        <select class="form-select" name="materias[${materiaIndex}][materia_id]" required>
          <option value="">Seleccionar materia prima</option>
          ${materias.map(m => `<option value="${m.ID}">${m.nombre}</option>`).join("")}
        </select>
      </div>
      <div class="col-md-3">
        <input type="number" step="0.01" min="0.01" class="form-control" name="materias[${materiaIndex}][cantidad]" placeholder="Cantidad" required>
      </div>
      <div class="col-md-3">
        <input type="number" step="0.01" min="0.01" class="form-control" name="materias[${materiaIndex}][precio]" placeholder="Precio unitario" required>
      </div>
      <div class="col-md-1 d-flex align-items-center">
        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.row').remove()">✕</button>
      </div>
    `;

    container.appendChild(div);
    materiaIndex++;
  }

  // Al cargar la página, mostrar un bloque por defecto
  window.addEventListener("DOMContentLoaded", () => {
    agregarMateria();
  });

</script>

<?php include("../../templates/footer.php"); ?>
