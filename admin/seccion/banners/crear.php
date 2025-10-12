<?php
include("../../bd.php");

$errorMensaje = "";
$titulo = "";
$descripcion = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = trim($_POST["titulo"] ?? "");
  $descripcion = trim($_POST["descripcion"] ?? "");

  if ($titulo === "" || $descripcion === "") {
    $errorMensaje = "Debe completar el título y la descripción.";
  }

  $nuevaImagen = null;

  if (!$errorMensaje && isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivoImagen = $_FILES['imagen'];

    if ($archivoImagen['error'] !== UPLOAD_ERR_OK) {
      $errorMensaje = "No se pudo cargar la imagen seleccionada.";
    } else {
      $infoImagen = @getimagesize($archivoImagen['tmp_name']);

      if ($infoImagen === false) {
        $errorMensaje = "El archivo seleccionado no es una imagen válida.";
      } else {
        $mime = $infoImagen['mime'] ?? '';
        $ancho = $infoImagen[0] ?? 0;
        $alto = $infoImagen[1] ?? 0;
        $formatosPermitidos = [
          'image/jpeg' => 'jpg',
          'image/png' => 'png',
          'image/webp' => 'webp',
        ];

        if (!array_key_exists($mime, $formatosPermitidos)) {
          $errorMensaje = "Formato de imagen no permitido. Utilice JPG, PNG o WEBP.";
        } elseif ($ancho < 1200 || $alto < 600) {
          $errorMensaje = "La imagen debe tener como mínimo 1200px de ancho y 600px de alto para evitar pixelación.";
        } else {
          $directorioSubidas = __DIR__ . '/../../../public/img/banners';

          if (!is_dir($directorioSubidas)) {
            if (!mkdir($directorioSubidas, 0775, true) && !is_dir($directorioSubidas)) {
              $errorMensaje = "No fue posible preparar el directorio de imágenes.";
            }
          }

          if (!$errorMensaje) {
            $extension = $formatosPermitidos[$mime];
            $nombreArchivo = sprintf('banner_%s.%s', uniqid('', true), $extension);
            $rutaDestino = $directorioSubidas . '/' . $nombreArchivo;

            if (!move_uploaded_file($archivoImagen['tmp_name'], $rutaDestino)) {
              $errorMensaje = "No se pudo guardar la imagen en el servidor.";
            } else {
              $nuevaImagen = 'img/banners/' . $nombreArchivo;
            }
          }
        }
      }
    }
  } else {
    $errorMensaje = "Debe seleccionar una imagen válida para el banner.";
  }

  if (!$errorMensaje && $nuevaImagen !== null) {
    $link = '#menu';
    $sentencia = $conexion->prepare("INSERT INTO `tbl_banners`
             (`titulo`, `descripcion`, `link`, `imagen`)
             VALUES (:titulo, :descripcion, :link, :imagen);");

    $sentencia->bindParam(":titulo", $titulo, PDO::PARAM_STR);
    $sentencia->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
    $sentencia->bindParam(":link", $link, PDO::PARAM_STR);
    $sentencia->bindParam(":imagen", $nuevaImagen, PDO::PARAM_STR);

    $sentencia->execute();
    header("Location:index.php");
    exit;
  }
}
include("../../templates/header.php");
?>
<br />
<div class="card">
  <div class="card-header">
    Banners
  </div>
  <div class="card-body">

    <?php if ($errorMensaje) { ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($errorMensaje, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <form action="" method="post" enctype="multipart/form-data">

      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input type="text"
          class="form-control" name="titulo" id="titulo" value="<?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="helpId" placeholder="Escriba el título del banner">
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text"
          class="form-control" name="descripcion" id="descripcion" value="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="helpId" placeholder="Escriba la descripción del banner">

      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen del banner</label>
        <input class="form-control" type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">Formatos permitidos: JPG, PNG o WEBP. Tamaño mínimo: 1200x600 píxeles.</div>
      </div>

      <button type="submit" class="btn btn-success">Crear banner</button>
      <a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>


    </form>

  </div>
  <div class="card-footer text-muted">

  </div>
</div>


<?php include("../../templates/footer.php"); ?>