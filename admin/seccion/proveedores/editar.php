<?php
include("../../bd.php");

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $txtID = trim($_POST["txtID"] ?? "");
  $nombre = trim($_POST["nombre"] ?? "");
  $telefono = trim($_POST["telefono"] ?? "");
  $email = trim($_POST["email"] ?? "");

  if (!is_numeric($txtID) || intval($txtID) <= 0) {
    $mensaje = "ID inválido para modificar proveedor.";
    $tipoMensaje = "danger";
  } elseif ($nombre === "" || $telefono === "") {
    $mensaje = "El nombre y el teléfono son obligatorios.";
    $tipoMensaje = "danger";
  } elseif (!preg_match('/^[\p{L}\s\-]+$/u', $nombre)) {
    $mensaje = "El nombre contiene caracteres inválidos.";
    $tipoMensaje = "danger";
  } elseif (!preg_match('/^[0-9+\s\-()]{6,20}$/', $telefono)) {
    $mensaje = "El teléfono no tiene un formato válido.";
    $tipoMensaje = "danger";
  } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "El correo electrónico no es válido.";
    $tipoMensaje = "danger";
  } else {
    try {
      $sentencia = $conexion->prepare("UPDATE tbl_proveedores SET 
        nombre = :nombre,
        telefono = :telefono,
        email = :email
        WHERE ID = :id");

      $sentencia->bindParam(":nombre", $nombre);
      $sentencia->bindParam(":telefono", $telefono);
      $sentencia->bindParam(":email", $email !== "" ? $email : null);
      $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
      $sentencia->execute();

      $mensaje = "Proveedor modificado correctamente.";
      $tipoMensaje = "success";
    } catch (PDOException $e) {
      $mensaje = "Error al modificar proveedor: " . htmlspecialchars($e->getMessage());
      $tipoMensaje = "danger";
    }
  }
}

// Cargar datos del proveedor
$nombre = "";
$telefono = "";
$email = "";

if (isset($_GET['txtID'])) {
  $txtID = trim($_GET["txtID"] ?? "");

  if (!is_numeric($txtID) || intval($txtID) <= 0) {
    $mensaje = "ID inválido para cargar proveedor.";
    $tipoMensaje = "danger";
  } else {
    try {
      $sentencia = $conexion->prepare("SELECT * FROM tbl_proveedores WHERE ID = :id");
      $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
      $sentencia->execute();
      $registro = $sentencia->fetch(PDO::FETCH_ASSOC);

      if ($registro) {
        $nombre = $registro["nombre"];
        $telefono = $registro["telefono"];
        $email = $registro["email"];
      } else {
        $mensaje = "Proveedor no encontrado.";
        $tipoMensaje = "warning";
      }
    } catch (PDOException $e) {
      $mensaje = "Error al cargar proveedor: " . htmlspecialchars($e->getMessage());
      $tipoMensaje = "danger";
    }
  }
}

include("../../templates/header.php");
?>

<br />
<?php if ($mensaje): ?>
  <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    Editar proveedor
  </div>
  <div class="card-body">

    <form action="" method="post" novalidate>

      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($txtID); ?>" name="txtID" id="txtID" readonly>
      </div>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombre); ?>" name="nombre" id="nombre" required>
      </div>

      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($telefono); ?>" name="telefono" id="telefono" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" name="email" id="email">
      </div>

      <button type="submit" class="btn btn-success">Modificar proveedor</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
