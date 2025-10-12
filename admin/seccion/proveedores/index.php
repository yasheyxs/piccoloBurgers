<?php
include("../../bd.php");

// Inicializar variables
$mensaje = "";
$tipoMensaje = "";

// Manejo de eliminación con validación y try/catch
if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    if (!is_numeric($txtID) || intval($txtID) <= 0) {
        $mensaje = "ID inválido para eliminar proveedor.";
        $tipoMensaje = "danger";
    } else {
        try {
            // Verificar existencia antes de eliminar
            $verificar = $conexion->prepare("SELECT COUNT(*) FROM tbl_proveedores WHERE ID = :id");
            $verificar->bindParam(":id", $txtID, PDO::PARAM_INT);
            $verificar->execute();
            $existe = $verificar->fetchColumn();

            if ($existe > 0) {
                $sentencia = $conexion->prepare("DELETE FROM tbl_proveedores WHERE ID = :id");
                $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
                $sentencia->execute();

                $mensaje = "Proveedor eliminado correctamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "El proveedor no existe o ya fue eliminado.";
                $tipoMensaje = "warning";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al eliminar proveedor: " . htmlspecialchars($e->getMessage());
            $tipoMensaje = "danger";
        }
    }
}

// Obtener lista de proveedores con manejo de errores
try {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_proveedores");
    $sentencia->execute();
    $lista_proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_proveedores = [];
    $mensaje = "Error al cargar proveedores: " . htmlspecialchars($e->getMessage());
    $tipoMensaje = "danger";
}

include("../../templates/header.php");
?>

<br>

<?php if ($mensaje): ?>
  <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar proveedor</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaProveedores" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($lista_proveedores)) : ?>
            <?php foreach ($lista_proveedores as $registro) { ?>
              <tr>
                <td><?php echo htmlspecialchars($registro["nombre"]); ?></td>
                <td><?php echo htmlspecialchars($registro["telefono"]); ?></td>
                <td><?php echo $registro["email"] ? htmlspecialchars($registro["email"]) : '—'; ?></td>
                <td>
                  <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo urlencode($registro['ID']); ?>">Editar</a>
                  <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo urlencode($registro['ID']); ?>" onclick="return confirm('¿Estás seguro de eliminar este proveedor?')">Borrar</a>
                </td>
              </tr>
            <?php } ?>
          <?php else : ?>
            <tr>
              <td colspan="4" class="text-center">No se encontraron proveedores.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#tablaProveedores')) {
      $('#tablaProveedores').DataTable().clear().destroy();
    }

    $('#tablaProveedores').DataTable({
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
</script>

<?php include("../../templates/footer.php"); ?>
