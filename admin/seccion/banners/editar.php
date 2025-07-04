<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
  // Si se envió el ID por GET, buscar el banner
  // Validar que se reciba el ID necesario
  $txtID = (isset($_GET["txtID"])) ? $_GET["txtID"] : "";

  $sentencia = $conexion->prepare("SELECT * FROM `tbl_banners` WHERE ID=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
// Verificar si se encontró el banner
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);
  $titulo = $registro["titulo"];
  $descripcion = $registro["descripcion"];
  $link = $registro["link"];
}

if ($_POST) {
// Si se envió el formulario, procesar los datos
  $titulo = (isset($_POST["titulo"])) ? $_POST["titulo"] : "";
  $descripcion = (isset($_POST["descripcion"])) ? $_POST["descripcion"] : "";
  $link = (isset($_POST["link"])) ? $_POST["link"] : "";
  $txtID = (isset($_POST["txtID"])) ? $_POST["txtID"] : "";
// Validar que se reciban los datos necesarios
  $sentencia = $conexion->prepare(" UPDATE `tbl_banners`
             SET titulo=:titulo,  descripcion=:descripcion, link=:link
             WHERE ID=:id");
// Preparar la consulta para actualizar el banner
  // Usar bindParam para evitar inyecciones SQL
  $sentencia->bindParam(":titulo", $titulo);
  $sentencia->bindParam(":descripcion", $descripcion);
  $sentencia->bindParam(":link", $link);
  $sentencia->bindParam(":id", $txtID);

  $sentencia->execute();
  header("Location:index.php");
}

include("../../templates/header.php");

?>
<br />
<div class="card">
  <div class="card-header">
    Banners
  </div>
  <div class="card-body">

    <form action="" method="post">

      <div class="mb-3">
        <label for="titulo" class="form-label">ID:</label>
        <input type="text"
          class="form-control" value="<?php echo $txtID; ?>" name="txtID" id="txtID" aria-describedby="helpId" placeholder="Escriba el título del banner">
      </div>


      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input type="text"
          class="form-control" value="<?php echo $titulo; ?>" name="titulo" id="titulo" aria-describedby="helpId" placeholder="Escriba el título del banner">
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text"
          class="form-control" value="<?php echo $descripcion; ?>" name="descripcion" id="descripcion" aria-describedby="helpId" placeholder="Escriba la descripción del banner">

      </div>

      <div class="mb-3">
        <label for="link" class="form-label">Link</label>
        <input type="text"
          class="form-control" value="<?php echo $link; ?>" name="link" id="link" aria-describedby="helpId" placeholder="Escriba el enlace">
      </div>

      <button type="submit" class="btn btn-success">Modificar banner</button>
      <a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>


    </form>

  </div>
  <div class="card-footer text-muted">

  </div>
</div>
<?php include("../../templates/footer.php"); ?>