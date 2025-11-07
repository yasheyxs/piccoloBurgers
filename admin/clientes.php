<?php
include("../admin/bd.php");

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$mensajeClientes = $_SESSION['mensaje_clientes'] ?? '';
$errorClientes = $_SESSION['error_clientes'] ?? '';
unset($_SESSION['mensaje_clientes'], $_SESSION['error_clientes']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? '';
  $clienteId = isset($_POST['cliente_id']) ? (int) $_POST['cliente_id'] : 0;
  $tokenSesion = $_SESSION['csrf_token'] ?? '';
  $tokenRecibido = $_POST['csrf_token'] ?? '';

  if (!$tokenRecibido || !$tokenSesion || !hash_equals($tokenSesion, $tokenRecibido)) {
    http_response_code(403);
    exit('Token CSRF inválido.');
  }

  if ($accion === 'eliminar' && $clienteId > 0) {
    try {
      $conexion->beginTransaction();

      $stmtPedidos = $conexion->prepare('UPDATE tbl_pedidos SET cliente_id = NULL WHERE cliente_id = :id');
      $stmtPedidos->bindParam(':id', $clienteId, PDO::PARAM_INT);
      $stmtPedidos->execute();

      $stmtMovimientos = $conexion->prepare('DELETE FROM movimientos_puntos WHERE cliente_id = :id');
      $stmtMovimientos->bindParam(':id', $clienteId, PDO::PARAM_INT);
      $stmtMovimientos->execute();

      $stmtCliente = $conexion->prepare('DELETE FROM tbl_clientes WHERE ID = :id');
      $stmtCliente->bindParam(':id', $clienteId, PDO::PARAM_INT);
      $stmtCliente->execute();

      $conexion->commit();
      $_SESSION['mensaje_clientes'] = 'El cliente se eliminó correctamente.';
    } catch (Throwable $error) {
      if ($conexion->inTransaction()) {
        $conexion->rollBack();
      }
      error_log('No se pudo eliminar el cliente: ' . $error->getMessage());
      $_SESSION['error_clientes'] = 'Ocurrió un error al intentar eliminar al cliente. Por favor, intentá nuevamente.';
    }
  }

  header('Location: clientes.php');
  exit();
}

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
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Clientes registrados</h5>
    <a class="btn btn-primary btn-sm" href="clientes_crear.php">
      <i class="fa-solid fa-user-plus me-1"></i>
      Crear nuevo cliente
    </a>
  </div>
  <div class="card-body">
    <?php if ($mensajeClientes) { ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensajeClientes, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php } ?>
    <?php if ($errorClientes) { ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($errorClientes, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php } ?>
    <div class="table-responsive">
      <table id="tablaClientes" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Puntos</th>
            <th>Cantidad de pedidos</th>
            <th>Último pedido</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_clientes as $cliente) {
            $nombreSeguro = htmlspecialchars($cliente["nombre"] ?? '', ENT_QUOTES, 'UTF-8');
            $telefonoSeguro = htmlspecialchars($cliente["telefono"] ?? '', ENT_QUOTES, 'UTF-8');
            $puntos = (int) ($cliente['puntos'] ?? 0);
            $clienteId = (int) ($cliente['ID'] ?? 0);
          ?>
            <tr>
              <td><?= $nombreSeguro ?></td>
              <td><?= $telefonoSeguro ?></td>
              <td><?= number_format($puntos, 0, ',', '.') ?></td>
              <td><?= $cliente["total_pedidos"] ?></td>
              <td>
                <?php if (!empty($cliente["ultimo_pedido"])) { ?>
                  <?= date("d/m/Y H:i", strtotime($cliente["ultimo_pedido"])) ?>
                <?php } else { ?>
                  <span class="text-muted">Sin pedidos</span>
                <?php } ?>
              </td>

              <td>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-outline-primary btn-sm" href="clientes_detalle.php?id=<?= $clienteId ?>" title="Ver detalles" aria-label="Ver detalles">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                  <a class="btn btn-outline-success btn-sm" href="clientes_canje.php?id=<?= $clienteId ?>" title="Canjear puntos" aria-label="Canjear puntos">
                    <i class="fa-solid fa-gift"></i>
                  </a>
                  <a class="btn btn-outline-secondary btn-sm" href="clientes_editar.php?id=<?= $clienteId ?>" title="Editar cliente" aria-label="Editar cliente">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form method="post" class="d-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="cliente_id" value="<?= $clienteId ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar cliente" aria-label="Eliminar cliente">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
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
  document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#tablaClientes');
  });
</script>

<?php include("../admin/templates/footer.php"); ?>