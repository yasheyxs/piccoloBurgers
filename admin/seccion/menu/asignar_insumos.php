<?php
include("../../bd.php");

// Validar menu_id
$menu_id = $_GET['menu_id'] ?? null;
if (!$menu_id || !is_numeric($menu_id)) {
  echo "<script>alert('Menú no especificado o inválido.'); window.location.href='index.php';</script>";
  exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cantidades = $_POST['cantidad'] ?? [];
  $eliminar = $_POST['eliminar'] ?? [];

  try {
    foreach ($cantidades as $mp_id => $valor) {
      $cantidad = floatval($valor);

      if (!is_numeric($mp_id)) continue;

      // Si está marcado para eliminar
      if (in_array($mp_id, $eliminar)) {
        $stmtDelete = $conexion->prepare("DELETE FROM tbl_menu_materias_primas WHERE menu_id = ? AND materia_prima_id = ?");
        $stmtDelete->execute([$menu_id, $mp_id]);
        continue;
      }

      if ($cantidad > 0) {
        // Verificar si ya existe
        $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM tbl_menu_materias_primas WHERE menu_id = ? AND materia_prima_id = ?");
        $stmtCheck->execute([$menu_id, $mp_id]);
        $existe = $stmtCheck->fetchColumn();

        if ($existe) {
          $stmtUpdate = $conexion->prepare("UPDATE tbl_menu_materias_primas SET cantidad = ? WHERE menu_id = ? AND materia_prima_id = ?");
          $stmtUpdate->execute([$cantidad, $menu_id, $mp_id]);
        } else {
          $stmtInsert = $conexion->prepare("INSERT INTO tbl_menu_materias_primas (menu_id, materia_prima_id, cantidad) VALUES (?, ?, ?)");
          $stmtInsert->execute([$menu_id, $mp_id, $cantidad]);
        }
      }
    }

    header("Location: materias_primas.php?menu_id=$menu_id");
    exit;
  } catch (Exception $e) {
    error_log("Error al guardar insumos del menú: " . $e->getMessage());
    echo "<script>alert('Error al guardar los cambios. Intenta nuevamente.');</script>";
  }
}

// Obtener nombre del menú
try {
  $stmt = $conexion->prepare("SELECT nombre FROM tbl_menu WHERE ID = ?");
  $stmt->execute([$menu_id]);
  $menu_nombre = $stmt->fetchColumn();

  if (!$menu_nombre) {
    echo "<script>alert('Menú no encontrado.'); window.location.href='index.php';</script>";
    exit;
  }
} catch (Exception $e) {
  error_log("Error al obtener nombre del menú: " . $e->getMessage());
  echo "<script>alert('Error al cargar el menú.'); window.location.href='index.php';</script>";
  exit;
}

// Obtener materias primas
try {
  $stmt = $conexion->prepare("SELECT ID, nombre, unidad_medida FROM tbl_materias_primas ORDER BY nombre ASC");
  $stmt->execute();
  $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener materias primas: " . $e->getMessage());
  $materias = [];
}

// Obtener asignaciones actuales
try {
  $stmt = $conexion->prepare("
    SELECT materia_prima_id, cantidad 
    FROM tbl_menu_materias_primas 
    WHERE menu_id = ?
  ");
  $stmt->execute([$menu_id]);
  $asignados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
  error_log("Error al obtener insumos asignados: " . $e->getMessage());
  $asignados = [];
}

include("../../templates/header.php");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Asignar insumos al menú: <strong><?= htmlspecialchars($menu_nombre) ?></strong></h4>
  <a class="btn btn-secondary" href="index.php">← Volver al menú</a>
</div>

<form method="POST">
  <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th>Materia Prima</th>
          <th>Unidad</th>
          <th>Cantidad por unidad vendida</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($materias) > 0): ?>
          <?php foreach ($materias as $mp): ?>
            <?php $valor = $asignados[$mp['ID']] ?? ''; ?>
            <tr>
              <td><?= htmlspecialchars($mp['nombre']) ?></td>
              <td><?= htmlspecialchars($mp['unidad_medida']) ?></td>
              <td>
                <input type="number" step="0.01" min="0" name="cantidad[<?= $mp['ID'] ?>]" value="<?= htmlspecialchars($valor) ?>" class="form-control form-control-sm">
              </td>
              <td class="text-center">
                <?php if (isset($asignados[$mp['ID']])): ?>
                  <input type="checkbox" name="eliminar[]" value="<?= $mp['ID'] ?>">
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="text-center text-muted">No hay materias primas registradas.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <button type="submit" class="btn btn-success mt-3">Guardar cambios</button>
</form>

<?php include("../../templates/footer.php"); ?>
