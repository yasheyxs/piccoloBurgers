<?php
session_start();
include("../bd.php");

if (!isset($_SESSION["admin_logueado"])) {
  header("Location: ../login.php");
  exit();
}

include("../templates/header.php");
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4>Cambiar contraseña</h4>
      <form action="procesar_cambio.php" method="post">
        <div class="mb-3">
          <label for="actual" class="form-label">Contraseña actual</label>
          <input type="password" class="form-control" name="actual" id="actual" required>
        </div>

        <div class="mb-3">
          <label for="nueva" class="form-label">Nueva contraseña</label>
          <input type="password" class="form-control" name="nueva" id="nueva" required>
        </div>

        <div class="mb-3">
          <label for="confirmar" class="form-label">Confirmar nueva contraseña</label>
          <input type="password" class="form-control" name="confirmar" id="confirmar" required>
        </div>

        <button type="submit" class="btn btn-success">Actualizar contraseña</button>
      </form>
    </div>
  </div>
</div>

<?php include("../templates/footer.php"); ?>
