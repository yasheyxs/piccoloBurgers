<?php
include("../../bd.php");

// Procesar modificación
if ($_POST) {
  $txtID = $_POST["txtID"] ?? "";
  $nombre = trim($_POST["nombre"] ?? "");
  $ingredientes = trim($_POST["ingredientes"] ?? "");
  $precio = $_POST["precio"] ?? "";
  $categoria = $_POST["categoria"] ?? "";

  if (!is_numeric($txtID) || $nombre === "" || $ingredientes === "" || !is_numeric($precio) || $precio < 0) {
    echo "<script>alert('Datos inválidos. Verifica los campos.');</script>";
  } else {
    try {
      $sentencia = $conexion->prepare("UPDATE tbl_menu SET
        nombre = :nombre, 
        ingredientes = :ingredientes,
        precio = :precio,
        categoria = :categoria 
        WHERE ID = :id");

      $sentencia->bindParam(":nombre", $nombre);
      $sentencia->bindParam(":ingredientes", $ingredientes);
      $sentencia->bindParam(":precio", $precio);
      $sentencia->bindParam(":categoria", $categoria);
      $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
      $sentencia->execute();

      // Proceso de actualización de foto
      $foto = $_FILES['foto']['name'] ?? "";
      $tmp_foto = $_FILES['foto']['tmp_name'] ?? "";

      if ($foto && $tmp_foto) {
        $fecha_foto = new DateTime();
        $nombre_foto = $fecha_foto->getTimestamp() . "_" . basename($foto);
        $destino = "../../../public/img/menu/" . $nombre_foto;

        if (!move_uploaded_file($tmp_foto, $destino)) {
          throw new Exception("No se pudo guardar la imagen.");
        }

        // Eliminar foto anterior
        $sentencia = $conexion->prepare("SELECT foto FROM tbl_menu WHERE ID = :id");
        $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
        $sentencia->execute();
        $registro_foto = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($registro_foto && isset($registro_foto['foto'])) {
          $foto_anterior = $registro_foto['foto'];
          $ruta_anterior = "../../../public/img/menu/" . $foto_anterior;
          if (file_exists($ruta_anterior)) {
            unlink($ruta_anterior);
          }
        }

        // Guardar nueva foto
        $sentencia = $conexion->prepare("UPDATE tbl_menu SET foto = :foto WHERE ID = :id");
        $sentencia->bindParam(":foto", $nombre_foto);
        $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
        $sentencia->execute();
      }

      header("Location:index.php");
      exit;
    } catch (Exception $e) {
      error_log("Error al modificar comida: " . $e->getMessage());
      echo "<script>alert('Error al modificar el menú. Intenta nuevamente.');</script>";
    }
  }
}

// Obtener datos para edición
$nombre = "";
$ingredientes = "";
$foto = "";
$precio = "";
$categoria = "";

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_menu WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
      $nombre = $registro["nombre"];
      $ingredientes = $registro["ingredientes"];
      $foto = $registro["foto"];
      $precio = $registro["precio"];
      $categoria = $registro["categoria"];
    } else {
      echo "<script>alert('Menú no encontrado.'); window.location.href='index.php';</script>";
      exit;
    }
  } catch (Exception $e) {
    error_log("Error al obtener menú: " . $e->getMessage());
    echo "<script>alert('Error al cargar los datos.'); window.location.href='index.php';</script>";
    exit;
  }
} else {
  echo "<script>alert('ID inválido o no proporcionado.'); window.location.href='index.php';</script>";
  exit;
}

include("../../templates/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    Menú de comida
  </div>
  <div class="card-body">

    <form action="" method="post" enctype="multipart/form-data">

      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($txtID) ?>" name="txtID" id="txtID" readonly>
      </div>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($nombre) ?>" name="nombre" id="nombre" required>
      </div>

      <div class="mb-3">
        <label for="ingredientes" class="form-label">Ingredientes (separados por comas):</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($ingredientes) ?>" name="ingredientes" id="ingredientes" required>
      </div>

      <div class="mb-3">
        <label for="foto" class="form-label">Foto:</label><br />
        <?php if ($foto): ?>
          <img width="50" src="../../../public/img/menu/<?= htmlspecialchars($foto) ?>" alt="Foto actual">
        <?php endif; ?>
        <input type="file" class="form-control mt-2" name="foto" id="foto" accept="image/*">
      </div>

      <div class="mb-3">
        <label for="precio" class="form-label">Precio:</label>
        <input type="number" step="0.01" min="0" class="form-control" name="precio" value="<?= htmlspecialchars($precio) ?>" id="precio" required>
      </div>

      <div class="mb-3">
        <label for="categoria" class="form-label">Categoría:</label>
        <select class="form-select" name="categoria" id="categoria" required>
          <option value="">Seleccionar...</option>
          <option value="Hamburguesas" <?= ($categoria == 'Hamburguesas') ? 'selected' : '' ?>>Hamburguesas</option>
          <option value="Lomitos y Sándwiches" <?= ($categoria == 'Lomitos y Sándwiches') ? 'selected' : '' ?>>Lomitos y Sándwiches</option>
          <option value="Pizzas" <?= ($categoria == 'Pizzas') ? 'selected' : '' ?>>Pizzas</option>
          <option value="Bebidas" <?= ($categoria == 'Bebidas') ? 'selected' : '' ?>>Bebidas</option>
          <option value="Acompañamientos" <?= ($categoria == 'Acompañamientos') ? 'selected' : '' ?>>Acompañamientos</option>
        </select>
      </div>

      <button type="submit" class="btn btn-success">Modificar comida</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
