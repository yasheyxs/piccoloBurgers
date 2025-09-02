<?php
include("../../bd.php");

if ($_POST) {
  $nombre = (isset($_POST["nombre"])) ? $_POST["nombre"] : "";
  $telefono = (isset($_POST["telefono"])) ? $_POST["telefono"] : "";
  $email = (isset($_POST["email"])) ? $_POST["email"] : null;

  $sentencia = $conexion->prepare("INSERT INTO tbl_proveedores (ID, nombre, telefono, email) 
    VALUES (NULL, :nombre, :telefono, :email);");

  $sentencia->bindParam(":nombre", $nombre);
  $sentencia->bindParam(":telefono", $telefono);
  $sentencia->bindParam(":email", $email);

  $sentencia->execute();
  header("Location:index.php");
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    Agregar proveedor
  </div>
  <div class="card-body">

    <form action="" method="post">

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
