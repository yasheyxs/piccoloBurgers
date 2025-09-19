<?php
session_start();
include("../bd.php");
require_once __DIR__ . '/../../componentes/password_utils.php';

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
          <input type="password" class="form-control" name="actual" id="actual" required autocomplete="current-password">
        </div>

        <div class="mb-3">
          <label for="nueva" class="form-label">Nueva contraseña</label>
<input
            type="password"
            class="form-control"
            name="nueva"
            id="nueva"
            required
            autocomplete="new-password"
            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[\\W_]).{8,}"
            title="<?php echo mensajeRequisitosPassword(); ?>"
          >
          <div class="form-text"><?php echo mensajeRequisitosPassword(); ?></div>        </div>

        <div class="mb-3">
          <label for="confirmar" class="form-label">Confirmar nueva contraseña</label>
          <input type="password" class="form-control" name="confirmar" id="confirmar" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-success">Actualizar contraseña</button>
      </form>
    </div>
  </div>
</div>

<?php include("../templates/footer.php"); ?>
