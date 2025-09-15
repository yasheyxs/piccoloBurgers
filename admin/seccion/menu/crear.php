<?php
include("../../bd.php");

if ($_POST) {
  $nombre = trim($_POST["nombre"] ?? "");
  $ingredientes = trim($_POST["ingredientes"] ?? "");
  $precio = $_POST["precio"] ?? "";
  $categoria = $_POST["categoria"] ?? "General";

  $foto = $_FILES['foto']['name'] ?? "";
  $tmp_foto = $_FILES['foto']['tmp_name'] ?? "";

  // Validaciones básicas
  if ($nombre === "" || $ingredientes === "" || !is_numeric($precio) || $precio < 0) {
    echo "<script>alert('Datos inválidos. Verifica los campos.');</script>";
  } else {
    try {
      $nombre_foto = "";
      if ($tmp_foto && $foto) {
        $fecha_foto = new DateTime();
        $nombre_foto = $fecha_foto->getTimestamp() . "_" . basename($foto);

        $destino = "../../../public/img/menu/" . $nombre_foto;
        if (!move_uploaded_file($tmp_foto, $destino)) {
          throw new Exception("No se pudo guardar la imagen.");
        }
      }

      $sentencia = $conexion->prepare("INSERT INTO 
        tbl_menu (ID, nombre, ingredientes, foto, precio, categoria) 
        VALUES (NULL, :nombre, :ingredientes, :foto, :precio, :categoria)");

      $sentencia->bindParam(":nombre", $nombre);
      $sentencia->bindParam(":ingredientes", $ingredientes);
      $sentencia->bindParam(":foto", $nombre_foto);
      $sentencia->bindParam(":precio", $precio);
      $sentencia->bindParam(":categoria", $categoria);
      $sentencia->execute();

      header("Location:index.php");
      exit;
    } catch (Exception $e) {
      error_log("Error al agregar comida: " . $e->getMessage());
      echo "<script>alert('Error al registrar el menú. Intenta nuevamente.');</script>";
    }
  }
}

include("../../templates/header.php");
?>

<!-- Estilo para flecha en el dropdown -->
<style>
  .custom-select-wrapper {
    position: relative;
  }

  .custom-select-arrow {
    padding-right: 2.5rem;
  }

  .custom-select-wrapper::after {
    content: "▾";
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 1rem;
    color: #6c757d;
  }
</style>

<br />
<div class="card">
  <div class="card-header">
    Menú de comida
  </div>
  <div class="card-body">

    <form action="" method="post" enctype="multipart/form-data">

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ej: Pizza Napolitana" required>
      </div>

      <div class="mb-3">
        <label for="ingredientes" class="form-label">Ingredientes (separados por comas):</label>
        <input type="text" class="form-control" name="ingredientes" id="ingredientes" placeholder="Ej: Tomate, Mozzarella, Albahaca" required>
      </div>

      <div class="mb-3">
        <label for="categoria" class="form-label">Categoría:</label>
        <div class="custom-select-wrapper">
          <select class="form-control custom-select-arrow" name="categoria" id="categoria">
            <option value="Hamburguesas">Hamburguesas</option>
            <option value="Lomitos y Sándwiches">Lomitos y Sándwiches</option>
            <option value="Pizzas">Pizzas</option>
            <option value="Bebidas">Bebidas</option>
            <option value="Acompañamientos">Acompañamientos</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label for="foto" class="form-label">Foto:</label>
        <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
      </div>

      <div class="mb-3">
        <label for="precio" class="form-label">Precio:</label>
        <input type="number" step="0.01" min="0" class="form-control" name="precio" id="precio" placeholder="Ej: 1500.00" required>
      </div>

      <button type="submit" class="btn btn-success">Agregar comida</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
