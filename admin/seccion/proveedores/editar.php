<?php
include("../../bd.php");

if ($_POST) {
  $txtID = (isset($_POST["txtID"])) ? $_POST["txtID"] : "";
  $nombre = (isset($_POST["nombre"])) ? $_POST["nombre"] : "";
  $telefono = (isset($_POST["telefono"])) ? $_POST["telefono"] : "";
  $email = (isset($_POST["email"])) ? $_POST["email"] : null;

  $sentencia = $conexion->prepare("UPDATE tbl_proveedores SET 
    nombre = :nombre,
    telefono = :telefono,
    email = :email
    WHERE ID = :id");

  $sentencia->bindParam(":nombre", $nombre);
  $sentencia->bindParam(":telefono", $telefono);
  $sentencia->bindParam(":email", $email);
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();

  header("Location:index.php");
}

if (isset($_GET['txtID'])) {
  $txtID = (isset($_GET["txtID"])) ? $_GET["txtID"] : "";

  $sentencia = $conexion->prepare("SELECT * FROM tbl_proveedores WHERE ID=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);

  $nombre = $registro["nombre"];
  $telefono = $registro["telefono"];
  $email = $registro["email"];
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    Editar proveedor
  </div>
  <div class="card-body">

    <form action="" method="post">

      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input type="text" class="form-control" value="<?php echo $txtID; ?>" name="txtID" id="txtID" readonly>
      </div>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" value="<?php echo $nombre; ?>" name="nombre" id="nombre" required>
      </div>

      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="text" class="form-control" value="<?php echo $telefono; ?>" name="telefono" id="telefono" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control" value="<?php echo $email; ?>" name="email" id="email">
      </div>

      <button type="submit" class="btn btn-success">Modificar proveedor</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
