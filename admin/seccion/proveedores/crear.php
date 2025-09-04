<?php
include("../../bd.php");

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Sanitizar y validar entradas
  $nombre = trim($_POST["nombre"] ?? "");
  $telefono = trim($_POST["telefono"] ?? "");
  $email = trim($_POST["email"] ?? "");

  if ($nombre === "" || $telefono === "") {
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
      $sentencia = $conexion->prepare("INSERT INTO tbl_proveedores (nombre, telefono, email) VALUES (:nombre, :telefono, :email)");
      $sentencia->bindParam(":nombre", $nombre);
      $sentencia->bindParam(":telefono", $telefono);
      $sentencia->bindParam(":email", $email !== "" ? $email : null);
      $sentencia->execute();

      $mensaje = "Proveedor agregado correctamente.";
      $tipoMensaje = "success";
    } catch (PDOException $e) {
      $mensaje = "Error al agregar proveedor: " . htmlspecialchars($e->getMessage());
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
    Agregar proveedor
  </div>
  <div class="card-body">

    <form action="" method="post" novalidate>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre del proveedor" required>
      </div>

      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="text" class="form-control" name="telefono" id="telefono" placeholder="Teléfono de contacto" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control" name="email" id="email" placeholder="Correo electrónico">
      </div>

      <button type="submit" class="btn btn-success">Agregar proveedor</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
